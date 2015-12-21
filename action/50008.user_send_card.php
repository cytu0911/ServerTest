<?php

//参数整理
$cardstype = cardsCheck( array_values($params['sendCards']) );
$sendCards = $cardstype['cards'];
if ( !$sendCards ) {
	$res = closeToFd( $fd, "出牌参数无效 params=".json_encode($params) );
	goto end;
}

$uid = $user['uid'];
$modelId = $user['modelId'];
$roomId = $user['roomId'];
$tableId= $user['tableId'];
$seatId = $user['seatId'];
$MODELS = include(ROOT.'/conf/rooms.php');
$GAME = include(ROOT.'/conf/games.php');
if ( !in_array($roomId,array_keys($MODELS[$modelId])) || !$tableId )
{
	debug("出牌用户失效[$fd|$uid|$tableId|$seatId] roomId=$roomId");
	goto end;
}

//获取牌桌
$table = $this->model->getTableInfo( $tableId );
if ( !$table )
{
	debug("出牌牌桌失效[$fd|$uid|$tableId|$seatId] params=".json_encode($params));
	goto end;
}
//校验牌桌状态、席位轮流、用户托管
elseif ( $table['state'] != 6 || $table['turnSeat'] != $seatId || $table['seat'.$seatId.'trust'] )
{
	debug("出牌网络延迟[$fd|$uid|$tableId|$seatId] state6=".$table['state']." turn".$table['turnSeat']."=".$seatId." trust=".$table['seat'.$seatId.'trust']);
	goto end;
}
//校验手牌
elseif ( !$table['seat'.$seatId.'cards'] )
{
	gerr("出牌手牌无效[$fd|$uid|$tableId|$seatId] table=".json_encode($table));
	goto end;
}

//牌型检测
if( $cardstype['type'] < 1)
{
	$code = 1020;//
	$data = array();//['error']=>3,['msg']=>"[ERROR]牌型",['cardType']=>$cardstype['type'],['sendCards']=>$sendCards,
	$res = $this->model->sendToUser( $user, $cmd, $code, $data );
	goto end;
}
//牌库检测
$hand = $table['seat'.$seatId.'cards'];
if ( array_diff( $sendCards, $hand) )
{
	$code = 1020;//
	$data = array();//['error']=>1,['msg']=>"[ERROR]牌面",['sendCards']=>$sendCards,['playerCard']=>$hand,
	$res = $this->model->sendToUser( $user, $cmd, $code, $data );
	goto end;
}
//大小检测	有前牌、非自己、且自己牌小
$card = new Card;
if( $table['lastCards'] && $table['lastCall'] != $seatId && $card->cardsCompare( $card->cardsDec($card->cardsEnTranse($table['lastCards'])), $card->cardsDec($card->cardsEnTranse($sendCards))) != 2 )
{
	$code = 1013;//
	$data = array();//['error']=>2,['msg']=>"[ERROR]牌小",['sendCards']=>$sendCards,['lastCards']=>$table['lastCards'],
	$res = $this->model->sendToUser( $user, $cmd, $code, $data );
	goto end;
}

//debug("用户选择出牌[$fd|$uid|$tableId|$seatId]");

//通知牌桌: 某人出牌
$code = 1017;
$data = array();
$data['callId'] = $seatId;
$data['sendCards'] = $sendCards;
$data['cardType'] = intval( $cardstype['type'] );
$res = $this->model->sendToTable( $table, $cmd, $code, $data, __LINE__ );

//检测牌型是否影响倍率
$rate = isset( $GAME['rate_cardstype'.intval( $cardstype['type'])]) ? $GAME['rate_cardstype'.intval( $cardstype['type'])] : 1;
if ( $rate > 1 )
{
	$new['rate'] = $table['rate'] = $this->TABLE_NEW_RATE( $table, $seatId, $rate );
}
//已打出的牌
$new['outCards'] = $table['outCards'] = is_array( $table['outCards']) ? array_merge( $table['outCards'], $sendCards) : $sendCards;
//手牌
$new['seat'.$seatId.'cards'] = $table['seat'.$seatId.'cards'] = array_values(array_diff( $table['seat'.$seatId.'cards'], $sendCards) );
//记录出牌次数，用于春天/反春判定
$new['seat'.$seatId.'sent'] = ++$table['seat'.$seatId.'sent'];
//轮转下家
$new['turnSeat'] = $table['turnSeat'] = $this->model->getSeatNext( $seatId );
//上把出牌人
$new['lastCall'] = $table['lastCall'] = $seatId;
//上把出牌内容
$new['lastCards'] = $table['lastCards'] = $sendCards;
//重设不跟次数
$new['noFollow'] = $table['noFollow'] = 0;

//更新牌桌信息
$res = $this->model->setTableInfo( $tableId, $new );

// 新版活动任务
/* $_num = in_array($cardstype['type'], array('88','99'))?'88':(in_array($cardstype['type'], array('8','9','10'))?'8910':($cardstype['type']=='7'?('7'.count($sendCards)):''));
if ( !$_num ) {
	if ( $cardstype['type']==1 ) {
		$_send = $sendCards[0];
		$_num = in_array($_send, array('12','22','32','42')) ? '1_2' : '';
	}
}
if ( $_num ) {
	$userinfo = $user;
	$tesk = new tesk($this->mysql, $this->redis, $accode, $action);
	$adduinfo = $tesk->execute('user_pct_'.$_num, $userinfo, array(), 1, $table);
	if ( $adduinfo ) {
		$res = $this->model->incUserInfo($uid, $adduinfo, 6);//6任务直奖
		$res && $userinfo = array_merge($userinfo, $res);
	}
	$user = $userinfo;
	
}   */

//检测手牌已经出完，执行GAME_OVER，并return
if ( count( $table['seat'.$seatId.'cards']) == 0 )
{
	if ( $modelId )
	{
		$res = $this->MODEL_GAME_OVER( $table, 1 );
	}
	else
	{
		$res = $this->TABLE_GAME_OVER( $table );
	}
	goto end;
}

//轮到打牌
$res = $this->TURN_PLAY_CARD( $table );


end:{}