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
