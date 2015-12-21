<?php
/**
 * Created by PhpStorm.
 * User: youjoy8
 * Date: 2015/12/7
 * Time: 13:46
 * 负责管理用户数据
 */

class userMgr
{
    public $redis = null;
    public $mysql = null;

    function __construct($rd, $mq)
    {
        $this->redis = $rd;
        $this->mysql = $mq;
    }

    public function getUserByUid( $uid )
    {
        $uid = intval($uid);
        $sql = "select * from `". MY_USER_TABLE_NAME ."` where uid=". $uid ;
        $arr = $this->mysql->getLine($sql);
        if( empty($arr) ) return false;
        return $arr;
    }

    // $fdx = hostip + fd
    public function getUserByFd( $fd )
    {
        $uid = $this->getUidByFd($fd);
        if(! $uid ) return false;
        return $this->getUserInfo($uid);
    }


    public function getUidByFd($fd)
    {
        $tName = "FD_UID_TABLE" ;
        $uid = $this->redis->hget($tName,$fd);
        $ret = isset($uid['uid']) ? $uid['uid'] : false;
        return $ret;
    }

    public function setFdUid($fd,$uid)
    {
        $tName = "FD_UID_TABLE" ;
        $fd = strval($fd);
        $var['uid'] = $uid;
        return $this->redis->hset($tName,$fd,$var);
    }

    public function remFdUid($fd)
    {
        $tName = "FD_UID_TABLE" ;
        $fd = strval($fd);
        return $this->redis->hdel($tName, $fd );
    }

    public function setUserInfo($uid,$arrUser)
    {
        $tName ="USERINFO" ;
        $uid = strval($uid);
        return $this->redis->hset($tName,$uid,$arrUser);
    }

    public function getUserInfo($uid)
    {
        $tName = "USERINFO";
        $uid = strval($uid);
        return $this->redis->hget($tName,$uid);
    }

    public function initUser(& $arrUser)
    {
        $arrUser['isInGame']    = false ;
        $arrUser['modelID']     = 0;
        $arrUser['roomID']      = 0;
        $arrUser['tableID']     = 0;
        $arrUser['coins']       = 10000;
        $arrUser['tablePos']    = 0;
        $arrUser['fd']    = 0;
        $arrUser['isRobot']    = 0;

    }
}