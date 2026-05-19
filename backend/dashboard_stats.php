<?php
// ARQUIVO: backend/dashboard_stats.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    $mils = $pdo->query("SELECT COUNT(*) FROM tb_militares")->fetchColumn();
    $veics = $pdo->query("SELECT COUNT(*) FROM tb_veiculos")->fetchColumn();
    // Agora conta veículos com homologado = 0 na tabela de veículos
    $pendentes = $pdo->query("SELECT COUNT(*) FROM tb_veiculos WHERE homologado = 0")->fetchColumn();

    echo json_encode(['status' => 'sucesso', 'militares' => $mils, 'veiculos' => $veics, 'pendentes' => $pendentes]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>