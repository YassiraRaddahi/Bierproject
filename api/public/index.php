<?php
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);

require_once $root . '/src/database/conn.php';
require_once $root . '/src/controllers/BierController.php';
require_once $root . '/src/routes.php';