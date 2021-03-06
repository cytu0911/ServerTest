<?php

require_once ROOT."/util/util.php";
require_once ROOT."/config/config.php";


class Server
{
    public $serv  = null;
    public $redis = null;
    public $mysql = null;
    public $gamer = null;
    public $root  = "";

    private $wid;
    private $pid;

    private $srv_lock_ = "srv_lock_";	//.$workId: 事务锁(int)
    private $srv_timer_= "srv_timer_";	//.$hostId重复性定时任务(redis-hash结构/执行前销毁)

    public function __construct()
    {
        $this->num  = 0;
        $this->root = ROOT ;

    }

    public function start()
    {
       $this->serv = new swoole_server("0.0.0.0", SW_PORT);
        $this->serv->set(array(
            'worker_num' => SW_WORKER_NUM,
            'daemonize' => false,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'debug_mode'=> 1,
            'task_worker_num' =>SW_TASK_WORKER_NUM
        ));
        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        $this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->serv->on('pipeMessage',	array($this, 'onPipeMessage'));
        $this->serv->on('Timer', array($this, 'onTimer'));

        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }

    public function onPipeMessage($ss, $src_wid, $data)
    {
        $arrData = json_decode($data,1);
        $act =  $arrData['act'];
        $tick = $arrData['tick'];
        $mtime = microtime(1);
        switch ( $act)
        {
            case 'SCENE':
            {
                $events = $this->redis->hgetall($this->srv_timer_);//1000~2000
                if ( !$events ) break;
                $events_mtime = array();
                foreach ( $events as $k => $v )
                {
                    $ntime = $v['mtime'] + $v['delay'] / 1000;
                    $events[$k]['ntime'] = $events_mtime[$k] = $ntime;
                }
                array_multisort($events_mtime, $events);
                foreach ( $events as $sceneId => $data )
                {
                    if ( $data['ntime'] > $mtime ) break;

                    $lockId = $this->srv_lock_."SCENE".$sceneId;
                    $res = $this->redis->setLock($lockId);
                    if ( !$res ) {
                        gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId data=".json_encode($data));
                        continue;
                    }
                    $event = $this->redis->hget($this->srv_timer_, $sceneId);
                    if ( !$event || $event['mtime'] != $data['mtime'] || $event['delay'] != $data['delay'] ) {
                        $res = $this->redis->delLock($lockId);
                        continue;
                    }
                    $res = $this->redis->hdel($this->srv_timer_, $sceneId);
                    if ( $data['stop'] ) {
                        $res = $this->redis->delLock($lockId);
                        continue;
                    }
                    $res = $this->redis->delLock($lockId);
                    $this->gamer->runEvent($data['act'],$data['params'] );
                }
                break;
            }

        }

    }

    public function onStart( $serv )
    {
        echo "Start\n";
    }

    public function onConnect( $serv, $fd, $from_id )
    {
        echo "workid: {$from_id}   Client {$fd} connect\n";
    }

    public function onTimer($serv, $interval) {

        switch( $interval ) {
            case 10*1000:{
                $this->gamer->TIMERM_10S_ROOMS();
                break;
            }

        }
    }

    public function onWorkerStart( $serv, $workid )
    {
        echo "workid: {$workid}   onWorkerStart\n";

        $this->wid = sprintf("%1$03d", $serv->worker_id);
        $this->pid = sprintf("%1$05d", $serv->worker_pid);

        require($this->root."/class/class.mysql.php");
        require($this->root."/class/class.redis.php");
        require($this->root."/class/class.gamer.php");
        $this->redis = $this->getRedis(1);
        $this->mysql = $this->getMysql(1);
        $this->gamer = new gamer($this->redis, $this->mysql);


        if( $workid == 0 )
        {
            $serv->addtimer(10*1000);
        }
        //定时任务
        if( $workid < SW_WORKER_NUM and $workid == 1  )
        {
            $tick = 300;
            $cb = function($timerId, $tick){
                $this->serv->sendMessage(json_encode(array('act'=>'SCENE', 'tick'=>$tick)), SW_WORKER_NUM + 0 );
            };
            swoole_timer_tick($tick, $cb, $tick);

        }

    }
    public function onReceive( swoole_server $serv, $fd, $from_id, $data )
    {
        echo "{$from_id} Get Message From Client {$fd}:\n";

        $len = UInt32Binary2Int(array_values(unpack("C*",substr($data, 0, 4))));
        $cmd = UInt32Binary2Int(array_values(unpack("C*",substr($data, 4, 4))));
        $sender = UInt32Binary2Int(array_values(unpack("C*",substr($data, 8, 4))));
        $receiver = UInt32Binary2Int(array_values(unpack("C*",substr($data,12, 4))));
        $params= substr($data, 16,$len-12);

        $arr = json_decode($params,true);

        $code = isset( $arr['t'] ) ? intval($arr['t']) : 0 ;
        if ($cmd == 1 )
        {
            $this->gamer->runLogin( $fd, $arr);
        }
        else
        {
            $user = $this->gamer->userMgr->getUserByFd( $fd );

            if( !$user )  $this->closeToFd($fd);
            $req = require ROOT. "/conf/action.php" ;
            $act = isset($req[$cmd][$code]) ? $req[$cmd][$code] : null;
            if(! $act ) $this->closeToFd($fd);
            $action = $act['act'];
            $this->gamer->runAction($fd,$cmd,$code,$action, $arr,$user);
        }
        echo $params . "\n";
        //$data = @json_decode($data_, 1);
    }
    public function onClose( $serv, $fd, $from_id )
    {
        echo "Client {$fd} close connection\n";
        $this->gamer->userMgr->remFdUid($fd);
    }

