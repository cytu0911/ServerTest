<?php

class Server
{
    public $ss = null;
    public $sc = null;
    public $wid = 0;
    public $pid = 0;
    public $hid = HOSTID;
    public $ver = SWOOLE_VERSION;
    public $shm = null;
    public $redis = null;
    public $mysql = null;
    public $gamer = null;
    public $root = "";
    public $tag = "";
    public $scfd= array();
    public $fdi = array();
    public $desc = "";
    public $sctimerId = 0;
    private $srv_hosts = "srv_hosts";   //host数组(redis-hash结构)
    private $srv_locks = "srv_locks";   //lock数组串行化，存放上次fixLock()时的已知锁
    private $srv_lock_ = "srv_lock_";   //.$workId: 事务锁(int)
    private $srv_bind_ = "srv_bind_";       //.$fdu: FDU数据(redis-hash结构)
    private $srv_user_ = KEY_USER_;     //.$uid: USER数据(redis-hash结构)
    private $srv_mysql = "srv_mysql";   //sql后置处理(redis-list结构)
    private $srv_route_= "srv_route_";  //.$hostId协议转发用队列(redis-list结构/POP后执行)
    private $srv_event_= "srv_event_";  //.$hostId一次性事件任务(redis-list结构/POP后执行)
    private $srv_timer_= "srv_timer_";  //.$hostId重复性定时任务(redis-hash结构/执行前销毁)

    function __construct()
    {
        global $root;
        $this->root = $root;
    }

