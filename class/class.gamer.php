<?php
/**
 * Created by PhpStorm.
 * User: youjoy8
 * Date: 2015/11/17
 * Time: 13:42
 */

require_once ROOT."/class/class.userMgr.php";
require_once ROOT."/class/class.roomMgr.php";
require_once ROOT."/class/class.model.php";
require_once ROOT."/class/class.card.php";

class gamer
{
    public $redis = null;
    public $mysql = null;
    public $userMgr = null;
    public $roomMgr = null;
    public $model  = null;

    function __construct($rd, $mq)
    {
        $this->redis = $rd;
        $this->mysql = $mq;
        $this->userMgr = new userMgr($rd,$mq);
        $this->roomMgr = new roomMrg($rd,$mq);
        $this->model = new model($rd,$mq);
    }

    public function runLogin($fd, $data)
    {
        $uid   = isset($data['u']) ? $data['u'] : '';
        $user = $this->userMgr->getUserByUid( $uid );
        if ( ! $user )
         {
             echo "用户名密码错误";
             closeToFd( $fd);
         }
         else  //用户名密码正确
         {
             $this->userMgr->initUser($user);
             $user['fd']  = $fd ;
             $this->userMgr->setFdUid($fd,$uid);
             $this->userMgr->setUserInfo($uid,$user);

            $ret = array(
                'uid'=> $uid ,
                "cool_num"=> "1576374",
                "nick"=> "\u65b0\u624b1576374",
                "sex"=> 1,
                "age"=> 0,
                "word"=> "",
                "gold"=> 0,
                "coins"=> 8889527800,
                "coupon"=> 27848,
                "lottery"=> 0,
                "level"=> 0,
                "exp"=> 0,
                "avatar"=> 0,
                "gameData" => array(
                    "win" => 0,
                    "matches"=>123
                ),
                "check_code" => "123456",
                "propDress" => array("3"=>"0","1"=>1) ,
                "score" => 1000,
                "verfile" => 0,
                "vertips" => 0,
                "mail_unread" => 0,
                "coupon" => 0,
                "contact"=> "qq123456",
                "contacts"=> "qq123456",
                "wechat"=> "adfadfad",
            );
             sendToFd($fd, 1, 10, $ret);
         }
        return $user;
        //sendToFd(1,1,null);
    }

    public function runAction( $fd, $cmd, $code, $action, $params=array(), $user=array() )
    {
        $accode = $cmd ? ($cmd * 10000 + $code) : sprintf("%1$05d", $code);
        $action = strtolower($action);
        $file = ROOT."/action/{$accode}.{$action}.php";
        echo "filename: " . $file . "\n";
        if ( !file_exists($file) ) return false;
        $param = $params;
        require $file;
    }

    public function runGame(  )
    {
        //echo "Game is runing\n";

        $MODELS = include(ROOT.'/conf/rooms.php');
        $gameConfig = $MODELS[1];

        //1 遍历每个房间，观察该房间排队人数是否达标
       // $this->checkRoomQueue( $gameConfig );

        //2 遍历每个牌桌，根据牌桌状态来执行不同操作
        //$this->traveTables( $gameConfig );
    }

    private function checkRoomQueue($gameConfig)
    {
        foreach($gameConfig as $value  )
        {
            $roomId        = isset( $value['roomReal'] ) ? $value['roomReal'] : 0 ;
            $gamePersonAll = isset( $value['gamePersonAll'] ) ? $value['gamePersonAll'] : 100;

            $gameRealPeople = $this->roomMgr->getNum($roomId);
            //echo $roomId . " real person:" .$gameRealPeople . "\n" ;
        }
    }


    private function traveTables($gameConfig)
    {

    }

    //游戏退出时完成清理及保存工作
    public function gameOver()
    {

    }

    //执行事件
    function runEvent( $act, $params=array() )
    {
        if ( method_exists($this, $act) ) return $this->$act($params);
        echo $act ."\n";
        gerr("[EVENT] ACT=$act PARAMS=".json_encode($params));
        return false;
    }

    //[???]	赛事检查人数
    function ACT_MODEL_CHECK( $game )
    {
        $cmd = 5;
        $gamesId = $game['gamesId'];
        $modelId = $game['modelId'];
        $roomId = $game['roomId'];
        $weekId = $game['weekId'];
        $gameId = $game['gameId'];
        /* if ( $this->model_conf ) {	//叠加动态竞技场配置
            $game = array_merge($game,$this->model_conf);
        } */

        $gameplayall = $this->model->getModelGamePlayAll( "1_1004_20151214_1");
        //$gameplayall = $this->model->getModelGamePlayAll($gamesId);
        if ( !$gameplayall )
        {
            return false;
        }

        //校验人数
        if ( $game['gamePerson'] < $game['gamePersonAll'] )  //  || count($gameplayall) < $game['gamePersonAll']
        {
           // return false;
        }
        //生成下一场
        $newgame = $this->model->getModelRoomWeekGameLast($modelId,$roomId,$weekId,1,$gameId+1);

        if ( !$newgame )
        {
            gerr("赛场自增无效[$gamesId]client-".__LINE__."");
            return false;
        }
        //踢出多余玩家
        $arr = array_chunk($gameplayall,$game['gamePersonAll'], true);
        $gameplay = $arr[0];
        $kickouts = isset($arr[1])?$arr[1]:array();
        foreach ( $kickouts as $k=>$v )
        {
            debug("赛场满员踢掉[$gamesId]client-".__LINE__."");
            //删除参赛用户
            $res = $this->model->delModelGamePlay($game,$v);
            //通知用户: 报名人数满员
            $uid = $v['uid'];
            $code = 104;
            $data['errno'] = 4;
            $data['error'] = "报名人数满员，请等待下一场开放。\n报名费用已经返还到您的账户。";
            $data['coins'] = $v['coins']+$game['gameInCoins'];
            $res = $this->model->sendToUser( $uid, $cmd, $code, $data );
        }
        //赛事赛场开始
        $game['gameStart'] = time();
        $res = $this->model->setModelGame($modelId,$roomId,$weekId,$gameId,$game);
        //赛事随机组桌

        shuffle($gameplay);
        $i=0;

        foreach ( $gameplay as $k=>$v )
        {
            $players[$v['uid']]=$v;
            $i++;

            if ( !($i%3) )
            {
                //使用真实roomId，伪造的为roomMock

                $table = $this->model->iniTableInfo($game['roomReal'],$players,$modelId,$roomId,$weekId,$gameId);
                if ( !$table )
                {
                    gerr("赛场新桌无效[$gamesId] table=".json_encode($table)." players=".json_encode($players));
                    foreach ( $players as $k=>$v )
                    {
                        if ( $v['fd'] )
                        {
                            closeToFd( $v['fd'], "赛场新桌无效 client-".__LINE__." iniTableInfo($roomId,players)<<players=".json_encode($players));
                        }
                        else
                        {
                            $this->model->desUserInfo( $v['uid'], $v, __LINE__ );
                        }
                    }
                }
                else
                {
                    $tableId = $table['tableId'];
                   // debug("赛场新桌新局[$gamesId|$tableId]");
                    $this->ACT_MODEL_READY($table, 1);
                }
                $players = array();
            }
        }
        return true;
    }

