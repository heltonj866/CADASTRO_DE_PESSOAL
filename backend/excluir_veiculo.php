<?php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
$data = json_decode(file_get_contents("php://input"), true);
$pdo->prepare("DELETE FROM tb_veiculos WHERE id = ?")->execute([$data['id']]);
echo json_encode(['status' => 'sucesso']);
?>