    public function start()
    {
        $this->ss = new swoole_server("127.0.0.1", PORT+10, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->ss->set(array(
            //'daemonize' => 1,         //启用进程守护
            'timeout' => 3,             //设置select/epoll溢出时间
            'open_cpu_affinity' => 1,   //启用CPU亲和设置
            'open_tcp_nodelay' => 1,    //启用TCP即时发送
            'socket_buffer_size' => 2400000,
            'enable_unsafe_event' => 1,
            'max_conn' => SW_CONNECT,       //最大连接数
            'backlog' => SW_BACKLOG,        //最大排队数
            'worker_num' => SW_WORKER,      //工作进程数
            'task_worker_num' => SW_TASKER, //任务进程数
            'max_request' => SW_REQUEST,    //进程回收数
            'dispatch_mode' => SW_DISPATCH, //FD分配模式
            'log_file' => SW_LOG,
            // 'heartbeat_check_interval' => SW_INTERVAL,
            // 'heartbeat_idle_time' => SW_IDLETIME,
            // 'open_eof_check' => 0,       //关闭
            // 'package_eof' => "\r\n\r\n",
            'open_length_check' => 1,   //开启
            'package_length_type' => 'N',
            'package_length_offset' => 0,
            'package_body_offset' => 4,
            'package_max_length' => 2000000,
        ));
        $this->ss->on('Start',          array($this, 'onServerStart'));
        $this->ss->on('Shutdown',       array($this, 'onServerClose'));
        $this->ss->on('ManagerStart',   array($this, 'onManageStart'));
        $this->ss->on('ManagerStop',    array($this, 'onManageClose'));
        $this->ss->on('WorkerStart',    array($this, 'onWorkerStart'));
        $this->ss->on('WorkerStop',     array($this, 'onWorkerClose'));
        $this->ss->on('WorkerError',    array($this, 'onWorkerError'));
        $this->ss->on('Timer',          array($this, 'onTimeRun'));
        $this->ss->on('Task',           array($this, 'onTaskRun'));
        $this->ss->on('Finish',         array($this, 'onTaskEnd'));
        $this->ss->on('Connect',        array($this, 'onFdStart'));
        $this->ss->on('Close',          array($this, 'onFdClose'));
        $this->ss->on('Receive',        array($this, 'onReceive'));
        $this->ss->start();
    }

    // ON 服务进程启动成功
    public function onServerStart( $ss )
    {
        $pid = getmypid();
        $f = $this->root.'/master.pid';
        touch($f);
        $h = fopen($f,'a');
        fwrite($h, $pid."\n");
        fclose($h);
        $pid = sprintf("%1$05d", $pid);
        slog("[SERVER] W=000 P=$pid H=$this->hid 内核服务进程启动:$this->ver");
        require($this->root."/lib/class.redis.php");
        $this->redis = $this->getRedis();
        $master = $this->getMaster();
        $this->redis->hset($this->srv_hosts, $this->hid, array("start"=>microtime(1),"master"=>intval(!$master),"ver"=>$this->ver));
        $this->redis->close();
    }

    // ON 服务进程开始关闭 (平滑关闭:kill -15)
    public function onServerClose( $ss )
    {
        $f = $this->root.'/master.pid';
        file_put_contents($f, '');
        $pid = sprintf("%1$05d", getmypid());
        slog("[SERVER] W=000 P=$pid H=$this->hid 内核服务进程关闭:$this->ver");
        require($this->root."/lib/class.redis.php");
        $this->redis = $this->getRedis();
        $this->redis->hdel($this->srv_hosts, $this->hid);
        $this->redis->close();
    }

    // ON 管理进程启动成功
    public function onManageStart( $ss )
    {
        $pid = sprintf("%1$05d", getmypid());
        slog("[SERVER] W=000 P=$pid H=$this->hid 内核管理进程启动");
    }

    // ON 管理进程开始关闭
    public function onManageClose( $ss )
    {
        $pid = sprintf("%1$05d", getmypid());
        slog("[SERVER] W=000 P=$pid H=$this->hid 内核管理进程关闭");
    }

    // ON 事件进程启动成功
    public function onWorkerStart( $ss, $wid )
    {
        $this->ss = $ss;
        $hid = $this->hid;
        $wid = $this->wid = sprintf("%1$03d", $ss->worker_id);
        $pid = $this->pid = sprintf("%1$05d", $ss->worker_pid);
        $tag = $this->tag = $wid < SW_WORKER ? "协议" : "任务";
        slog("[SERVER] W=$wid P=$pid H=$hid 内核{$tag}进程启动");
        $this->connServer($hid, $wid, $pid);
        require($this->root."/lib/class.mysql.php");
        require($this->root."/lib/class.redis.php");
        require($this->root."/game/class.gamer.php");
        $this->redis = $this->getRedis(1);
        $this->mysql = $this->getMysql(1);
        $this->gamer = new gamer($this->redis, $this->mysql);
        $this->act_login = explode(',', ACT_LOGIN);
        foreach ($this->act_login as $k => $v)
        {
            $v_ = explode(':', $v);
            $this->act_login[$v_[0]] = $v_[1];
            unset($this->act_login[$k]);
        }
        $this->act_logout = explode(',', ACT_LOGOUT);
        foreach ( $this->act_logout as $k => $v )
        {
            $v_ = explode(':', $v);
            $this->act_logout[$v_[0]] = $v_[1];
            unset($this->act_logout[$k]);
        }
        $this->act_heart = ACT_HEART;
    }

    // ON 事件进程开始关闭
    public function onWorkerClose( $ss, $wid )
    {
        // $this->sc->close();
        // foreach ( $this->scfd as $fd => $mtime )
        // {
        //  $ss->close($fd);
        // }
        slog("[SERVER] W=$this->wid H=$this->hid 内核{$this->tag}进程关闭");
    }

    // ON 事件进程运行异常
    public function onWorkerError( $ss, $wid, $pid, $errno ) 
    {
        serr("[SERVER] W=$this->wid H=$this->hid 内核{$this->tag}进程异常:$errno");
    }

    // ON 异步定时开始执行
    public function onTimeRun( $ss, $interval )
    {
        return true;
    }

    // ON 异步任务开始执行
    public function onTaskRun( $ss, $taskid, $wid, $data )
    {
        return true;
    }

    // ON 异步任务执行完毕
    public function onTaskEnd( $ss, $taskid, $data )
    {
        return true;
    }

    // ON 事件进程用户连接
    public function onFdStart( $ss, $fd, $rid )
    {
        glog("[SERVER] W=$this->wid F={$this->hid}_{$fd} 内核连接成功");
    }

    // ON 事件进程用户断开
    public function onFdClose( $ss, $fd, $rid )
    {
        glog("[SERVER] W=$this->wid F={$this->hid}_{$fd} 内核连接断开");
    }

    // ON 事件进程接收数据
    public function onReceive( $ss, $fd, $rid, $data )
    {
        // // 调试代码，务必保留！！！
        // slog("[!!!!!!] W=$this->wid H=$this->hid line=".__LINE__." onReceive=".serialize($data));
        $sttime = microtime(1);
        $hid = $this->hid;
        $wid = $this->wid;
        $pid = $this->pid;
        $fdx = "{$hid}_{$fd}";
        $info = $ss->connection_info($fd);
        if ( !$info || !isset($info['remote_ip']) ) {
            glog("[--<<<<][?????] F=$fdx info=".json_encode($info));
            return false;
        }
        // $len = UInt32Binary2Int(array_values(unpack("C*",substr($data, 0, 4))));
        $game_id = UInt32Binary2Int(array_values(unpack("C*",substr($data, 4, 4))));
        $cmd = UInt32Binary2Int(array_values(unpack("C*",substr($data, 8, 4))));//cmd
        $cme = UInt32Binary2Int(array_values(unpack("C*",substr($data, 12, 4))));//本机中转指令标记//原sendID
        $cmf = UInt32Binary2Int(array_values(unpack("C*",substr($data, 16, 4))));//原recvID
        $data_= substr($data, 20);
        $data = json_decode($data_, 1);
        if ( !is_array($data) ) {
            glog("[--<<<<][?????] F=$fdx data=".$data_);
            return false;
        }
        
        //CME=1 内网心跳
        if ( $cme == 1 ) {
            slog("[--><--][D$cmd E$cme] F=$fdx W=$this->wid");
            return true;
        }
        
        //CME=2 内网连接
        if ( $cme == 2 ) {
            $this->scfd[$fd] = microtime(1);
            slog("[->><<-][D$cmd E$cme] F=$fdx W=$this->wid");
            return true;
        }
        
        // 执行Mysql语句
        if($cme == 4) {
            $this->gamer->runSql($game_id, $data['sql']);
            glog("[runSql] game_id=$game_id data=$data_");
            return true;
        }
        
        //CME=7 执行事件
        if ( $cme == 7 ) {
            if ( isset($data['isOnly']) && $data['isOnly'] ) {
                $master = $this->getMaster();
                if ( $master != $this->hid ) return true;
            }
            glog("[<<<<<<][CRONT] data=$data_");
            $act = $data['act'];
            $res = $this->gamer->runCront($data['game_id'], $act);
            $ustime = number_format(microtime(1) - $sttime, 3);
            if ( $ustime > ROE_CRONT ) {
                gerr("[++++++][CRONT] time=$ustime data=$data_");
            }
            return $res;
        }
        //CME=8 执行事件
        if ( $cme == 8 ) {
            if ( isset($data['isOnly']) && $data['isOnly'] ) {
                $master = $this->getMaster();
                if ( $master != $this->hid ) return true;
            }
            $act = $data['act'];
            $params = $data['params'];
            $lockId = 0;
            if ( isset($data['lockId']) && $data['lockId'] ) {
                $lockId = $this->srv_lock_.$data['lockId'];
                $res = $this->redis->setLock($lockId);
                if ( !$res ) {
                    gerr("[LOCKON] lockid=$lockId params=".json_encode($params));
                    return false;
                }
            }
            glog("[<<<<<<][EVENT] data=$data_");
            $res = $this->gamer->runEvent($game_id, $act, $params);
            if ( $lockId ) {
                $res2= $this->redis->delLock($lockId);
            }
            $ustime = number_format(microtime(1) - $sttime, 3);
            if ( $ustime > ROE_EVENT ) {
                gerr("[++++++][EVENT] time=$ustime data=$data_");
            }
            return $res;
        }
        //CME=9 断开用户
        if ( $cme == 9 ) {
            $ufd = (isset($data['fd']) && $data['fd']) ? intval($data['fd']) : 0;
            if ( !$ufd ) {
                gerr("[-<<<<<][CLOSE] data=$data_");
                return false;
            }
            $fdu = "{$hid}_{$ufd}";
            glog("[<<<<<<][CLOSE] F=$fdu");
            $lockId2 = $this->srv_lock_.'LOGINOUT'.$fdu;
            $res = $this->setLock($lockId2);
            if ( !$res ) {
                gerr("[LOCKLL][CLOSE] F=$fdu lockId2=$lockId2 data=$data_");
            }
            $user = $this->getUserByFd($fdu);
            $lockId = $user && isset($user['uid']) && intval($user['uid']) ? ($this->srv_lock_.'USER_'.intval($user['uid'])) : '';
            if ( $lockId ) {
                $res = $this->setLock($lockId);
                if ( !$res ) {
                    gerr("[LOCKUU][CLOSE] F=$fdu lockId=$lockId data=$data_");
                }
            }
            $res = $this->delBind($fdu);
            $res = $this->gamer->runClose($fdu, $user);
            $lockId && $this->delLock($lockId);
            $res = $this->delLock($lockId2);
            $ustime = number_format(microtime(1) - $sttime, 3);
            if ( $ustime > ROE_CLOSE ) {
                gerr("[++++++][CLOSE] F=$fdu time=$ustime");
            }
            return $res;
        }
        //cme>0 无效指令
        if ( $cme  > 0 ) {
            gerr("[-<<<<<][$cmd????] cme=$cme data=$data_");
            return false;
        }
        //!$ufd 无效用户
        if ( !isset($data['fd']) || !$data['fd'] ) {
            gerr("[-<<<<<][$cmd????] data=$data_");
            return false;
        }
        $ufd = intval($data['fd']); unset($data['fd']);
        $fdu = "{$hid}_{$ufd}";
        if ( !$ufd ) {
            gerr("[-<<<<<][$cmd????] F=$fdu cme=$cme data=$data_");
            return false;
        }
        //cme=0 执行协议(默认)
        $code = $data['t'];
        $ccn = sprintf('%d-%d-%d', $game_id, $cmd, $code);
        glog("[<<<<<<][$ccn] F=$fdu data=$data_");
        //基础协议
        if ($game_id == 1 && $cmd == 1) // 大厅的基础协议
        {
            $lockLL = $this->srv_lock_.'LOGINOUT_'.$fdu;
            $res = $this->setLock($lockLL);
            if ( !$res ) {
                gerr("[LOCKLL][$ccn] F=$fdu lockLL=$lockLL data=$data_");
            }
            //用户登录
            if ( isset($this->act_login[$code]) )
            {
                $uid = $this->gamer->runLogin($fdu, $cmd, $code, $data);
                $uid = $uid > 0 ? intval($uid) : 0;
                if ( $uid ) {
                    $res = $this->setBind($fdu, array('uid'=>$uid));
                    $res = $this->setUser($uid, array('fd'=>$fdu, 'last_action'=>$this->act_login[$code], 'last_time'=>$sttime));
                }
            }
            //用户登出
            elseif ( isset($this->act_logout[$code]) )
            {
                $uid = 0;
                $user = $this->getUserByFd($fdu);
                if ( $user ) {
                    $uid = $user['uid'] = $user['uid'] > 0 ? intval($user['uid']) : 0;
                    if ( $uid ) {
                        $user['last_action'] = $newU['last_action'] = $this->act_logout[$code];
                        $user['last_time']   = $newU['last_time']   = $sttime;
                        $res = $this->setUser($uid, $newU);
                    } else {
                        $user = array();
                    }
                }
                $lockUU = $uid ? ($this->srv_lock_.'USER_'.$uid) : '';
                if ( $lockUU ) {
                    $res = $this->setLock($lockUU);
                    if ( !$res ) {
                        gerr("[LOCKUU][CLOSE] F=$fdu lockUU=$lockUU data=$data_");
                    }
                }
                $res = $this->delBind($fdu);
                $res = $this->gamer->runClose($fdu, $user, 1);
                $lockUU && $this->delLock($lockUU);
            }
            $lockLL && $this->delLock($lockLL);
        }
        //其他协议
        else
        {
            $reqs = include ROOT."/conf/action.php";
            $act = isset($reqs[$game_id][$cmd][$code]) ? $reqs[$game_id][$cmd][$code] : array();
            if ( !isset($act['act']) || empty($act['act']) ) return $this->closeToFd($fdu, "无效协议编号[$ccn] data=$data_");
            $action = $act['act'];
            $uid = 0;
            $user = array();
            if ( isset($act['user']) && $act['user'] > 0 ) {
                $user = $this->getUserByFd($fdu);
                if ( !$user || !($user['uid'] > 0) || !$this->gamer->runCheck($user) ) return $this->closeToFd($fdu, "无效协议用户[$ccn] data=$data_ user=".json_encode($user));
                $uid = $user['uid'] = intval($user['uid']);
            }
            $lockUU = $uid && isset($act['locku']) && $act['locku'] ? ($this->srv_lock_.'USER_'.$uid) : '';
            if ( $lockUU ) {
                $res = $this->setLock($lockUU);
                if ( !$res ) {
                    gerr("[LOCKUU][$ccn] F=$fdu lockUU=$lockUU data=$data_");
                }
            }
            $lockCC = $uid && isset($act['lock']) && $act['lock'] && isset($user[$act['lock']]) && $user[$act['lock']] ? ($this->srv_lock_.$act['lock'].$user[$act['lock']]) : '';
            if ( $lockCC ) {
                $res = $this->setLock($lockCC);
                if ( !$res ) {
                    gerr("[LOCKCC][$ccn] F=$fdu lockCC=$lockCC data=$data_");
                }
            }
            $res = $this->gamer->runAction($fdu, $game_id, $cmd, $code, $action, $data, $user);
            if ( $uid ) {
                $newU = array('last_action'=>$action, 'last_time'=>$sttime);
                $res = $this->setUser($uid, $newU);
            }
            $lockCC && $this->delLock($lockCC);
            $lockUU && $this->delLock($lockUU);
        }
        $ustime = number_format(microtime(1) - $sttime, 3);
        if ( $ustime > ROE_ACTION ) {
            gerr("[++++++][$ccn] F=$fdu time=$ustime data=$data_");
        }
        return true;
    }

    // REDIS 获取Redis对象
    public function getRedis( $isKeep=false, $isThrow=false )
    {
        if ( !is_null($this->redis) ) return $this->redis;
        $this->redis = new RD($isKeep, $isThrow);
        if ( $this->redis ) return $this->redis;
        serr("[REDIS] new failed.");
        return null;
    }
    // MYSQL 获取MySQL对象
    public function getMysql( $isKeep=false, $isThrow=false )
    {
        if ( !is_null($this->mysql) ) return $this->mysql;
        $this->mysql = new DB(MY_HOST, MY_PORT, MY_USER, MY_PASS, MY_BASE, MY_CHAR, $isKeep, $isThrow);
        if ( $this->mysql ) return $this->mysql;
        serr("[MYSQL] new failed.");
        return null;
    }
    // HOSTS 获取主服务器的HOSTID
    public function getMaster()
    {
        $hosts = $this->redis->hgetall($this->srv_hosts);
        if ( !$hosts || !is_array($hosts) ) return false;
        $master = 0;
        foreach ( $hosts as $k => $v )
        {
            if ( isset($v['master']) && $v['master'] ) return $k;
            if ( $master === 0 ) {
                $master = $k;
            }
        }
        $host = $hosts[$master];
        $host['master'] = 1;
        $res = $this->redis->hset($this->srv_hosts, $master, $host);
        return $master;
    }
    // HOSTS 获取所有HOST_PORT的进程数据
    public function getHosts()
    {
        return $this->redis->hgetall($this->srv_hosts);
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
    // LOCK 修复事务锁
    public function fixLock( $pattern )
    {
        return $this->redis->fixLock($pattern, $this->srv_locks);
    }
    // 异步SQL sql入队
    // sql = ['game_id' => $game_id, 'sql' => $sql]);
    public function bobSql($sql)
    {
        return $this->redis->ladd($this->srv_mysql, $sql);
    }
    // FD绑定 获取
    public function getBind( $fdu )
    {
        return $this->redis->hgetall($this->srv_bind_.$fdu);
    }
    // FD绑定 设置
    public function setBind( $fdu, $data )
    {
        return $this->redis->hmset($this->srv_bind_.$fdu, $data);
    }
    // FD绑定 删除
    public function delBind( $fdu )
    {
        return $this->redis->del($this->srv_bind_.$fdu);
    }
    // 用户数据 通过UID获取
    public function getUser( $uid )
    {
        return $this->redis->hgetall($this->srv_user_.$uid);
    }
    // 用户数据 设置
    public function setUser( $uid, $data )
    {
        // TODO: 为什么data里没有uid?
        $data['uid'] = $uid;
        return $this->redis->hmset($this->srv_user_.$uid, $data);
    }
    // 用户数据 增减
    public function incUser( $uid, $key, $num )
    {
        return $this->redis->hincrby($this->srv_user_.$uid, $key, $num);
    }
    // 用户数据 删除
    public function delUser( $uid )
    {
        return $this->redis->del($this->srv_user_.$uid);
    }
    // 用户数据 通过FDU获取
    public function getUserByFd( $fdu )
    {
        $fdu = trim($fdu);
        if ( empty($fdu) ) return false;
        $info = $this->getBind($fdu);
        if ( !$info || !is_array($info) || !isset($info['uid']) ) return false;
        return $this->getUser($info['uid']);
    }

    private function connServer( $hid, $wid, $pid )
    {
        $this->sc = null;
        if ( $this->sctimerId ) {
            swoole_timer_clear($this->sctimerId);
        }
        $this->sctimerId = 0;
        $this->sc = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_ASYNC, "SERVER_{$wid}");
        $this->sc->set(array(
            'open_tcp_nodelay'      => 1,
            'socket_buffer_size'    => 2400000,
            'open_length_check'     => 1,
            'package_length_type'   => 'N',
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            'package_max_length'    => 2000000,
        ));
        $this->sc->on("connect", function( $cli ) use ( $hid, $wid, $pid ) {
            $mtime = microtime(1);
            slog("[SRVCLI] W=$wid P=$pid H=$hid 内核客端连接 $mtime");
            $this->sendServer(0, 0, 2, 0, array('CLIENT'=>$mtime));
            $sctick = function($timerId){
                $this->sctimerId = $timerId;
                $this->sendServer(0, 0, 1, 0, array());
            };
            // // 测试代码
            // swoole_timer_tick(10000, $sctick);
            swoole_timer_tick(100000, $sctick);
        });
        $this->sc->on("receive", function( $cli, $data ) use ( $hid, $wid, $pid ) {
            return true;
        });
        $this->sc->on("close", function( $cli ) use ( $hid, $wid, $pid ) {
            slog("[SRVCLI] W=$wid P=$pid H=$hid 内核客端重连");
            $this->sc->connect('127.0.0.1', PORT, 0.2);
            usleep(250000);
        });
        $this->sc->on("error", function( $cli ) use ( $hid, $wid, $pid ) {
            serr("[SRVCLI] W=$wid P=$pid H=$hid 内核客端错误");
        });
        $this->sc->connect('127.0.0.1', PORT, 0.2);
        usleep(250000);
    }

    // 发消息给外壳
    private function sendServer($game_id, $cmd, $cme, $cmf, $data=array() )
    {
        $msg = is_array($data) ? json_encode($data) : $data;
        $len = num2UInt32Str(strlen($msg) + 16);
        $game_id = num2UInt32Str($game_id);
        $cmd = num2UInt32Str(intval($cmd));
        $cme = num2UInt32Str(intval($cme));
        $cmf = num2UInt32Str(intval($cmf));
        $pack = $len.$game_id.$cmd.$cme.$cmf.$msg;
        $res = $this->sc->send($pack);
        if ( !$res ) {
            // // 调试代码，务必保留！！！
            // serr("[444444] W=$this->wid H=$this->hid line=".__LINE__." sendServer=".serialize($pack));
            $this->connServer($this->hid, $this->wid, $this->pid);
            $res = $this->sc->send($pack);
            // if ( !$res ) {
            //  // 调试代码，务必保留！！！
            //  serr("[333333] W=$this->wid H=$this->hid line=".__LINE__." sendServer=".serialize($pack));
            // } else {
            //  // 调试代码，务必保留！！！
            //  slog("[444444] W=$this->wid H=$this->hid line=".__LINE__." sendServer=".serialize($pack));
            // }
            return $res;
        }
        // // 调试代码，务必保留！！！ 待续
        // slog("[!!!!!!] W=$this->wid H=$this->hid line=".__LINE__." sendServer=".serialize($pack));
        return $res;
    }
    // SEND 发送全服/部分广播
    public function sendHorn($game_id, $msg, $level=0, $fdxs=array() )
    {
        $msg = trim($msg);
        if ( !$msg ) return false;
        $level = intval($level);
        $fdxs = is_array($fdxs) ? $fdxs : array();
        $hosts = $this->getHosts();
        if ( !$hosts ) return false;
        $cmd = intval(ACT_HORN / 10000);
        $code = ACT_HORN % 10000;
        if ( $fdxs ) {
            $fdss = array();
            foreach ( $fdxs as $k => $v )
            {
                $v_ = explode('_', $v);
                $fdss[$v_[0].'_'.$v_[1]][]=$v_[2];
            }
            foreach ( $fdss as $hostId => $fds )
            {
                $route = array('act'=>"HORN", 'fds'=>$fds, 'game_id' => $game_id, 'cmd'=>$cmd, 'code'=>$code, 'data'=>array('msg'=>$msg, 'level'=>$level));
                $this->setRoute($route, $hostId);
            }
        } else {
            $route = array('act'=>"HORN", 'fds'=>$fdxs, 'game_id' => $game_id, 'cmd'=>$cmd, 'code'=>$code, 'data'=>array('msg'=>$msg, 'level'=>$level));
            foreach ( $hosts as $hostId=>$v )
            {
                $this->setRoute($route, $hostId);
            }
        }
        return true;
    }
    // SEND 发送数据到FD
    public function sendToFd( $fdx, $game_id, $cmd, $code, $info )
    {
        $fd_ = explode("_", $fdx);
        if ( !$fd_ || !is_array($fd_) || count($fd_) !== 3 || !($ufd = intval($fd_[2])) ) return false;
        $hostId = $fd_[0]."_".$fd_[1];
        if ( $hostId == $this->hid ) {
            $cme = 0;
            $data = array('fd'=>$ufd, 'code'=>$code, 'data'=>$info);
            $res = $this->sendServer($game_id, $cmd, $cme, 0, $data);
            if ( !$res ) {
                gerr("[>-----] F=$fdx D=$cmd E=$cme data=".json_encode($data));
            }
            return $res;
        }
        $route = array('act'=>"SEND", 'fd'=>$fdx, 'game_id' => $game_id, 'cmd'=>$cmd, 'code'=>$code, 'data'=>$info);
        return $this->setRoute($route, $hostId);
    }
    // CLOSE 关闭FD连接
    public function closeToFd( $fdx, $desc='' )
    {
        $fd_ = explode("_", $fdx);
        if ( !$fd_ || !is_array($fd_) || count($fd_) !== 3 || !($ufd = intval($fd_[2])) ) return false;
        $desc = is_array($desc) && $desc ? json_encode($desc) : ( $desc ? $desc : '' );
        $hostId = $fd_[0]."_".$fd_[1];
        if ( $hostId == $this->hid ) {
            $cmd = 0;
            $cme = 9;
            $data = array('fd'=>$ufd, 'desc'=>$desc);
            $res = $this->sendServer($cmd, $cme, 0, $data);
            if ( !$res ) {
                gerr("[>-----] F=$fdx D=$cmd E=$cme data=".json_encode($data));
            }
            return $res;
        }
        $route = array("act"=>"KICK", "fd"=>$fdx, "desc"=>$desc);
        return $this->setRoute($route, $hostId);
    }

    // ROUTE 追加跨服转发事件
    public function setRoute($data, $hostId=null )
    {
        if ( !$data ) return false;
        if ( is_null($hostId) ) {
            if ( isset($data['fd']) && $data['fd'] ) {
                $fd_ = explode("_", $data['fd']);
                if ( !$fd_ || !is_array($fd_) || count($fd_) !== 3 || !($ufd = intval($fd_[2])) ) return false;
                $hostId = $fd_[0]."_".$fd_[1];
            } else {
                $hostId = $this->hid;
            }
        }
        return $this->redis->ladd($this->srv_route_.$hostId, $data);
    }

    // EVENT 设置一个常规或者延时事件
    public function setEvent($game_id, $act, $params=array(), $delay=0, $hostId=HOSTID )
    {
        $act = trim($act);
        if ( !$act ) return false;
        $delay = intval($delay);
        if ( $delay < 0 ) {
            $delay = 0;
        }
        $event = array('act'=>$act, 'game_id' => $game_id, 'params'=>$params, 'delay'=>$delay, 'mtime'=>microtime(1));
        if ( $hostId == "ALL" ) {
            $hosts = $this->getHosts();
            if ( !$hosts ) return false;
            foreach ( $hosts as $hostId => $v )
            {
                $this->redis->ladd($this->srv_event_.$hostId, $event);
            }
            return true;
        }
        return $this->redis->ladd($this->srv_event_.$hostId, $event);
    }

    // TIMER 设置一个场景轮次定时器事件
    public function setTimer($game_id, $sceneId, $act, $params=array(), $delay=0, $hostId=HOSTID )
    {
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
        if ( $hostId == "ALL" ) {
            $hosts = $this->getHosts();
            if ( !$hosts ) return false;
            $lockId = $this->srv_lock_."SCENE".$sceneId;
            $res = $this->redis->setLock($lockId);
            if ( !$res ) {
                gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId event=".json_encode($event));
                return false;
            }
            foreach ( $hosts as $hostId => $v )
            {
                $this->redis->hset($this->srv_timer_.$hostId, $sceneId, $event);
            }
            $res = $this->redis->delLock($lockId);
            return true;
        }
        $lockId = $this->srv_lock_."SCENE".$sceneId;
        $res = $this->redis->setLock($lockId);
        if ( !$res ) {
            gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId event=".json_encode($event));
            return false;
        }
        $res = $this->redis->hset($this->srv_timer_.$hostId, $sceneId, $event);
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
            gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId delay=$delay params=".json_encode($params));
            return false;
        }
        $event = $this->redis->hget($this->srv_timer_.$hostId, $sceneId);
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
        $res = $this->redis->hset($this->srv_timer_.$hostId, $sceneId, $event);
        $res2= $this->redis->delLock($lockId);
        return $res;
    }