    //[108]	赛事准备开始
    function ACT_MODEL_READY( $table, $is_new=0 )
    {
        $MODELS = include(ROOT.'/conf/rooms.php');
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $weekId = $table['weekId'];
        $gameId = $table['gameId'];
        $gamesId = $table['gamesId'];
        $tableId = $table['tableId'];
        $roomConf = $MODELS[$modelId][$roomId];
        $roomId = $table['roomMock'];
        //通知牌桌: 赛事将开
        $cmd = 5; $code = 108;
        $data['errno'] = 0;
        $data['error'] = "竞技赛即将开始。";
        $data['modelId'] = $modelId;
        $data['weekId'] = $weekId;			//场次编号(年+周)
        $data['gameId'] = $gameId;			//场次编号(本周第n次)
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //加事务锁 场次变动
        $lockId = 'GAMESID_'.$gamesId;
        $res = setLock($lockId);
        if ( !$res ) {
            gerr("[LOCKON] lockId=$lockId func=".__FUNCTION__);
            return false;
        }
        //标记赛事牌桌
        $game = $this->model->getModelGame($modelId,$roomId,$weekId,$gameId);
        $game['tableIds'][$tableId] = 1;
        $res = $this->model->setModelGame($modelId,$roomId,$weekId,$gameId,$game);
        //解事务锁 场地变动
        $res = delLock($lockId);
        $this->ACT_MODEL_ROOMIN($table, $is_new);
        return true;
    }

    //[sys]赛事通知进房
    function ACT_MODEL_ROOMIN( $table, $is_new=0 )
    {
        $MODELS = include(ROOT.'/conf/rooms.php');
        $GAME = include(ROOT.'/conf/games.php');
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $weekId = $table['weekId'];
        $gameId = $table['gameId'];
        $gamesId = $table['gamesId'];
        $tableId = $table['tableId'];
        $roomConf = $MODELS[$modelId][$roomId];
        //debug("赛场拉人进房[$gamesId|$tableId]");
        //通知用户: 进房成功
        $cmd = 5; $code = 1015;
        $data = array();
        $data['modelId'] = $modelId;
        $data['roomId'] = $roomId;
        $data['enterLimit'] = $roomConf['enterLimit'];
        $data['enterLimit_'] = $roomConf['enterLimit_'];
        $data['isGaming'] = 0;				//不在游戏
        $data['isContinue'] = 0;			//不返牌桌
        $data['baseCoins'] = $roomConf['baseCoins'];
        $data['rate'] = $roomConf['rate'];
        $data['rateMax'] = $roomConf['rateMax'];
        $data['limitCoins'] = $roomConf['limitCoins'];
        $data['rake'] = $roomConf['rake'];
        $data['gameBombAdd'] = $roomConf['gameBombAdd'];
        foreach ( $table['seats'] as $uid=>$seatId )
        {
            $data['coins'] = $table["seat{$seatId}coins"];
            $data['score'] = $table["seat{$seatId}score"];
            $player = array('fd'=>$table["seat{$seatId}fd"], 'uid'=>$uid, 'tableId'=>$tableId);
            $res = $this->model->sendToPlayer($player, $cmd, $code, $data,0);

        }
        //事件 - 牌桌开始
        $sceneId = $tableId;
        $act = "GAME_ALL_READY";
        $params = array('tableId'=>$tableId);
        $delay = $GAME['time_model_game_start'] * 1000;
        $hostId = $table['hostId'];

        setTimer(1,$sceneId, $act, $params, $delay,$hostId);

    }

