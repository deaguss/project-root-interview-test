<?php

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

if ($path !== '/' && file_exists(__DIR__ . '/public' . $path)) {
    return false;
}

$_SERVER['REQUEST_URI'] = $uri;
require_once __DIR__ . '/public/index.php';
