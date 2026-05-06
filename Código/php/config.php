<?php
$host = 'trolley.proxy.rlwy.net';
$db = 'oficina360';
$user = 'root';
$password = 'rSUcnUdLsveZesxJUOdUlcIYyNHXyzaC';
$port = '54353';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
