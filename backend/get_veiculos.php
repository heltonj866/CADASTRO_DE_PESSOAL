<?php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
$stmt = $pdo->prepare("SELECT * FROM tb_veiculos WHERE militar_id = ? ORDER BY id DESC");
$stmt->execute([$_GET['militar_id']]);
echo json_encode(['status' => 'sucesso', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
?>