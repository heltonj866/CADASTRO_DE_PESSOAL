<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Adaptação: Verifica 'usuario_role'
if (!isset($_SESSION['usuario_role']) || !in_array(strtolower($_SESSION['usuario_role']), ['admin', 'sargenteacao'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Acesso negado.']); exit;
}

$id = $_GET['id'] ?? 0;

try {
    $sql = "SELECT * FROM tb_alteracoes WHERE militar_id = ? ORDER BY data_fato DESC, id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'sucesso', 'dados' => $dados]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>