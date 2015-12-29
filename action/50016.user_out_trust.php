<?php

$uid = $user['uid'];
$modelId = $user['modelId'];
$roomId = $user['roomId'];
$tableId= $user['tableId'];
$seatId = $user['seatId'];
$MODELS = include(ROOT.'/conf/rooms.php');
if ( !in_array($roomId,array_keys($MODELS[$modelId])) || !$tableId )
{
	debug("解托用户失效[$fd|$uid|$tableId|$seatId] roomId=$roomId");
	goto end;
}

//获取牌桌
$table = $this->model->getTableInfo($tableId);
if ( !$table )
{
	debug("解托牌桌失效[$fd|$uid|$tableId|$seatId] client-".__LINE__);
}
//校验状态
elseif ( !in_array($table['state'], array(3,4,5,6)) )
{
	debug("解托网络延迟[$fd|$uid|$tableId|$seatId] state3456=".$table['state']);
}
//执行解托 0主动解托
else
{
	//debug("用户开始解托[$fd|$uid|$tableId|$seatId]");
	$res = $this->USER_DETRUST( $table, $seatId, 0 );
}


end:{}