<?php

//参数整理
$doLord = $params['grabLord'];
if ( !in_array($doLord,range(1,2)) )
{
	$res = closeToFd( $fd, "抢庄参数无效 params=".json_encode($params) );
	goto end;
}

$uid = $user['uid'];
$modelId = $user['modelId'];
$roomId = $user['roomId'];
$tableId= $user['tableId'];
$seatId = $user['seatId'];
$MODELS = include(ROOT.'/conf/rooms.php');
if ( !in_array($roomId,array_keys($MODELS[$modelId])) || !$tableId )
{
	debug("抢庄用户失效[$fd|$uid|$tableId|$seatId] roomId=$roomId");
	goto end;
}

$res = $this->AUTO_GRAB_LORD($tableId, $user, $doLord);


end:{}