    // TIMER 删除一个场景轮次定时器事件
    public function delTimer( $sceneId, $hostId )
    {
        if ( $hostId == "ALL" ) {
            $hosts = $this->getHosts();
            if ( !$hosts ) return false;
            $lockId = $this->srv_lock_."SCENE".$sceneId;
            $res = $this->redis->setLock($lockId);
            if ( !$res ) {
                gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId hostid=".$hostId);
                return false;
            }
            foreach ( $hosts as $hostId => $v )
            {
                $this->redis->hdel($this->srv_timer_.$hostId, $sceneId);
            }
            $res = $this->redis->delLock($lockId);
            return true;
        }
        $lockId = $this->srv_lock_."SCENE".$sceneId;
        $res = $this->redis->setLock($lockId);
        if ( !$res ) {
            gerr("[LOCKON] W=$this->wid H=$this->hid lockid=$lockId hostid=".$hostId);
            return false;
        }
        $res = $this->redis->hdel($this->srv_timer_.$hostId, $sceneId);
        $res2= $this->redis->delLock($lockId);
        return $res;
    }

}
//获取本机标示
function getHostId()
{
    return HOSTID;
}
//获取主服务器标示
function getMaster()
{
    global $sweety;
    return $sweety->getMaster();
}
//获取所有服务器关联数据
function getHosts()
{
    global $sweety;
    return $sweety->getHosts();
}
//获取一个REDIS连接对象
function getRedis()
{
    global $sweety;
    return $sweety->getRedis(1);
}
//获取一个MYSQL连接对象
function getMysql()
{
    global $sweety;
    return $sweety->getMysql(1);
}
//设置一个并发锁
    // lockId   string  必须      并发锁ID
    // isOnly   num     默认0         0忙等锁1互斥锁
    // return   bool    false|true
