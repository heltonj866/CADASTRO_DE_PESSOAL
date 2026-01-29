<?php
// ARQUIVO: backend/toggle_homolog.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? null;

if (!$id) { echo json_encode(['status' => 'erro', 'msg' => 'ID inválido']); exit; }

try {
    // Inverte de forma segura (0 vir 1, 1 vira 0, NULL vira 1)
    $sql = "UPDATE tb_militares SET homologado = IF(homologado = 1, 0, 1) WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode(['status' => 'sucesso']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>