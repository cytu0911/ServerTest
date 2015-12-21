<?php

$fdinfo['is_lock'] = 0;
//$res = setBind($fd, $fdinfo);

//通知牌桌: 竞技已准备
$code = 128;
$data = array();
$res = sendToFd($fd, $cmd, $code, $data);


end:{}