function setLock( $lockId, $isOnly=0 )
{
    $lockId = trim($lockId);
    if ( empty($lockId) ) return false;
    global $sweety;
    return $sweety->setLock($lockId, intval(!!$isOnly));
}
//销毁一个并发锁
    // lockId   string  必须      并发锁ID
    // return   bool    false|true
function delLock( $lockId )
{
    $lockId = trim($lockId);
    if ( empty($lockId) ) return false;
    global $sweety;
    return $sweety->delLock($lockId);
}
//检查修复某种指定的并发锁，系统底层有并发锁自动修复功能，除非特殊情况不建议使用
    // pattern  string  必须      redis锁的key的匹配表达式
    // return   bool    false|true
function fixLock( $pattern )
{
    $pattern = trim($pattern);
    if ( empty($pattern) ) return false;
    global $sweety;
    return $sweety->fixLock($pattern);
}
//追加一条MySQL异步操作
    // sql      string  必须      MySQL查询字串
    // return   bool    false|true
function bobSql($sql )
{
    $sql['sql'] = trim($sql['sql']);
    if ( empty($sql) ) return false;
    global $sweety;
    return $sweety->bobSql($sql);
}
//获取连接绑定
    // fdu      string  必须      连接标示
    // return   mix     false|array('uid'=>111,...)
