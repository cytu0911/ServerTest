<?php

define("ROOT", __DIR__);
require_once "server.php" ;


$server = new Server;
$server->start();
