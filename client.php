<?php 

require_once "util/util.php";

for ($x=0; $x<=16; $x++) {
  
    $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $client->on("connect", function(swoole_client $cli) {
        $data = array();
        $data["name"] = 1; $data["passwd"] = 1;

        $msg = is_array($data) ? json_encode($data) : $data;
        $len = num2UInt32Str(strlen($msg) + 12);
        $cmd = num2UInt32Str(intval(1));
        $sender = num2UInt32Str(intval(2));
        $receiver = num2UInt32Str(intval(3));

        $pack = $len.$cmd.$sender.$receiver.$msg;
        $cli->send($pack);
    });
    $client->on("receive", function(swoole_client $cli, $data){
        echo "Receive: $data";
    });
    $client->on("error", function(swoole_client $cli){
        echo "error\n";
    });
    $client->on("close", function(swoole_client $cli){
        echo "Connection close\n";
    });
    $client->connect('127.0.0.1', 9501);
}

/*
$dir = dirname(__FILE__);

require_once $dir . "/class.mysql.php";
require_once $dir . "/class.redis.php";

define("RD_HOST","127.0.0.1");
define("RD_PORT",6379);

//phpinfo();
$rd = new RD();
$rd->setnx("who","cytu");

$db = new DB("127.0.0.1",3306,"root","123456","doudizhu","utf-8");
$res = $db->getData("SELECT * FROM `user_info` LIMIT 0 , 30 ");
print_r($res);  */