    //事件 - 准备开打
    function GAME_ALL_READY( $table, $isTable=0 )
    {
        echo "GAME_ALL_READY \n";
        $state = 3;
        //直接执行
        if ( $isTable ) {
            $tableId = $table['tableId'];
        }
        //执行事件
        else {
            $tableId = $table['tableId'];
            $table = $this->model->getTableInfo($tableId);
            if ( !$table ) {
                gerr("牌桌开打无效[$tableId]");
                return false;
            }
            $newT = array();
            foreach ( $table['seats'] as $uid=>$seatId )
            {
                $user = $this->model->getUserInfo($uid);
                $fd = $user ? $user['fd'] : 0;
                if ( $table["seat{$seatId}fd"] != $fd ) {
                    $newT["seat{$seatId}fd"] = $table["seat{$seatId}fd"] = $fd;
                }
            }
        }
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $isNewgame = $table['isNewGame'];
        //清除打牌历史
        $this->model->delTableHistory($tableId);
        //更新牌桌状态及是否新局
        $newT['state'] = $table['state'] = $state;
        $newT['gameStart'] = $table['gameStart'] = time();
        $res = $this->model->setTableInfo($tableId, $newT);
        if ( !$res ) {
            gerr("牌桌开打失败[$tableId] newT=".json_encode($newT));
            return false;
        }
        if ( $modelId )
        {	//赛事模式下主动推送 已进凑桌
            foreach ( $table['seats'] as $uid=>$seatId )
            {
                $cmd = 5;
                $code = 1001;
                $player = array('uid'=>$uid, 'fd'=>$table["seat{$seatId}fd"], 'tableId'=>$tableId);
                $data = array();
                $res = $this->model->sendToUser($player, $cmd, $code, $data,0);
            }
        }
        if ( $isNewgame ) {
           // debug("牌桌三人凑齐[$tableId]");
        } else {
            // debug("牌桌三人准备[$tableId]");
        }
        //通知牌桌: 正式开始
        $cmd = 5;
        $code = 1004;
        $data = array();
        $data['modelId'] = $modelId;
        $data['roomId'] = $roomId;
        $data['tableId'] = $tableId;
        if ( $isNewgame ) {//!!!这个属性一定要这么写，源于最初版本中坑爹的协议设计
            $data['isNewGame'] = $isNewgame;//是否新局
        }
        $data['0'] = array(
            'seatId'	=> 0,
            'roomId'	=> $table['roomId'],
            'tableId'	=> $table['tableId'],
            'uid'		=> $table['seat0uid'],
            'coin'		=> $table['seat0coins'],
            'score'		=> $table['seat0score'],
            'nick'		=> "222222",//$table['seat1info']['nick'],
            'sex'		=> "1",//$table['seat1info']['sex'],
            'word'		=> "hello",//$table['seat1info']['word'],
            'propDress'	=> array("3"=>"0","1"=>1),// $table['seat1info']['propDress'],
        );
        $data['1'] = array(
            'seatId'	=> 1,
            'roomId'	=> $table['roomId'],
            'tableId'	=> $table['tableId'],
            'uid'		=> $table['seat1uid'],
            'coin'		=> $table['seat1coins'],
            'score'		=> $table['seat1score'],
            'nick'		=> "二傻子",//$table['seat1info']['nick'],
            'sex'		=> "1",//$table['seat1info']['sex'],
            'word'		=> "hello",//$table['seat1info']['word'],
            'propDress'	=> array("3"=>"0","1"=>1),// $table['seat1info']['propDress'],
        );
        $data['2'] = array(
            'seatId'	=> 2,
            'roomId'	=> $table['roomId'],
            'tableId'	=> $table['tableId'],
            'uid'		=> $table['seat2uid'],
            'coin'		=> $table['seat2coins'],
            'score'		=> $table['seat2score'],
            'nick'		=> "二妞",//$table['seat1info']['nick'],
            'sex'		=> "1",//$table['seat1info']['sex'],
            'word'		=> "hello",//$table['seat1info']['word'],
            'propDress'	=> array("3"=>"0","1"=>1),// $table['seat1info']['propDress'],
        );
        foreach ( $table['seats'] as $uid=>$seatId )
        {
            $data['seatId'] = $seatId;
            $player = array('uid'=>$uid, 'fd'=>$table["seat{$seatId}fd"], 'tableId'=>$tableId);
            $res = $this->model->sendToPlayer($player, $cmd, $code, $data,0);
        }
        //执行洗牌发牌
        $res = $this->GAME_SHUFFLE($table);
        return true;
    }

    //[SYS]	洗牌发牌
    function GAME_SHUFFLE( $table )
    {
        $MODELS = include(ROOT.'/conf/rooms.php');
        $GAME = include(ROOT.'/conf/games.php');
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $tableId = $table['tableId'];
        $roomConf = $MODELS[$modelId][$roomId];
        //debug("牌桌开始发牌[$tableId]");
        //洗牌
        $card = new Card;
        $cardPool = $card->newCardPool(1, $roomConf['gameBombAdd']);//[0][1][2][lord]
        //好牌分派 待续
        //待续
        //初始倍率
        $new['baseCoins'] = $table['baseCoins'] = $roomConf['baseCoins'];
        $new['rate'] = $new['rate_'] = $table['rate'] = $table['rate_'] = $roomConf['rate'];
        $new['limitCoins'] = $table['limitCoins'] = $roomConf['limitCoins'];
        $new['rake'] = $table['rake'] = $roomConf['rake'];
        //通知牌桌: 设置倍率
        $cmd = 5; $code = 1021;
        $data = array();
        $data['rateId'] = 0;
        $data['rate_num'] = 1;
        $data['rate'] = $table['rate'];
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //确定谁先叫庄
        $new['turnSeat'] = $table['turnSeat'] = ( $table['turnSeat'] == 4 ? mt_rand(0,2) : $table['turnSeat'] );
        //确定底牌(地主牌)
        $new['lordCards'] = $table['lordCards'] = $cardPool['lord'];
        //设置各家手牌，判断明牌
        $rate_showcard = 0;
        foreach( $table['seats'] as $uid => $seatId )
        {
            //设置各自手牌
            $new["seat{$seatId}cards"] = $table["seat{$seatId}cards"] = $cardPool[$seatId];
            //如果有人明牌开始
            if( $table["seat{$seatId}show"] )
            {
                //明牌倍率
                $rate_showcard = $GAME['rate_showcard'];
                //通知牌桌: 有人明牌
                $cmd = 5; $code = 1019;
                $data = array();
                $data['rate'] = $rate_showcard;
                $data['showCardId'] = $seatId;
                $data['showCardInfo'] = $cardPool[$seatId];
                $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);

                $data_['showCard'][$seatId] = 1;
                $data_['showCardInfo'][$seatId] = $cardPool[$seatId];
                continue;
            }
            //如果没有明牌
            $data_['showCard'][$seatId] = 0;
            $data_['showCardInfo'][$seatId] = array();
        }
        //更新牌桌倍率
        if ( $rate_showcard ) {
            $new['rate'] = $new['rate_'] = $table['rate'] = $table['rate_'] = $this->TABLE_NEW_RATE( $table, $seatId, $rate_showcard );
        }
        //更新牌桌数据
        $res = $this->model->setTableInfo( $tableId, $new );
        if ( !$res ) {
            gerr("牌桌发牌失败[$tableId] new=".json_encode($new));
            return false;
        }

        //通知用户: 牌桌发牌
        $cmd = 5;
        $code = 1005;
        foreach( $table['seats'] as $uid => $seatId )
        {
            /*if ( ISPRESS || $table["seat{$seatId}robot"] )
            {	//压测/外挂专用代码: 三家牌面发到用户
                $data_['seat0cards'] = $table['seat0cards'];
                $data_['seat1cards'] = $table['seat1cards'];
                $data_['seat2cards'] = $table['seat2cards'];
            }  */
            $data_['myCard'] = $table["seat{$seatId}cards"];
            $player = array('fd'=>$table["seat{$seatId}fd"], 'uid'=>$uid, 'tableId'=>$tableId);
            $res = $this->model->sendToPlayer($player, $cmd, $code, $data_,0);
        }
        //事件 - 邀请叫庄
        $sceneId = $tableId;
        $act = "TURN_CALL_LORD";
        $params = array('tableId'=>$tableId);
        $delay = $GAME['time_invite_belord'] * 1000;
        $hostId = $table['hostId'];
        setTimer(1,$sceneId, $act, $params, $delay, $hostId);

