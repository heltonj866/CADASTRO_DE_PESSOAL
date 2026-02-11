<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Segurança
if (!isset($_SESSION['usuario_role']) || !in_array(strtolower($_SESSION['usuario_role']), ['admin', 'sargenteacao'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Sem permissão.']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

try {
    // Apaga o registro
    $stmt = $pdo->prepare("DELETE FROM tb_alteracoes WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['status' => 'sucesso']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>