function getBind( $fdu )
{
    $fdu = trim($fdu);
    if ( empty($fdu) || !$fdu ) return false;
    global $sweety;
    return $sweety->getBind($fdu);
}
//设置连接绑定
    // fdu      string  必须      连接标示
    // data     array   必须      连接数据
    // return   bool    false|true
function setBind( $fdu, $data )
{
    $fdu = trim($fdu);
    if ( empty($fdu) || !$fdu || !is_array($data) || !$data ) return false;
    global $sweety;
    return $sweety->setBind($fdu, $data);
}
//删除连接绑定
    // fdu      string  必须      连接标示
    // return   bool    false|true
function delBind( $fdu )
{
    $fdu = trim($fdu);
    if ( empty($fdu) || !$fdu ) return false;
    global $sweety;
    return $sweety->delBind($fdu);
}
//获取用户数据
    // uid      int     必须      用户UID
    // return   mix     false|array('uid'=>111,...)
function getUser( $uid )
{
    $uid = intval($uid);
    if ( $uid < 1 ) return false;
    global $sweety;
    return $sweety->getUser($uid);
}
//设置用户数据
    // uid      int     必须      用户UID
    // data     array   必须      用户数据
    // return   bool    false|true
function setUser( $uid, $data )
{
    $uid = intval($uid);
    if ( $uid < 1 || !is_array($data) || !$data ) return false;
    global $sweety;
    return $sweety->setUser($uid, $data);
}
//增减用户属性
    // uid      int     必须      用户UID
    // key      string  必须      用户属性
    // num      numeric 必须      增减数值
    // return   numeric 增减后的新数值
