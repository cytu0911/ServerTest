<?php

class TableMgr
{
    public $redis = null;
    public $mysql = null;
    public $roomMgr = null;

    private $key_table_info_ = "key_table_info_";


    private $ini_table = array(//65
        'tableId'=>0,	//标记桌号
        'hostId'=>0,	//标记HOST
        'wid'=>false,
        'modelId'=>0,	//标记模式
        'roomId'=>0,	//标记房间
        'state'=>0,		//桌子状态
        'rate'=>0,		//牌桌倍率
        'rateMax'=>0,	//牌桌顶倍
        'rake'=>0,		//抽水倍率
        'baseCoins'=>0,	//底注基线
        'limitCoins'=>0,//赢钱上限
        'rate_'=>0,				//临时倍率 与rate、seat0rate、seat1rate、seat2rate用于叫抢地主算法
        'firstShow'=>4,			//哪个位置先明牌
        'lordSeat'=>4,			//哪个位置是地主[0|1|2]
        'turnSeat'=>4,			//哪个位置要操作[0|1|2]最先明牌的有叫地主优先权
        'lastCall'=>4,			//哪个席位叫牌/跟牌
        'lastCards'=>array(),	//叫牌/跟牌的内容
        'outCards'=>array(),	//废牌(已出的)
        'lordCards'=>array(),	//底牌(地主牌)
        'noFollow'=>0,			//记录不跟次数
        'isRegame'=>1,			//是否再来一局
        'isNewGame'=>1,			//是否是新一局
        'isStop'=>0,			//是否已经停止继续轮次
        'move'=>0,				//轮次每移动一次＋1
        'create'=>0,			//create time
        'update'=>0,			//update time
        'seats'=>array(),		//uid=>[0|1|2]
        'seat0info'=>array(),	//uid=>/sex=>/nick...
        'seat0fd'=>0,			//席位fd
        'seat0uid'=>0,			//席位uid
        'seat0coins'=>0,		//席位coins
        'seat0score'=>0,		//席位score
        'seat0show'=>0,			//席位是否名牌
        'seat0state'=>0,		//席位游戏状态
        'seat0queue'=>-1,		//席位在牌桌队列的掉线重发起点
        'seat0cards'=>array(),	//席位手牌
        'seat0rate'=>-1,		//席位叫抢地主的倍率
        'seat0robot'=>0,		//席位是否为机器人
        'seat0trust'=>0,		//席位托管状态
        'seat0delay'=>0,		//席位超时次数
        'seat0sent'=>0,			//席位出牌次数
        'seat1info'=>array(),
        'seat1fd'=>0,
        'seat1uid'=>0,
        'seat1coins'=>0,
        'seat1score'=>0,
        'seat1show'=>0,
        'seat1state'=>0,
        'seat1queue'=>-1,
        'seat1cards'=>array(),
        'seat1rate'=>-1,
        'seat1robot'=>0,
        'seat1trust'=>0,
        'seat1delay'=>0,
        'seat1sent'=>0,
        'seat2info'=>array(),
        'seat2fd'=>0,
        'seat2uid'=>0,
        'seat2coins'=>0,
        'seat2score'=>0,
        'seat2show'=>0,
        'seat2state'=>0,
        'seat2queue'=>-1,
        'seat2cards'=>array(),
        'seat2rate'=>-1,
        'seat2robot'=>0,
        'seat2trust'=>0,
        'seat2delay'=>0,
        'seat2sent'=>0,
    );

    function __construct( $rd, $mq, $rm  )
    {
        $this->redis = $rd;
        $this->mysql = $mq;
        $this->roomMgr = $rm ;
    }

