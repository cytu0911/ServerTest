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
   // private $key_userinfo = "KEY_USERINFO";

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
        $arrUser['gameIds']    = array();
    }

    //判断用户是否报名了该场次比赛
    public function hasSignUp($uid, $gameId)
    {
        $user =  getUser( $uid );
        $Ids = $user['gameIds'];
        return in_array( $gameId,$Ids );
    }

    //用户报名赛场
    public function addGameId($uid ,$gameId)
    {
        $user =  getUser( $uid );
        $Ids = $user['gameIds'];
        $Ids[] = $gameId;
        $gameids = array(
            'gameIds' => $Ids
        );
        setUser($uid, $gameids );

    }


    public function delGameId($uid, $gameId)
    {
        $user =  getUser( $uid );
        $Ids = $user['gameIds'];
        unset( $Ids[ array_search($gameId,$Ids) ]  );
        $gameids = array(
            'gameIds' => $Ids
        );
        setUser($uid, $gameids );
    }
}