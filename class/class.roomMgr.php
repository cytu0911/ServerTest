<?php
/**
 * Created by PhpStorm.
 * User: youjoy8
 * Date: 2015/12/7
 * Time: 18:52
 * 负责管理房间和牌桌数据
 */

class roomMrg
{
    public $redis = null;
    public $mysql = null;

    private $lord_model_room_conf = "lord_model_room_conf";
    private $key_room_games_ = "key_room_games_";      //特定房间games
    private $key_model_gameplay_ = "key_model_gameplay_"; //赛场用户表

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

    function __construct( $rd, $mq )
    {
        $this->redis = $rd;
        $this->mysql = $mq;
    }

    //获取所有房间配置
    public function getRooms( )
    {
        $arr = $this->redis->hgetall($this->lord_model_room_conf);
        if($arr)
        {
            return $arr;
        }
        $sql = "SELECT *FROM `lord_model_rooms` WHERE `isOpen` =1 LIMIT 0 , 30";
        $arr = $this->mysql->getData($sql);
        if( empty($arr) ) return false;

        foreach( $arr as $v )
        {
            $this->redis->hset($this->lord_model_room_conf, $v['roomId'],$v  );
            $ret[ $v['roomId'] ] = $v;
        }
        return $ret;
    }

    //获取房间配置
    public function getRoom( $roomId )
    {
        return $this->redis->hget( $this->lord_model_room_conf,$roomId );
    }

    //判断当前场次是否可报名
    public function canSignUp( $roomId, $gameId  )
    {
        return true;
    }


    //初始化一个赛场
    function newModelGame($roomId,$weekId,$gameId=1,$modelId=1)
    {
        $roomConf = $this->getRoom($roomId);
        if(! $roomConf ) { return false;}

        $gamesId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$gameId;
        $game = array(
            'gamesId' => $gamesId,
            'modelId' => $modelId,
            'roomId' => $roomId,
            'weekId' => $weekId,
            'gameId' => $gameId,
           // 'gameLevel' => $room['gameLevel'],
            'gamePool' => 0,
            'gamePerson' => 0,
            'gamePersonAll' => $roomConf['gamePersonAll'],
            'startType' => $roomConf['startType'],
            'gamePlay' => 0,
            'gameStart' => 0,
            'gameCreate' => time(),
        );
        $res = $this->redis->hset($this->key_room_games_.$modelId."_".$roomId,$gamesId,$game);
        if ( !$res )
            return false;
        return $game;
    }

    //获取某个房间所有games
    public function getRoomGames($roomId, $modelId = 1 )
    {
        if ( !$roomId ){ return false; }
        return $this->redis->hgetall($this->key_room_games_.$modelId."_".$roomId);
    }

    //获取房间game
    public function getRoomGame( $roomId,$gamesId,$modelId=1 )
    {
        if( !$roomId || !$gamesId ) { return false; }
        return $this->redis->hget($this->key_room_games_.$modelId."_".$roomId,$gamesId );
    }
    public function setRoomGame($roomId,$gamesId,$game,$modelId=1)
    {
        if( !$roomId || !$gamesId || !$game || !is_array($game) ) { return false; }
        return $this->redis->hset( $this->key_room_games_.$modelId."_".$roomId,$gamesId,$game );
    }
    //获取模式最新赛场
    public function getModelRoomWeekGameLast($modelId,$roomId,$weekId,$isNew=0,$gameId=1)
    {
        if ( !$modelId || !$weekId )  return false;
        if ( $isNew )
            $game = array();
        else
        {
            $games = $this->getRoomGames($roomId);
            $games = $games ? $games : array();

            $game = array();
            foreach ( $games as $k=>$v )
            {
                if (  $weekId == $v['weekId'] )
                    $game[$v['gameId']] = $v;
            }
        }
        $now = time();
        $day0 = strtotime(date('Y-m-d'));
        $weekDay = date("N");	//周n[1-7]
        if ( $game )
        {
            krsort($game);
            $game = reset($game);
        }
        else
        {
            $game = $this->newModelGame($roomId,$weekId,$gameId,$modelId);
        }
        $game['gameIsOpen'] = 1;

        return $game;
    }

    //加入场赛用户
    function addModelGamePlay($game,$user)
    {

        if ( !$game || !is_array($game) || !$user || !is_array($user) ) { return false; }
        $now = time();
        $uid = $user['uid'];
        $modelId = $game['modelId'];
        $roomId = $game['roomId'];
        $weekId = $game['weekId'];
        $gameId = $game['gameId'];
        $gamesId = $game['gamesId'];

        $game['gamePerson']++;
        $game['gamePlay']++;

        $person = array( 'uid' => $uid );
        $res = $this->redis->hset($this->key_model_gameplay_.$gamesId,$uid,$person);
        if(!$res ) { return false; }

        $res = $this->setRoomGame($roomId,$gamesId,$game,$modelId) ;
        if(!$res ) { return false; }

        return $game;
    }

    //获取场赛全部用户
    function getModelGamePlayAll($gamesId)
    {
        if ( !$gamesId ) { return false;}
        return $this->redis->hgetall($this->key_model_gameplay_.$gamesId);
    }
    //获取场赛用户
    function getModelGamePlay($gamesId,$uid)
    {
        if ( !$gamesId || !$uid )  { return false;}
        return $this->redis->hget($this->key_model_gameplay_.$gamesId,$uid);
    }

}
