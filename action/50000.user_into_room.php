<?php

//参数整理
$modelId = isset($params['modelId']) ? intval($params['modelId']) : 0;
$roomId = isset($params['roomId']) ? intval($params['roomId']) : 1004;
$isContinue = isset($params['isContinue']) ? intval($params['isContinue']) : 0;//是否重返牌桌
if ( !$roomId )
{
	$res = closeToFd( $fd, "进房参数无效 params=".json_encode($params) );
	goto end;
}
$roomConf = array();
$MODELS = include(ROOT.'/conf/rooms.php');
foreach ( $MODELS as $k => $v )
{
	if ( isset($v[$roomId]) ) {
		$modelId = $k;
		$roomConf = $MODELS[$modelId][$roomId];
		break;
	}
}
if ( !$roomConf )
{
	$res = closeToFd( $fd );
	goto end;
}

$code = 1015;//进入房间
$data['modelId'] = $modelId ;
$data['roomId'] = $roomId;
$data['isGaming'] = 0;	//不在游戏
$data['isContinue'] = 0;//不返牌桌
$data['baseCoins'] = $roomConf['baseCoins'];
$data['rate'] = $roomConf['rate'];
$data['rateMax'] = $roomConf['rateMax'];
$data['limitCoins'] = $roomConf['limitCoins'];
$data['rake'] = $roomConf['rake'];
$data['gameBombAdd'] = $roomConf['gameBombAdd'];
$res = sendToFd($fd, $cmd, $code, $data);


end:{}