        return true;
    }

    //[SYS]	轮到叫庄
    function TURN_CALL_LORD( $table, $isTable=0 )
    {
        $state = 4;
        //直接执行
        if ( $isTable ) {
            $tableId = $table['tableId'];
        }
        //执行事件
        else {
            $tableId = $table['tableId'];
            $table = $this->model->getTableInfo($tableId);
            if ( !$table ) {
                gerr("轮叫牌桌无效[?|?|$tableId|?]");
                return false;
            }
            $newT = array();
            foreach ( $table['seats'] as $uid=>$seatId )
            {
                $user = $this->model->getUserInfo($uid);
                $fd = $user ? $user['fd'] : 0;
                if ( $table["seat{$seatId}fd"] != $fd ) {
                    $newT["seat{$seatId}fd"] = $table["seat{$seatId}fd"] = $fd;
                }
            }
            $newT && $this->model->setTableInfo($tableId, $newT);
        }
        $seatId = $table['turnSeat'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //更新牌桌状态
        if ( $table['state'] != $state )
        {
            $table['state'] = $state;
            $res = $this->model->setTableState($tableId, $state);
            if ( !$res ) {
                gerr("轮叫状态失败[$fd|$uid|$tableId|$seatId] setTableState=$state");
                return false;
            }
           // debug("牌桌开始叫庄[$fd|$uid|$tableId|$seatId]");
            foreach ($table['seats'] as $_uid=>$_seatId)
            {
                if ( !$table["seat{$_seatId}fd"] && !$table["seat{$_seatId}robot"] ) {
                    $this->USER_ENTRUST($table, $_seatId, 3);//掉线用户自动托管
                }
            }
        }
        //debug("牌桌轮到叫庄[$fd|$uid|$tableId|$seatId]");
        $GAME = include(ROOT.'/conf/games.php');
        //闹钟 - 到期叫庄
        $sceneId = $tableId;
        $act = "AUTO_CALL_LORD";
        $params = array('tableId'=>$tableId, 'seatId'=>$seatId);
        $delay = ($table["seat{$seatId}trust"] || $table["seat{$seatId}robot"] ? $GAME['time_trust_play'] : $GAME['time_auto_lord']) * 1000;
        $hostId = $table['hostId'];
        setTimer(1,$sceneId, $act, $params, $delay, $hostId);
        //通知牌桌: 轮到叫庄
        $cmd = 5;
        $code = 1008;
        $data = array(
            'callId'=>$seatId,
        );
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        return true;
    }

    //[SYS]	自动叫庄
    function AUTO_CALL_LORD( $params, $user=array(), $beLord=0 )
    {
        echo "111111111111111111111AUTO_CALL_LORD \n";
        $state = 4;
        //直接执行
        if ( $user ) {
            $tableId = $user['tableId'];
            $seatId = $user['seatId'];
        }
        //事件执行
        else {
            //参数校验
            if ( !isset($params['tableId']) || !isset($params['seatId']) ) {
                gerr("叫庄参数无效[?|?|?|?] params=".json_encode($params));
                return false;
            }
            $tableId = $params['tableId'];
            $seatId = $params['seatId'];
            $beLord = mt_rand(1,3) > 2 ? 2 : mt_rand(1,2);//三分之一
            if ( ISTESTS ) $beLord = 1;
        }
        //获取牌桌
        $table = $this->model->getTableInfo($tableId);
        if ( !$table ) {
            $fd = $user ? $user['fd'] : "?";
            $uid = $user ? $user['uid'] : "?";
            $seatId = $user ? $user['seatId'] : "?";
            gerr("叫庄牌桌无效[$fd|$uid|$tableId|$seatId]");
            return false;
        }
        $fd = $user ? $user['fd'] : $table["seat{$seatId}fd"];
        $uid = $user ? $user['uid'] : $table["seat{$seatId}uid"];
        if ( $table['state'] != $state || $table['turnSeat'] != $seatId ) {
            debug("叫庄网络延迟[$fd|$uid|$tableId|$seatId] state$state=".$table['state']." turn".$table['turnSeat']."=".$seatId);
            return false;
        }
        $old_rate_ = $table['rate_'];
        //执行叫庄，获取最新牌桌数据
        $tableOld = $table;
        $table = $this->model->call_lord($tableOld, $beLord);
        if ( !$table ) {
            gerr("叫庄执行失败[$fd|$uid|$tableId|$seatId] call_lord( ".json_encode($tableOld).", $beLord )");
            return false;
        }
        if ( $user ) {
            if ( $beLord == 1) {$text = "用户选择叫庄"; }
            else { 				$text = "用户放弃叫庄"; }
        } else {
            if ( $beLord == 1) {$text = "自动选择叫庄"; }
            else { 				$text = "自动放弃叫庄"; }
        }
        //debug("{$text}[$fd|$uid|$tableId|$seatId] beLord=$beLord");
        //通知牌桌: 叫庄/不叫
        $cmd = 5;
        $code = 1006;
        $data = array(
            'beLordId'=>$seatId,
            'beLordInfo'=>$beLord,
        );
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //通知牌桌: 有倍率变化
        if ( $table['rate_'] > $old_rate_ )
        {
            $cmd = 5;
            $code = 1021;
            $data = array(
                'rateId'=>$seatId,
                'rate_num'=>intval($table['rate_'] / $old_rate_),
                'rate'=>$table['rate_'],
            );
            $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        }
        //检查牌桌状态
        if ( $table['state'] == 3 )
        {
            //debug("叫庄再次发牌[$fd|$uid|$tableId|$seatId]");
            //再次发牌
            $res = $this->GAME_SHUFFLE($table);
        }
        elseif ( $table['state'] == 4 )
        {
            //轮到叫庄
            $res = $this->TURN_CALL_LORD($table, 1);
        }
        elseif ( $table['state'] == 5 )
        {
            //轮到抢庄
            $res = $this->TURN_GRAB_LORD($table);
        }
        elseif ( $table['state'] == 6 )
        {
            //确认地主
            $res = $this->GAME_LORD_DONE($table);
        }
        else
        {
            gerr("叫庄执行失败[$fd|$uid|$tableId|$seatId] table＝".json_encode($table));
        }
        return true;
    }

    //[SYS]	轮到抢庄
    function TURN_GRAB_LORD( $table )
    {
        $state = 5;
        $tableId = $table['tableId'];
        $seatId = $table['turnSeat'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //更新牌桌状态
        if ( $table['state'] != $state )
        {
            $table['state'] = $state;
            $res = $this->model->setTableState($tableId, $state);
            if ( !$res ) {
                gerr("轮抢执行失败[$fd|$uid|$tableId|$seatId] state=$state");
                return false;
            }
            //debug("牌桌开始抢庄[$fd|$uid|$tableId|$seatId]");
        }
        //debug("牌桌轮到抢庄[$fd|$uid|$tableId|$seatId]");
        $GAME = include(ROOT.'/conf/games.php');
        //闹钟 - 自动抢庄
        $sceneId = $tableId;
        $act = "AUTO_GRAB_LORD";
        $params = array('tableId'=>$tableId, 'seatId'=>$seatId);
        $delay = ($table["seat{$seatId}trust"] || $table["seat{$seatId}robot"] ? $GAME['time_trust_play'] : $GAME['time_auto_lord']) * 1000;
        $hostId = $table['hostId'];
        setTimer(1,$sceneId, $act, $params, $delay, $hostId);
        //通知牌桌: 轮到抢庄
        $cmd = 5;
        $code = 1011;
        $data = array(
            'callId'=>$seatId,
        );
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        return true;
    }

    //[SYS]	自动抢庄
    function AUTO_GRAB_LORD( $params, $user=array(), $beLord=0 )
    {
        $state = 5;
        //直接执行
        if ( $user ) {
            $tableId = $user['tableId'];
            $seatId = $user['seatId'];
        }
        //事件执行
        else {
            //校验参数
            if ( !isset($params['tableId']) || !isset($params['seatId']) ) {
                gerr("抢庄参数无效[?|?|?|?] params=".json_encode($params));
                return false;
            }
            $tableId = $params['tableId'];
            $seatId = $params['seatId'];
            $beLord = mt_rand(1,3) > 2 ? 2 : mt_rand(1,2);//三分之一
            if ( ISTESTS ) $beLord = 1;
        }
        //获取牌桌
        $table = $this->model->getTableInfo($tableId);
        if ( !$table ) {
            $fd = $user ? $user['fd'] : "?";
            $uid = $user ? $user['uid'] : "?";
            $seatId = $user ? $user['seatId'] : "?";
            gerr("抢庄牌桌无效[$fd|$uid|$tableId|$seatId]");
            return false;
        }
        $fd = $user ? $user['fd'] : $table["seat{$seatId}fd"];
        $uid = $user ? $user['uid'] : $table["seat{$seatId}uid"];
        if ( $table['state'] != $state || $table['turnSeat'] != $seatId ) {
            debug("抢庄网络延迟[$fd|$uid|$tableId|$seatId] state$state=".$table['state']." turn".$table['turnSeat']."=".$seatId);
            return false;
        }
        $old_rate_ = $table['rate_'];
        //执行抢庄，获取最新牌桌数据
        $tableOld = $table;
        $table = $this->model->grab_lord($tableOld, $beLord);
        if ( !$table ) {
            gerr("抢庄执行失败[$fd|$uid|$tableId|$seatId] grab_lord( ".json_encode($tableOld).", $beLord )");
            return false;
        }
        if ( $user ) {
            if ( $beLord == 1) {$text = "用户选择抢庄"; }
            else { 				$text = "用户放弃抢庄"; }
        } else {
            if ( $beLord == 1) {$text = "自动选择抢庄"; }
            else { 				$text = "自动放弃抢庄"; }
        }
        //debug("{$text}[$fd|$uid|$tableId|$seatId] beLord=$beLord");
        //通知牌桌: 抢庄/不抢
        $cmd = 5;
        $code = 1016;
        $data = array(
            'grabLordId'=>$seatId,
            'grabLordInfo'=>$beLord,
        );
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //通知牌桌: 有倍率变化
        if ( $table['rate_'] > $old_rate_ )
        {
            $cmd = 5;
            $code = 1021;
            $data = array(
                'rateId'=>$seatId,
                'rate_num'=>intval($table['rate_'] / $old_rate_),
                'rate'=>$table['rate_'],
            );
            $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        }
        //检查牌桌状态
        if ( $table['state'] == 5 )
        {
            //轮到抢庄
            $res = $this->TURN_GRAB_LORD($table);
        }
        elseif ( $table['state'] == 6 )
        {
            //确认地主
            $res = $this->GAME_LORD_DONE($table);
        }
        else
        {
            gerr("抢庄执行失败[$fd|$uid|$tableId|$seatId] table＝".json_encode($table));
        }
        return true;
    }

    //[SYS]	敲定地主
    function GAME_LORD_DONE( $table )
    {
        $state = 6;
        $tableId = $table['tableId'];
        $seatId = $table['turnSeat'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //更新牌桌状态
        $table['state'] = $state;
        $res = $this->model->setTableState($tableId, $state);
        if ( !$res ) {
            gerr("定庄执行失败[$fd|$uid|$tableId|$seatId] table＝".json_encode($table));
            return false;
        }
        //debug("牌桌敲定地主[$fd|$uid|$tableId|$seatId]");
        //临时叫抢倍率扶正
        $table['rate'] = $table['rate_'];
        $card = new Card;
        //通知牌桌: 有人成为地主
        $cmd = 5;
        $code = 1007;
        $data = array();
        $data['lordId'] = $seatId;
        $data['lordCard'] = $table['lordCards'];
        $data['lordBonus'] = $card->getLordCardsRate($table['lordCards']);
        foreach ( $table['seats'] as $_uid=>$_seatId )
        {
            if (isset($data['myCard'])) { unset($data['myCard']); }
            if (isset($data['lordShowCard'])) { unset($data['lordShowCard']); }
            //如果是地主本人: 把地主所有手牌给他
            if ( $seatId == $_seatId ) {
                $data['myCard'] = $table["seat{$seatId}cards"];
            }
            //如果非地主本人，但地主明牌了: 把地主所有手牌亮给他
            elseif ( $seatId != $_seatId && $table["seat{$seatId}show"] == 1 ) {
                $data['lordShowCard'] = $table["seat{$seatId}cards"];
            }
            $player = array('fd'=>$table["seat{$_seatId}fd"], 'uid'=>$_uid, 'tableId'=>$tableId);
            $res = $this->model->sendToPlayer($player, $cmd, $code, $data);
        }
        //通知牌桌: 底牌导致倍率变更(即使倍率没变化也要通知)
        $newT['rate'] = $table['rate'] = $this->TABLE_NEW_RATE($table, $seatId, $data['lordBonus']);
        //更新牌桌信息
        $res = $this->model->setTableInfo($tableId, $newT);
        if ( !$res ) {
            gerr("定庄执行失败[$fd|$uid|$tableId|$seatId] client-".__LINE__." rate=".$table['rate']);
            return false;
        }
        // 新版活动任务
       /* if ( $table["seat{$seatId}fd"] ) {
            $accode = 0;
            $action = 'GAME_LORD_DONE';
            $userinfo = $this->model->getUserInfo($uid);
            $tesk = new tesk($this->mysql, $this->redis, $accode, $action);
            $adduinfo = $tesk->execute('be_lord', $userinfo, array(), 1, $table);
            if ( $adduinfo ) {
                $res = $this->model->incUserInfo($uid, $adduinfo, 6);
                $res && $userinfo = array_merge($userinfo, $res);
            }
        } */
        //轮到打牌
        $res = $this->TURN_PLAY_CARD($table);
        return true;
    }

    //[SYS]	轮到打牌
    function TURN_PLAY_CARD( $table )
    {
        $GAME = include(ROOT.'/conf/games.php');
        $tableId = $table['tableId'];
        $seatId = $table['turnSeat'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //debug("牌桌轮到打牌[$fd|$uid|$tableId|$seatId]");
        //闹钟 - 到期打牌
        $sceneId = $tableId;
        $act = "AUTO_PLAY_CARD";
        $params = array('tableId'=>$tableId, 'seatId'=>$seatId);
        $delay = ($table["seat{$seatId}trust"] || $table["seat{$seatId}robot"] ? $GAME['time_trust_play'] : $GAME['time_auto_play']) * 1000;
        $hostId = $table['hostId'];
        setTimer(1,$sceneId, $act, $params, $delay, $hostId);
        //通知牌桌: 轮到打牌
        $cmd = 5;
        $code = 1009;
        $data = array(
            'callId'=>$seatId,
        );
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        return true;
    }

    //[SYS]	自动打牌(出牌跟牌过牌)
    function AUTO_PLAY_CARD( $params )
    {
        $GAME = include(ROOT.'/conf/games.php');
        $state = 6;
        //校验参数
        if ( !isset($params['tableId']) || !isset($params['seatId']) ) {
            gerr("机打参数无效 params=".json_encode($params));
            return false;
        }
        $tableId = $params['tableId'];
        $seatId = $params['seatId'];
        //获取牌桌
        $table = $this->model->getTableInfo($tableId);
        if ( !$table ) {
            gerr("机打牌桌无效[?|?|$tableId|$seatId]");
            return false;
        }
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $weekId = $table['weekId'];
        $gameId = $table['gameId'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //校验牌桌状态、席位轮流
        if ( $table['state'] != $state || $table['turnSeat'] != $seatId ) {
            //debug("机打网络延迟[$fd|$uid|$tableId|$seatId] state".$table['state']."=$state turn".$table['turnSeat']."=$seatId");
            return false;
        }
        //校验手牌
        if ( !$table["seat{$seatId}cards"] ) {
            gerr("机打手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
            return false;
        }
        //自动托管
        if ( !$table['seat'.$seatId.'trust'] && !$table['seat'.$seatId.'robot'] )
        {
            $new['seat'.$seatId.'delay'] = ++$table['seat'.$seatId.'delay'];

            $new['seat'.$seatId.'delay'] = $table['seat'.$seatId.'delay'] = 0;
            //执行托管
           // debug("机打自动托管[$fd|$uid|$tableId|$seatId]");
            $table = $this->USER_ENTRUST( $table, $seatId, 2 );//2自动托管
            // }
        }
        //新打牌机器人start
        $_mineseatid = $seatId;
        $_prevseatid = $_mineseatid+1;
        $_prevseatid = ($_prevseatid == 3 ? 0 : $_prevseatid);
        $_nextseatid = $_prevseatid+1;
        $_nextseatid = ($_nextseatid == 3 ? 0 : $_nextseatid);
        $_cards = $table['outCards']?$table['outCards']:array();
        $_table['out'] = cardsEnTranse($_cards);
        $_cards = $table['lastCards']?$table['lastCards']:array();
        $_table['cards'] = cardsEnTranse($_cards);
        $_table['bidder'] = $table['lastCall'];
        $_mine['pos'] = $_mineseatid;
        if ( !$table['seat'.$_mineseatid.'cards'] || !is_array($table['seat'.$_mineseatid.'cards']) ){
            gerr("机打手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
            return false;
        }
        $_mine['hand'] = cardsEnTranse($table['seat'.$_mineseatid.'cards']);
        $_mine['is_lord'] = intval($table['lordSeat']==$_mineseatid);
        $_mine['is_bidder'] = intval($table['lastCall']==$_mineseatid);
        $_prev['pos'] = $_prevseatid;
        if ( !$table['seat'.$_prevseatid.'cards'] || !is_array($table['seat'.$_prevseatid.'cards']) ){
            gerr("机打手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
            return false;
        }
        $_prev['hand'] = cardsEnTranse($table['seat'.$_prevseatid.'cards']);
        $_prev['is_lord'] = intval($table['lordSeat']==$_prevseatid);
        $_prev['is_bidder'] = intval($table['lastCall']==$_prevseatid);
        $_next['pos'] = $_nextseatid;
        if ( !$table['seat'.$_nextseatid.'cards'] || !is_array($table['seat'.$_nextseatid.'cards']) ){
            gerr("机打手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
            return false;
        }
        $_next['hand'] = cardsEnTranse($table['seat'.$_nextseatid.'cards']);
        $_next['is_lord'] = intval($table['lordSeat']==$_nextseatid);
        $_next['is_bidder'] = intval($table['lastCall']==$_nextseatid);
        $card = new Card(1, $_table, $_prev, $_next, $_mine);//1AI为1级，目前只有01
        $_out = $card->cardsLogic();
        $_out = $_out ? $_out : array();
        //新打牌机器人end
        //有牌打出
        if ( $_out )
        {
            $cardstype = cardsCheck($card->cardsDeTranse($_out));
            $sendCards = $cardstype['cards'];
           // debug("机打自动出牌[$fd|$uid|$tableId|$seatId]");
            //通知牌桌: 某人出牌
            $cmd = 5; $code = 1017;
            $data = array();
            $data['callId'] = $seatId;
            $data['sendCards'] = $sendCards;
            $data['cardType'] = intval($cardstype['type']);
            $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
            //检测牌型是否影响倍率
            $rate = isset( $GAME['rate_cardstype'.intval( $cardstype['type'])]) ? $GAME['rate_cardstype'.intval( $cardstype['type'])] : 1;
            if ( $rate > 1 ) {
                $new['rate'] = $table['rate'] = $this->TABLE_NEW_RATE( $table, $seatId, $rate );
            }
            //已打出的牌
            $new['outCards'] = $table['outCards'] = is_array( $table['outCards']) ? array_merge( $table['outCards'], $sendCards) : $sendCards;
            //手牌
            $new['seat'.$seatId.'cards'] = $table['seat'.$seatId.'cards'] = array_values(array_diff( $table['seat'.$seatId.'cards'], $sendCards) );
            //记录出牌次数，用于春天/反春判定
            $new['seat'.$seatId.'sent'] = ++$table['seat'.$seatId.'sent'];
            //轮转下家
            $new['turnSeat'] = $table['turnSeat'] = $this->model->getSeatNext($seatId);
            //上把出牌人
            $new['lastCall'] = $table['lastCall'] = $seatId;
            //上把出牌内容
            $new['lastCards'] = $table['lastCards'] = $sendCards;
            //重设不跟次数
            $new['noFollow'] = $table['noFollow'] = 0;
            //更新牌桌信息
            $res = $this->model->setTableInfo($tableId, $new);
            if ( !$res ) {
                gerr("机打执行失败[$fd|$uid|$tableId|$seatId] new=".json_encode($new));
                return false;
            }
            //手牌出完
            if ( count( $table["seat{$seatId}cards"]) == 0 ) {
                if ( $modelId ) {
                    //竞技结算
                    $res = $this->MODEL_GAME_OVER($table);
                } else {
                    //牌桌结算
                    $res = $this->TABLE_GAME_OVER($table);
                }
                return true;
            }
            //轮到打牌
            $res = $this->TURN_PLAY_CARD($table);
            return true;
        }
        //debug("机打自动不跟[$fd|$uid|$tableId|$seatId]");
        //通知牌桌: 某人不跟
        $cmd = 5; $code = 1018;
        $data = array();
        $data['callId'] = $seatId;
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //重设不跟次数
        $new['noFollow'] = ++$table['noFollow'];
        //清空叫牌内容
        if( $table['noFollow'] == 2 ) {
            $new['noFollow'] = $table['noFollow'] = 0;
            $new['lastCards'] = $table['lastCards'] = array();
        }
        //轮转下家
        $new['turnSeat'] = $table['turnSeat'] = $this->model->getSeatNext($seatId);
        //更新牌桌信息
        $res = $this->model->setTableInfo($tableId, $new);
        if ( !$res ) {
            gerr("机打执行失败[$fd|$uid|$tableId|$seatId] new=".json_encode($new));
            return false;
        }
        //轮到打牌
        $res = $this->TURN_PLAY_CARD($table);
        return true;
    }

    //[SYS]	某人托管
    function USER_ENTRUST( $table, $seatId, $state )
    {
        $tableId = $table['tableId'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //通知牌桌: 有人托管
        $cmd = 5; $code = 1028;
        $data = array();
        $data['trustId'] = $seatId;
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //设置托管状态		//state: 0没有托管1主动托管2延时托管3退房托管4掉线托管
        $newT["seat{$seatId}trust"] = $table["seat{$seatId}trust"] = $state;
        if ( $state == 3 || $state == 4 ) {	//退房托管或掉线托管时，不向用户发送牌桌消息
            $newT["seat{$seatId}fd"] = $table["seat{$seatId}fd"] = 0;
        }
        //更新牌桌信息
        $res = $this->model->setTableInfo($tableId, $newT);
        if ( !$res ) {
            gerr("用户托管失败[$fd|$uid|$tableId|$seatId] state=".$table["seat{$seatId}trust"]."->".$state);
            return false;
        }
        //修改牌桌上对应座位计时器 - 不管什么任务
        $GAME = include(ROOT.'/conf/games.php');
        //闹钟 - 修改时间
        $sceneId = $tableId;
        $params = array('seatId'=>$seatId);
        $delay = $GAME['time_trust_play'] * 1000;
        $hostId = $table['hostId'];
        //updTimer($sceneId, $params, $delay, $hostId);   //TODO
        return $table;
    }

    //[SYS]	某人解除托管
    function USER_DETRUST( $table, $seatId, $state )
    {
        $tableId = $table['tableId'];
        $fd = $table["seat{$seatId}fd"];
        $uid = $table["seat{$seatId}uid"];
        //通知牌桌: 有人解除托管
        $cmd = 5; $code = 1029;
        $data = array();
        $data['trustId'] = $seatId;
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //设置解托状态
        $newT["seat{$seatId}trust"] = $state;
        //更新牌桌信息
        $res = $this->model->setTableInfo($tableId, $newT);
        if ( !$res ) {
            gerr("用户解托失败[$fd|$uid|$tableId|$seatId] state=$state");
            return false;
        }
        return true;
    }

    //[SYS]	赛事结束
    function MODEL_GAME_OVER( $table )
    {
        echo "MODEL_GAME_OVER beg \n";
        $state = 7;
        //参数整理
        $MODELS = include(ROOT.'/conf/rooms.php');
        $GAME = include(ROOT.'/conf/games.php');
        $now = time();
        $modelId = $table['modelId'];
        $roomId = $table['roomId'];
        $weekId = $table['weekId'];
        $gameId = $table['gameId'];
        $gamesId = $table['gamesId'];
        $tableId = $table['tableId'];
        $winner = $table['lastCall'];
        $lordId = $table['lordSeat'];
        //加事务锁	弃赛锁	互斥锁
        $lockId = 'MODEL_GIVEUP_'.$tableId;
        $res = setLock($lockId, 1);
        if ( !$res ) return false;
        //更新牌桌状态
        $newT['state'] = $table['state'] = $state;
        $res = $this->model->setTableState($tableId, $state);
        //debug("赛桌结算开始[$gamesId|$tableId]");
        //地主赢/农民赢、春天/反春
        $isLordwin = $isLordspring = $isBoorspring = 0;
        if ( $winner == $lordId ) {
            $isLordwin = 1;
            $isLordspring = intval(!$table["seat".$this->model->getSeatNext($winner)."sent"] && !$table["seat".$this->model->getSeatNext($this->model->getSeatNext($winner))."sent"]);
        } else {
            $isLordwin = 0;
            $isBoorspring = intval($table["seat{$lordId}sent"]==1 && in_array(0, array($table['seat0sent'],$table['seat1sent'],$table['seat2sent'])));
        }
        //变更牌桌倍率
        if ( $isLordspring ) {
            $newT['rate'] = $table['rate'] = $this->TABLE_NEW_RATE( $table, $winner, $GAME['rate_lordspring'] );
        } elseif( $isBoorspring ) {
            $newT['rate'] = $table['rate'] = $this->TABLE_NEW_RATE( $table, $winner, $GAME['rate_boorspring'] );
        }
        //竞技场不存在输光因素
        $total = $table['baseCoins'] * $table['rate'];
        //计算座位输赢及分值状况
        $data['total'] = array( "0"=>0, "1"=>0, "2"=>0 );
        $data['coins'] = array( "0"=>$table["seat0coins"], "1"=>$table["seat1coins"], "2"=>$table["seat2coins"] );
        if ( $isLordwin ) {
            $next = $this->model->getSeatNext($lordId);
            $data['scoreTotal'][$next] = -1 * min($total, $table["seat{$next}score"]);
            $prev = $this->model->getSeatNext($next);
            $data['scoreTotal'][$prev] = -1 * min($total, $table["seat{$next}score"]);
            $data['scoreTotal'][$lordId] = 2 * $total;//竞技场不存在输光因素
        } else {
            $data['scoreTotal'][$lordId] = -1 * min(2 * $total, $table["seat{$lordId}score"]);
            $next = $this->model->getSeatNext($lordId);
            $data['scoreTotal'][$next] = $total;//竞技场不存在输光因素
            $prev = $this->model->getSeatNext($next);
            $data['scoreTotal'][$prev] = $total;//竞技场不存在输光因素
        }
        $users = array();
        foreach ( $table['seats'] as $uid=>$seatId )
        {
            $data['isWinner'][$seatId] = intval( ($isLordwin && $seatId == $lordId) || (!$isLordwin && $seatId != $lordId) );
            $data['score'][$seatId] = max(0, $table["seat{$seatId}score"] + $data['scoreTotal'][$seatId]);
        }
        $data['cards'] = array( "0"=>$table['seat0cards'], "1"=>$table['seat1cards'], "2"=>$table['seat2cards'] );//剩下的牌
        krsort($data['isWinner']);
        krsort($data['total']);
        krsort($data['coins']);
        krsort($data['scoreTotal']);
        krsort($data['score']);
        krsort($data['cards']);
        $seat_score = $data['score'];
        //通知牌桌: 开始结算
        $cmd = 5; $code = 1014;
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        //处理牌桌玩家数据
        foreach( $seat_score as $seatId => $val )
        {
            $uid = $table["seat{$seatId}uid"];
            $win = intval($data['isWinner'][$seatId] == 1);
            //增加用户打牌记录
            $sql = "UPDATE `lord_game_analyse` SET `matches` = `matches` + 1, `win` = `win` + $win WHERE `uid` = $uid";
           // bobSql($sql);
            $addU['normal_all_play'] = 1;
            $addU['normal_all_win'] = $win;
            $newU['score'] = $newUM['score'] = $val;
            $newU['gameStart'] = $user['gameStart'] = 0;
           // $addU && $this->model->incUserInfo($uid, $addU);unset($addU);
            //$newU && setUser($uid, $newU);unset($newU);
            //$newUM && $this->model->setUserModel($uid, $newUM);unset($newUM);
            $newT["seat{$seatId}score"] = $table["seat{$seatId}score"] = $val;
        }
        $newT['seat_score'] = $table['seat_score'] = $seat_score;
        $newT && $this->model->setTableInfo($tableId, $newT);unset($newT);

        //事件 - 场赛结束
        $sceneId = $tableId;
        $act = "MODEL_GAME_DONE";
        $params = array('tableId'=>$tableId);
        $delay = $GAME['time_table_total'] * 1000;
        $hostId = $table['hostId'];
        setTimer(1,$sceneId, $act, $params, $delay, $hostId);

        //解事务锁	弃赛锁	互斥锁
        $res = delLock($lockId);

        echo "MODEL_GAME_OVER end \n";
        return true;
    }

    //[SYS]	赛事结束
    function MODEL_GAME_DONE( $params )
    {
        $tableId = $params['tableId'];
        $table = $this->model->getTableInfo($tableId);
        if (!$tableId || !$table) {
            gerr("赛结牌桌无效[$tableId] table=" . json_encode($table));
            return false;
        }
    }

    //计算倍率，广播到牌桌，并返回新的$table['rate']
    function TABLE_NEW_RATE( $table, $seatId, $rate )
    {
        $table['rate'] = $table['rate'] ? $table['rate'] : 1;	//基础设置
        $rate_num = ( $rate >= 1 && $rate <= 5) ? $rate : 1;		//翻倍限制
        $rate = $table['rate'] = $table['rate'] * $rate_num;	//翻倍结果

        //广播到牌桌: 倍率变更
        $cmd = 5; $code = 1021;
        $data['rateId'] = $seatId;
        $data['rate_num'] = $rate_num;
        $data['rate'] = $rate;
        $res = $this->model->sendToTable($table, $cmd, $code, $data, __LINE__);
        return $rate;
    }

}