    public function onTask($serv,$task_id,$from_id, $data)
    {
        echo "This Task {$task_id} from Worker {$from_id}\n";
    }

    public function onFinish($serv,$task_id, $data)
    {
        echo "Task {$task_id} finish\n";
    }

    public function getRedis( $isKeep=false, $isThrow=false )
    {
        if ( !is_null($this->redis) ) return $this->redis;
        $this->redis = new RD($isKeep, $isThrow);
        if ( $this->redis ) return $this->redis;
        return null;
    }
    // MYSQL 获取MySQL对象
    public function getMysql( $isKeep=false, $isThrow=false )
    {
        if ( !is_null($this->mysql) ) return $this->mysql;
        $this->mysql = new DB(MY_HOST, MY_PORT, MY_USER, MY_PASS, MY_BASE, MY_CHAR, $isKeep, $isThrow);
        if ( $this->mysql ) return $this->mysql;
        return null;
    }

    public function closeToFd($fd)
    {
        return $this->serv->close($fd);
    }

    // LOCK 事务加锁
    public function setLock( $lockId, $isOnly=0 )
    {
        return $this->redis->setLock($this->srv_lock_.$lockId, $isOnly);
    }
    // LOCK 事务解锁
    public function delLock( $lockId )
    {
        return $this->redis->delLock($this->srv_lock_.$lockId);
    }

   public function sendClient($fds, $cmd, $code, $data=array() )
    {
        echo "sendClient\n";
        $send['code'] = $code;
        $send['data'] = $data;

        $msg = is_array($send) ? json_encode($send) : $send;
        $len = num2UInt32Str(strlen($msg) + 12);
        $cmd = num2UInt32Str(intval($cmd));
        $cme = num2UInt32Str(0);
        $cmf = num2UInt32Str(0);
        $pack = $len.$cmd.$cme.$cmf.$msg;

        if ( is_array($fds) ) {
            foreach ( $fds as $fd )
            {
                $this->serv->send($fd, $pack);
            }
            return true;
        }
        return $this->serv->send($fds, $pack);
    }

    // TIMER 设置一个场景轮次定时器事件
    public function setTimer($game_id, $sceneId, $act, $params=array(), $delay=0, $hostId=HOSTID )
    {
        echo "setTimer beg \n";
        $sceneId = trim($sceneId);
        $act = trim($act);
        if ( !$sceneId || !$act ) return false;
        $delay = intval($delay);
        if ( $delay < 0 ) {
            $stop = 1;
            $delay *= 1000;
        } else {
            $stop = 0;
        }
        $event = array('act'=>$act, 'game_id' => $game_id, 'params'=>$params, 'lockId'=>$sceneId, 'delay'=>$delay, 'stop'=>$stop, 'mtime'=>microtime(1));

        $lockId = $this->srv_lock_."SCENE".$sceneId;

        $res = $this->redis->setLock($lockId);
        if ( !$res ) {
            gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId event=".json_encode($event));
            return false;
        }
        $res = $this->redis->hset($this->srv_timer_, $sceneId, $event);

        $res2= $this->redis->delLock($lockId);

        return $res;
    }

    // TIMER 修改一个场景轮次定时器事件的执行时间
    public function updTimer( $sceneId, $params, $delay, $hostId )
    {
        if ( $delay < 0 ) {
            $stop = 1;
            $delay *= 1000;
        } else {
            $stop = 0;
        }
        $lockId = $this->srv_lock_."SCENE".$sceneId;
        $res = $this->redis->setLock($lockId);
        if ( !$res ) {
            //gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId delay=$delay params=".json_encode($params));
            return false;
        }
        $event = $this->redis->hget($this->srv_timer_, $sceneId);
        if ( !$event ) {
            $this->redis->delLock($lockId);
            return false;
        }
        if ( $params ) {
            foreach ( $params as $k => $v )
            {
                if ( !isset($event['params'][$k]) || $event['params'][$k] != $v ) {
                    $this->redis->delLock($lockId);
                    return false;
                }
            }
        }
        $event['delay'] = $delay;
        $event['mtime'] = microtime(1);
        $res = $this->redis->hset($this->srv_timer_, $sceneId, $event);
        $res2= $this->redis->delLock($lockId);
        return $res;
    }

