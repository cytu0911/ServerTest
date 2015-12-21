<?php


$code = 102;
$errno = 0;
$error = '操作成功。';

$data['errno'] = $errno;
$data['gamePerson'] = 10;
$data['gamePersonAll'] = 30;

$res = sendToFd($fd, $cmd, $code, $data);

end:{}