    //初始化牌桌信息
    function iniTableInfo($roomId, $players, $modelId=0,$roomMock=0,$weekId=0,$gameId=0)
    {
        var_dump($players);
        $roomConf = $this->roomMgr->getRoom( $roomId );
        echo "roomConf \n";
        var_dump($roomConf);
        if ( !$roomConf || !$players || !is_array($players) || count($players) != 3 )
        {
            return false;
        }

        $table = $this->ini_table;
        $tableId = $roomId.'_'.join('_',array_keys($players));
        $res = delTimer($tableId);
        $table['modelId'] = $modelId;
        $table['roomId'] = $roomId;
        $table['roomMock'] = $roomMock;
        $table['weekId'] = $weekId;
        $table['gameId'] = $gameId;
        $table['gamesId'] = $gameId ? ($modelId.'_'.$roomMock.'_'.$weekId.'_'.$gameId) : '';
        $table['tableId'] = $tableId;
        $table['rate'] =    1;//$roomConf['rate'];
        $table['rateMax'] = 100;//$roomConf['rateMax'];
        $table['rake'] =0; //$roomConf['rake'];
        $table['baseCoins']  = 0;//$roomConf['baseCoins'];
        $table['limitCoins'] = 0; // $roomConf['limitCoins'];
        $table['lastSurprise'] = 0;
        $table['create'] = $table['update'] = microtime(1);

        foreach ( $players as $uid=>$user )
        {
            $user_ = getUser($uid);
            //var_dump($user_);

            if ( $user_ && isset($user_['fd'])  ) {   // && $user_['fd']
                $user = array_merge($user, $user_, array('isShowcard'=>false));
                $fd = $user_['fd'];

            } else {
                $user['fd'] = $fd = 0;
            }
            if ( !isset($user['wid']) ) $user['wid'] = false;
            if ( !isset($user['giveup']) ) $user['giveup'] = 0;
            if ( !isset($user['isRobot']) ) $user['isRobot'] = 0;
            if ( !isset($user['isShowcard']) ) $user['isShowcard'] = 0;
            $players[$uid] = $user;
        }
        /*if ( !$table['hostId'] ) {
            $table['hostId'] = HOSTID;
            $table['wid'] = false;
        }  */
        $i = 0;
        foreach ( $players as $uid=>$user )
        {
            $table['firstShow'] = ($table['firstShow'] == 4 && $user['isShowcard']) ? $i : $table['firstShow'];
            $table['seats'][$uid] = $i;
            $table['seat'.$i.'info'] = $user;
            $new['modelId'] = $table['seat'.$i.'info']['modelId'] = $modelId;
            $new['weekId'] = $table['seat'.$i.'info']['weekId'] = $weekId;
            $new['gameId'] = $table['seat'.$i.'info']['gameId'] = $gameId;
            $new['gamesId'] = $table['seat'.$i.'info']['gamesId'] = $table['gamesId'];
            $new['roomId'] = $table['seat'.$i.'info']['roomId'] = $roomId;
            $new['tableId'] = $table['seat'.$i.'info']['tableId'] = $tableId;
            $new['seatId'] = $table['seat'.$i.'info']['seatId'] = $i;
            $new['wid'] = $user['wid'];
            $new['twid'] = $table['wid'];
            $new['giveup'] = $user['giveup'];
            $new['lastSurprise'] = 0;
            $table['seat'.$i.'robot']= $user['isRobot'];	//用户，基础识别
            $table['seat'.$i.'uid'] = $uid;					//编号，基础识别
            $table['seat'.$i.'fd'] = $user['fd'];			//通道，需要变化
            $table['seat'.$i.'coins'] =1000;// $user['coins'];		//筹码，经常变化
            $table['seat'.$i.'score'] = 10000;		//赛币，需要变化
            $table['seat'.$i.'show'] = $user['isShowcard'];	//明牌，需要变化
            $table['seat'.$i.'giveup'] = $user['giveup'];	//弃赛，需要变化
            $table['seat'.$i.'state'] = 17;					//状态，经常变化，17:SYS开始发牌
            $res = setUser($uid, $new);
            $i++;
        }
        $table['turnSeat'] = $table['firstShow'];
        $res = $this->setTableInfo($tableId, $table);
        return $table;
    }

    //设置牌桌信息
    function setTableInfo( $tableId, $info )
    {
        if ( !$tableId || !$info || !is_array($info) ) return false;
        return $this->redis->hmset($this->key_table_info_.$tableId,$info);
    }

    //获取全部牌桌信息
    function getTableInfo( $tableId )
    {
        if ( !$tableId ) return false;
        $table = $this->redis->hgetall($this->key_table_info_.$tableId);
        if ( !$table ) {
            return false;
        } elseif ( count($table) < 66 ) {
            gerr("异常牌桌清理[$tableId] ".json_encode($table));
            $res = $this->delTableInfo($tableId);
            $res = delTimer($tableId, isset($table['hostId']) ? $table['hostId'] : 0);
            return false;
        }
        return $table;
    }

    //发送到牌桌所有用户，相同数据
    function sendToTable( $table, $cmd, $code, $data, $line=0 )
    {
        if ( !isset($table['seats']) || !$table['seats'] || !is_array($table['seats']) )
        {
            gerr("牌桌数据无效 sendToTable table=".json_encode($table));
            return false;
        }
        foreach ( $table['seats'] as $uid=>$sid ) {
            $player = array('fd'=>$table["seat{$sid}fd"], 'uid'=>$uid, 'tableId'=>$table['tableId']);
            $res = $this->sendToPlayer($player, $cmd, $code, $data,0);
        }
        return true;
    }