    // TIMER 删除一个场景轮次定时器事件
    public function delTimer( $sceneId, $hostId )
    {
        $lockId = $this->srv_lock_."SCENE".$sceneId;
        $res = $this->redis->setLock($lockId);
        if ( !$res ) {
            gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId hostid=".$hostId);
            return false;
        }
        $res = $this->redis->hdel($this->srv_timer_, $sceneId);
        $res2= $this->redis->delLock($lockId);
        return $res;
    }

    // 用户数据 设置
    public function setUser( $uid, $data )
    {
        $data['uid'] = $uid;
        $user = $this->getUser($uid);
        if( $user)
        {
            $user_ = array_merge($user ,$data );
            return $this->gamer->userMgr->setUserInfo($uid,$user_);
        }
        return false;
    }
    public function getUser($uid)
    {
        $userInfo = $this->gamer->userMgr->getUserInfo($uid);
        return $userInfo;
    }
}


function sendToFd($fd, $cmd, $code, $data )
{
    global $server;
    return $server->sendClient($fd, $cmd, $code, $data);
}

//断开连接
// fdu		string 	必须 		连接标示。
// hostId 	string 	默认空字串 	描述信息，会写入日志
function closeToFd( $fd)
{
    $fdu = trim($fd);
    if ( empty($fdu) || !$fdu  ) return false;
    global $server;
    return $server->closeToFd($fdu);
}

//设置一个并发锁
// lockId	string 	必须 		并发锁ID
// isOnly 	num 	默认0 		0忙等锁1互斥锁
// return 	bool 	false|true
function setLock( $lockId, $isOnly=0 )
{
    $lockId = trim($lockId);
    if ( empty($lockId) ) return false;
    global $server;
    return $server->setLock($lockId, intval(!!$isOnly));
}
//销毁一个并发锁
// lockId	string 	必须 		并发锁ID
// return 	bool 	false|true
function delLock( $lockId )
{
    $lockId = trim($lockId);
    if ( empty($lockId) ) return false;
    global $server;
    return $server->delLock($lockId);
}


//添加动态闹钟事件
// sceneId	string 	必须 		场景ID＝闹钟ID
// act		string 	必须 		事件执行时的方法
// params 	array 	必须 		事件执行时的参数
// delay 	int 	必须			延迟指定毫秒数后，开始执行事件。<0未执行的定时器停止
// hostId 	string 	默认HOSTID 	在某个服务器上增加。ALL在所有服务器上增加
function setTimer($game_id, $sceneId, $act, $params, $delay, $hostId=null )
{
    $sceneId = trim($sceneId);
    $act = trim($act);
    $delay = intval($delay);
    $hostId = is_null($hostId) ? HOSTID : trim($hostId);
    if ( empty($sceneId) ||  empty($act) || !is_array($params) ) { echo "setTimer err\n"; return false;}
    global $server;
    return $server->setTimer($game_id, $sceneId, $act, $params, $delay, $hostId);
}
//校验后修改动态闹钟事件的执行时间
// sceneId	string 	必须 		场景ID＝闹钟ID
// params 	array 	必须			校验参数。array()不做校验，否则如果校验用项与原参数某项不符合，不执行修改
// delay 	int 	必须			延迟指定毫秒数后，开始执行事件。<0未执行的定时器停止
// hostId 	string 	必须 		在某个服务器上的闹钟。
function updTimer( $sceneId, $params, $delay, $hostId )
{
    $sceneId = trim($sceneId);
    $delay = intval($delay);
    $hostId = trim($hostId);
    if ( empty($sceneId) || empty($hostId) || !is_array($params) ) return false;
    global $server;
    return $server->updTimer($sceneId, $params, $delay, $hostId);
}
//删除动态闹钟事件
// sceneId	string 	必须 		场景ID＝闹钟ID
// hostId 	string 	默认HOSTID 	在某个服务器上删除。ALL在所有服务器上删除
function delTimer( $sceneId, $hostId=null )
{
    $sceneId = trim($sceneId);
    $hostId = is_null($hostId) ? HOSTID : trim($hostId);
    if ( empty($sceneId) || empty($hostId) ) return false;
    global $server;
    return $server->delTimer($sceneId, $hostId);
}
//获取用户数据
// uid		int 	必须 		用户UID
// return 	mix 	false|array('uid'=>111,...)
function getUser( $uid )
{
    $uid = intval($uid);
    if ( $uid < 1 ) return false;
    global $server;
    return $server->getUser($uid);
}

function setUser( $uid, $data )
{
    echo "setUser beg \n";
    $uid = intval($uid);
    if ( $uid < 1 || !is_array($data) || !$data ) return false;
    global $server;
    return $server->setUser($uid, $data);
}