function incUser( $uid, $key, $num )
{
    $uid = intval($uid);
    $key = trim($key);
    $num += 0;
    if ( $uid < 1 || empty($key) ) return false;
    global $sweety;
    return $sweety->incUser($uid, $key, $num);
}
//删除用户数据
    // uid      int     必须      用户UID
    // return   bool    false|true
function delUser( $uid )
{
    $uid = intval($uid);
    if ( $uid < 1 ) return false;
    global $sweety;
    return $sweety->delUser($uid);
}
//通过FDU获取用户数据
    // fdu      string  必须      连接标示
function getUserByFd( $fdu )
{
    $fdu = trim($fdu);
    if ( empty($fdu) || !$fdu ) return false;
    global $sweety;
    return $sweety->getUserByFd($fdu);
}
//发送全服广播
    // msg      string  必须      广播的文本内容
    // level    int     默认0         广播优先级
    // fdus     array   默认空数组   空数组时发送到全服；否则发送到指定的部分fdu
function sendHorn($game_id, $msg, $level=0, $fdus=array() )
{
    $msg = trim($msg);
    $level = intval($level);
    if ( empty($msg) || $level < 0 || !is_array($fdus) ) return false;
    global $sweety;
    return $sweety->sendHorn($game_id, $msg, $level, $fdus);
}
//发送数据
    // fdu      string  必须      连接标示
    // cmd      int     必须      协议族
    // code     int     必须      协议号
    // info     array   必须      数据