    //发送到牌桌某个用户，不同(相同)数据
    //is_save	当前发送的内容: 0不存历史+直接发送/1存入历史+可能缓发/2不存历史+可能缓发
    function sendToPlayer( $player, $cmd, $code, $data, $is_save=0 )
    {
        $fd = $player['fd'];//发送给牌桌用户的时候使用牌桌上的用户fd
        $uid = $player['uid'];
        $tableId = $player['tableId'];
        $data['log']['ud'] = $uid;
        $data['log']['td'] = $tableId;
        //添加到牌桌历史
       // $is_save ==1 && $this->addTableHistory( $tableId, array('uid'=>$uid,'cmd'=>$cmd,'code'=>$code,'data'=>$data) );
        //掉线用户或机器人不发
        if ( !$fd ) { return false; }
        //不用存入历史的，绿色通道，直接发送
        if ( !$is_save ) { return $this->sendToFd($fd, $cmd, $code, $data); }
        //常规消息
        $fdinfo = getBind($fd);
        //阻塞状态，增加到缓发队列
        if ( $fdinfo && isset($fdinfo['is_lock']) && $fdinfo['is_lock'] )
        {	//return $index;
            return $this->addUserQueue( $uid, array('uid'=>$uid,'cmd'=>$cmd,'code'=>$code,'data'=>$data) );
        }
        //畅通状态，优先发送缓发数据
        //预留 这个地方需要优化，目前无优化方案
        while ( $queue = $this->popUserQueue($uid) )
        {
            $this->sendToFd($fd, $queue['cmd'], $queue['code'], $queue['data']);
        }
        //正式发送
        return $this->sendToFd($fd, $cmd, $code, $data);
    }

    //发送给不一定在牌桌的用户
    function sendToUser( $uid, $cmd, $code, $data )
    {
        if ( is_array($uid) && isset($uid['fd']) && isset($uid['uid']) && isset($uid['tableId']) ) {
            $user = $uid;
            $uid = intval($user['uid']);
            if ( $uid <= 0 ) return false;
        } elseif ( $uid > 0 ) {
            $user = $this->getUserInfo($uid);
            if ( !$user ) {
                return false;
            }
        } else {
            return false;
        }
        $fd = $user['fd'];	//常规发送使用用户信息里的fd
        $tableId = $user['tableId'];
        $data['log']['ud'] = $uid;
        $data['log']['td'] = $tableId;
        return $this->sendToFd($fd, $cmd, $code, $data);
    }

    //发送给FD
    function sendToFd( $fd, $cmd, $code, $data )
    {
        //暂不支持串型桌号、整型微信码、整型靓号
        if ( isset($data['check_code']) ) $data['check_code'] = strval($data['check_code']);
        if ( isset($data['cool_num']) ) $data['cool_num'] = strval($data['cool_num']);
        if ( isset($data['tableId']) ) $data['tableId'] = 1;
        if ( isset($data['seat0info']['tableId']) ) $data['seat0info']['tableId'] = 1;
        if ( isset($data['seat1info']['tableId']) ) $data['seat1info']['tableId'] = 1;
        if ( isset($data['seat2info']['tableId']) ) $data['seat2info']['tableId'] = 1;
        if ( isset($data['0']['tableId']) ) $data['0']['tableId'] = 1;
        if ( isset($data['1']['tableId']) ) $data['1']['tableId'] = 1;
        if ( isset($data['2']['tableId']) ) $data['2']['tableId'] = 1;
        return sendToFd($fd, $cmd, $code, $data);
    }

    //删除全部牌桌信息
    function delTableInfo( $tableId )
    {
        if ( !$tableId ) return false;
        return $this->redis->del($this->key_table_info_.$tableId);
    }
    //更新桌子状态
    function setTableState( $tableId, $state )
    {
        if ( !$tableId ) return false;
        return $this->redis->hset($this->key_table_info_.$tableId,'state', $state);
    }

    //叫地主，返回最新牌桌数据
    function call_lord( $table, $doLord )
    {
        if ( !$table || !is_array($table) || !in_array($doLord,array(1,2)) ) return false;
        $GAME = include(ROOT.'/conf/games.php');
        $tableId = $table['tableId'];
        $seatId = $table['turnSeat'];
        $rate_ = $table['rate_'];
        $seat_rate = $rate_ * $GAME['rate_belord'];
        $is_want = intval($doLord == 1);//1叫抢/2不要
        $seat_rate = $seat_rate * $is_want;
        $res = $this->setSeatRate($tableId,$seatId,$seat_rate);
        if ( !$res ) return false;
        $table["seat{$seatId}rate"] = $seat_rate;
        //临时叫抢倍率变更
        if ( $seat_rate > $rate_ )
        {
            $res = $this->setTableRate_($tableId,$seat_rate);
            if ( !$res ) return false;
            $table['rate_'] = $seat_rate;
        }
        //只要有人叫，马上进入抢地主阶段
        if ( $is_want )
        {
            $state = 5;//抢地主阶段
            $res = $this->setTableState($tableId,$state);
            if ( !$res ) return false;
            $table['state'] = $state;
        }
        return $this->check_lord($table);
    }

