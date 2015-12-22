<?php

$uid = $user['uid'];
$modelId = $user['modelId'];
$roomId = $user['roomId'];
$tableId= $user['tableId'];
$seatId = $user['seatId'];
$MODELS = include(ROOT.'/conf/rooms.php');
if ( !in_array($roomId,array_keys($MODELS[$modelId])) || !$tableId )
{
	debug("不跟用户失效[$fd|$uid|$tableId|$seatId] roomId=$roomId");
	goto end;
}

//获取牌桌
$table = $this->model->getTableInfo( $tableId );
if ( !$table )
{
	debug("不跟牌桌失效[$fd|$uid|$tableId|$seatId]");
	goto end;
}
//校验牌桌状态、席位轮流、用户托管
elseif ( $table['state'] != 6 || $table['turnSeat'] != $seatId )
{
	debug("不跟网络延迟[$fd|$uid|$tableId|$seatId] state6=".$table['state']." turn".$table['turnSeat']."=".$seatId);
	goto end;
}
//校验手牌
elseif ( !$table['seat'.$seatId.'cards'] )
{
	gerr("不跟手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
	goto end;
}

//debug("用户选择不跟[$fd|$uid|$tableId|$seatId]");

//通知牌桌: 某人不跟
$code = 1018;//
$data = array();
$data['callId'] = $seatId;
$res = $this->model->sendToTable( $table, $cmd, $code, $data, __LINE__ );

//重设不跟次数
$newt['noFollow'] = ++$table['noFollow'];
//是否清空叫牌内容
if( $table['noFollow'] == 2 )
{
	$newt['noFollow'] = $table['noFollow'] = 0;
	$newt['lastCards'] = $table['lastCards'] = array();
}
//轮转下家
$newt['turnSeat'] = $table['turnSeat'] = $this->model->getSeatNext( $seatId );

//更新牌桌信息
$res = $this->model->setTableInfo( $tableId, $newt );
if ( !$res )
{
	gerr("不跟执行失败[$fd|$uid|$tableId|$seatId] setTableInfo( $tableId, ".json_encode($newt)." )");
	goto end;
}

//轮到打牌
$res = $this->TURN_PLAY_CARD($table);


end:{}