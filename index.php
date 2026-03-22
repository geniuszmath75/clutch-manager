<?php

use core\Routing;

require_once("core/Routing.php");

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);