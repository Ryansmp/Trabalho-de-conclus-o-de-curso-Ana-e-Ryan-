<?php

$host = 'localhost';
$db = 'oficina360';
$user = 'root';
$password = '';

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "";

} catch (PDOException $e) {

    exit;

}

