<?php

require_once('config.php');
require_once('class.php');
require_once('functions.php');

session_start();

try {
    $dsn = 'mysql:dbname='.$config['dbname'].';host='.$config['dbhost'].';charset=utf8';
    $db = new PDO($dsn, $config['user'], $config['password']);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de connexion : '.$e->getMessage());
}

if(!isset($_SESSION['combat'])) {
    $_SESSION['combat'] = [];
}
