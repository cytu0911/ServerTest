<?php

$code = 104;
$errno = 0;
$error = '操作成功。';

date_default_timezone_set('PRC');

//参数整理
$modelId = isset($params['modelId']) ? intval($params['modelId']) : 0;//赛事id
$roomId = isset($params['roomId']) ? intval($params['roomId']) : 1004;	//房间id
$weekId = intval(date("Ymd",time()-(date("N")-1)*86400));
$now = time();
$MODELS = include(ROOT.'/conf/rooms.php');

$inRoomNum = $this->roomMgr->enRoll($roomId, $user);

//获取本周最新场次
$game = $this->model->getModelRoomWeekGameLast($modelId,$roomId,$weekId);

$gameId = 1002;
//通知用户: 赛事报名
$data = array(
	"errno" => 0,
	"error" => "报名成功",
	"modelId" => $modelId,
	//"roomId" => $roomId,
	"weekId" => $weekId,			//场次编号(年+周)
	"gameId" => $gameId,			//场次编号(本周第n次)
	"gameInCoins" =>100,	//报名费coins
	"coins" => 1000,//处理完毕后的coins
);
$res = sendToFd( $fd, $cmd, $code, $data);

$gamePersonAll =  $MODELS[ $modelId ][$roomId]['gamePersonAll'];
$gameRealPeople = $this->roomMgr->getNum($roomId);



//加入报名
$gameNew = $this->model->addModelGamePlay($game,$user);

$this->ACT_MODEL_CHECK($gameNew);

/*
if(  $gameNew['gamePerson'] >= $gameNew['gamePersonAll'] )
{
	echo "222222222222222222222222222\n";
	$this->ACT_MODEL_CHECK($gameNew);
} */
//检查报名是否已满
/*
if ( $gameNew['gamePerson'] >= $gameNew['gamePersonAll'] )
{
	$this->ACT_MODEL_CHECK($gameNew);
}

//解事务锁
$res = delLock($lockId);
 */

end:{}