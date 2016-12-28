<?php
header('Access-Control-Allow-Origin: *');

include 'etc/config.php';
include 'etc/scripts.php';
include 'etc/init.php';

app()->run();