function sendToFd( $fdu, $game_id, $cmd, $code, $info )
{
    $fdu = trim($fdu);
    $cmd = intval($cmd);
    $code = intval($code);
    if ( empty($fdu) || !$fdu || $cmd < 0 || $code < 0 || !is_array($info) ) return false;
    global $sweety;
    return $sweety->sendTofd($fdu, $game_id, $cmd, $code, $info);
}
//断开连接
    // fdu      string  必须      连接标示。
    // hostId   string  默认空字串   描述信息，会写入日志
function closeToFd( $fdu, $desc="" )
{
    $fdu = trim($fdu);
    if ( empty($fdu) || !$fdu || !is_string($desc) ) return false;
    global $sweety;
    return $sweety->closeToFd($fdu, $desc);
}
//添加常规延迟事件
    // act      string  必须      事件执行时的方法
    // params   array   必须      事件执行时的参数
    // delay    int     默认0     延迟指定毫秒数后，开始执行事件。<0立即执行
    // hostId   string  默认HOSTID    在某个服务器上增加。ALL在所有服务器上增加
function setEvent($game_id, $act, $params, $delay=0, $hostId=null )
{
    $act = trim($act);
    $delay = intval($delay);
    $hostId = is_null($hostId) ? HOSTID : trim($hostId);
    if ( empty($hostId) || empty($act) || !is_array($params) ) return false;
    global $sweety;
    return $sweety->setEvent($game_id, $act, $params, $delay, $hostId);
}
//添加动态闹钟事件
    // sceneId  string  必须      场景ID＝闹钟ID
    // act      string  必须      事件执行时的方法
    // params   array   必须      事件执行时的参数
    // delay    int     必须          延迟指定毫秒数后，开始执行事件。<0未执行的定时器停止
    // hostId   string  默认HOSTID    在某个服务器上增加。ALL在所有服务器上增加