    //抢地主，返回最新牌桌数据
    function grab_lord( $table, $doLord )
    {
        if ( !$table || !is_array($table) || !in_array($doLord,array(1,2)) ) return false;
        $GAME = include(ROOT.'/conf/games.php');
        $tableId = $table['tableId'];
        $seatId = $table['turnSeat'];
        $rate_ = $table['rate_'];//临时叫抢倍率
        $seat_rate = $rate_ * $GAME['rate_grablord'];
        $is_want = intval($doLord == 1);//1叫抢/2不要
        $seat_rate = $seat_rate * $is_want;
        $res = $this->setSeatRate($tableId,$seatId,$seat_rate);
        if ( !$res ) return false;
        $table["seat{$seatId}rate"] = $seat_rate;
        //临时叫抢倍率变更
        if ( $seat_rate > $rate_ )
        {
            $res = $this->setTableRate_($tableId,$seat_rate);
            if ( !$res ) return false;
            $table['rate_'] = $seat_rate;
        }
        return $this->check_lord($table);
    }

    //检查地主是否确定，返回最新牌桌数据
    function check_lord( $table )
    {
        if ( !$table || !is_array($table) ) return false;
        $tableId = $table['tableId'];
        $rate = $table['rate'];
        $seat_rates = array(
            'seat0rate'=>$table['seat0rate'],
            'seat1rate'=>$table['seat1rate'],
            'seat2rate'=>$table['seat2rate'],
        );
        $count = array_count_values($seat_rates);
        $new = array();
        $is_lordto = false;//是否确定地主
        //还有人没发话：轮转向下一位
        if ( isset($count['-1']) )
        {
            $seat = $this->getSeatNext($table['turnSeat']);
            $new['turnSeat'] = $seat;
        }
        //三人放弃，且无人明牌：轮转向空位，还原牌桌信息，用于重新发牌
        elseif ( isset($count['0']) && $count['0'] == 3 && $table['firstShow'] == 4 )
        {
            $seat = 4;
            $new['turnSeat'] = $seat;
            $new['state'] = 3;//发牌明牌阶段
            $new['lordCards'] = array();
            $new['seat0rate'] = $new['seat1rate'] = $new['seat2rate'] = -1;
            $new['seat0cards'] = $new['seat1cards'] = $new['seat2cards'] = array();
        }
        //三人放弃，且有人明牌：轮转向明牌的座位，确定地主，改变状态，处理牌组
        elseif ( isset($count['0']) && $count['0'] == 3 && $table['firstShow'] != 4 )
        {
            $seat = $table['firstShow'];
            $new['turnSeat'] = $seat;
            $new['lordSeat'] = $seat;
            $new['state'] = 6;//出牌过牌阶段
            $new["seat{$seat}cards"] = cardsSort2(array_merge($table["seat{$seat}cards"], $table['lordCards']));
        }
        //两人放弃，或都抢弃过：轮转最大倍率座位，确定地主，改变状态，处理牌组
        elseif ( isset($count['0']) && $count['0'] == 2  || !in_array($rate, $seat_rates))
        {
            $seat_rate = max($seat_rates);
            $seat = intval(substr(array_search($seat_rate,$seat_rates),4,1));
            $new['turnSeat'] = $seat;
            $new['lordSeat'] = $seat;
            $new['state'] = 6;//出牌过牌阶段
            $new["seat{$seat}cards"] = cardsSort2(array_merge($table["seat{$seat}cards"], $table['lordCards']));
        }
        //其他情形：轮转向下一个未放弃的座位
        else
        {
            $seat = $this->getSeatNext($table['turnSeat']);
            if ( !$table["seat{$seat}rate"] )
            {
                $seat = $this->getSeatNext($seat);
            }
            $new['turnSeat'] = $seat;
        }
        $res = $this->setTableInfo($tableId, $new);
        if ( !$res ) return false;
        return array_merge($table,$new);
    }

    //获取下一个席位
    function getSeatNext( $seatId )
    {
        if ( !in_array($seatId,range(0,2)) ) return 0;
        return --$seatId == -1 ? 2 : $seatId;
    }

    //更新座位用户抢叫地主的倍率
    function setSeatRate($tableId,$seatId,$rate)
    {
        if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
        return $this->redis->hset($this->key_table_info_.$tableId,'seat'.$seatId.'rate',$rate);
    }

    //设置牌桌叫抢临时倍率
    function setTableRate_( $tableId, $rate )
    {
        if ( !$tableId ) return false;
        return $this->redis->hset($this->key_table_info_.$tableId,'rate_',$rate);
    }

}