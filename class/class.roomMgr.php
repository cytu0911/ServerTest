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

    function __construct( $rd, $mq )
    {
        $this->redis = $rd;
        $this->mysql = $mq;
    }

    public function enRoll($roomId, $user_arr)
    {
        if( isset($user_arr['uid'])  )
        {
            $key = "ROOM_QUEUE" . $roomId ;
            $info['uid'] =$user_arr['uid'];
            $this->redis->ladd($key,$info );
            return $this->getNum( $roomId );
        }
       return false;
    }

    public function remRoom($roomId)
    {
        $key = "ROOM_QUEUE" . $roomId ;
        $this->redis->lnot($key);
    }

    public function getNum( $roomId )
    {
        $key = "ROOM_QUEUE" . $roomId ;
        return $this->redis->llen( $key );
    }

    public function popNum($roomId, $num )
    {
        $totalNum = $this->getNum($roomId);
        if ($totalNum ==0 ) return false;
        $ret = array();
        $key = "ROOM_QUEUE" . $roomId ;
        $num = $num > $totalNum ? $totalNum : $num;

        for( $i =0 ; $i <$num; $i ++ )
        {
            $ret[$i] = $this->redis->lpop($key);
        }
        return $ret;
    }

    public function remRoomUser( $roomId, $user_arr )
    {
        if( isset($user_arr['uid'])  )
        {
            $key = "ROOM_QUEUE" . $roomId ;
            $info['uid'] =$user_arr['uid'];
            return $this->redis->ldel($key, json_encode( $info['uid'] ) );
        }
        return false;
    }


    public function setTable( $roomId,$gameId, $tableId, $table_arr )
    {
        $tname = "ROOM_TABLE" . $roomId . "_" . $gameId ;
        return $this->redis->hset($tname,$tableId,$table_arr);
    }

    public function  getTable( $roomId,$gameId, $tableId  )
    {
        $tname = "ROOM_TABLE" . $roomId . "_" . $gameId ;
        return $this->redis->hget($tname,$tableId);
    }

    public function  getTables( $roomId, $gameId  )
    {
        $tname = "ROOM_TABLE" . $roomId . "_" . $gameId ;
        return $this->redis->hgetall($tname);
    }



}