function setTimer($game_id, $sceneId, $act, $params, $delay, $hostId=null )
{
    $sceneId = trim($sceneId);
    $act = trim($act);
    $delay = intval($delay);
    $hostId = is_null($hostId) ? HOSTID : trim($hostId);
    if ( empty($sceneId) || empty($hostId) || empty($act) || !is_array($params) ) return false;
    global $sweety;
    return $sweety->setTimer($game_id, $sceneId, $act, $params, $delay, $hostId);
}
//校验后修改动态闹钟事件的执行时间
    // sceneId  string  必须      场景ID＝闹钟ID
    // params   array   必须          校验参数。array()不做校验，否则如果校验用项与原参数某项不符合，不执行修改
    // delay    int     必须          延迟指定毫秒数后，开始执行事件。<0未执行的定时器停止
    // hostId   string  必须      在某个服务器上的闹钟。
function updTimer( $sceneId, $params, $delay, $hostId )
{
    $sceneId = trim($sceneId);
    $delay = intval($delay);
    $hostId = trim($hostId);
    if ( empty($sceneId) || empty($hostId) || !is_array($params) ) return false;
    global $sweety;
    return $sweety->updTimer($sceneId, $params, $delay, $hostId);
}
//删除动态闹钟事件
    // sceneId  string  必须      场景ID＝闹钟ID
    // hostId   string  默认HOSTID    在某个服务器上删除。ALL在所有服务器上删除
function delTimer( $sceneId, $hostId=null )
{
    $sceneId = trim($sceneId);
    $hostId = is_null($hostId) ? HOSTID : trim($hostId);
    if ( empty($sceneId) || empty($hostId) ) return false;
    global $sweety;
    return $sweety->delTimer($sceneId, $hostId);
}
//进程重载
function srvReload()
{
    global $sweety;
    return $sweety->ss->reload();
}


