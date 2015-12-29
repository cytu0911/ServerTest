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

//获取本周最新场次
$game = $this->model->getModelRoomWeekGameLast($modelId,$roomId,$weekId);
$gameId = isset($game["gamesId"]) ? $game["gamesId"] : "";

//判断用户是否报名了
$uid = isset($user["uid"]) ? $user["uid"] : "";

echo "gameid " . $gameId . "\n";
var_dump($user);

$bHasSignUp =  $this->userMgr->hasSignUp($uid, $gameId) ;

if( $bHasSignUp)
{
	//通知用户已经报名该场次比赛
	echo "已经报过名了\n";
	goto end;
}

//判断用户是否可报名
if( ! $this->roomMgr->canSignUp($roomId, $gameId ) )
{
	// 通知用户不可报名
	echo "通知用户不可报名\n";
	goto end;
}

//通知用户: 赛事报名
$data = array(
	"errno" => 0,
	"error" => "报名成功",
	"modelId" => $modelId,
	"weekId" => $weekId,			//场次编号(年+周)
	"gameId" => $gameId,			//场次编号(本周第n次)
	"gameInCoins" =>100,	//报名费coins
	"coins" => 1000,//处理完毕后的coins
);
$res = sendToFd( $fd, $cmd, $code, $data);

$MODELS = include(ROOT.'/conf/rooms.php');
$gamePersonAll =  $MODELS[ $modelId ][$roomId]['gamePersonAll'];


//加入报名

$gameNew = $this->model->addModelGamePlay($game,$user);
$this->userMgr->addGameId($uid ,$gameNew["gamesId"]);

echo "baoming  \n";
echo $gameNew["gamePerson"] . "  / " . $gameNew["gamePersonAll"]. "\n";
if( $gameNew["gamePerson"] >= $gameNew["gamePersonAll"] )
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