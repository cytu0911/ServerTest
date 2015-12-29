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

}
