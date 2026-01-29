<?php
// ARQUIVO: backend/dashboard_stats.php
header('Content-Type: application/json');
require 'db_connect.php';

try {
    // 1. Total
    $sql1 = "SELECT COUNT(*) as total FROM tb_militares";
    // 2. Veículos (Tem placa)
    $sql2 = "SELECT COUNT(*) as frota FROM tb_militares WHERE placa IS NOT NULL AND placa != ''";
    // 3. Pendentes (Tem Placa E Não está homologado)
    $sql3 = "SELECT COUNT(*) as pendentes FROM tb_militares 
             WHERE (placa IS NOT NULL AND placa != '') 
             AND (homologado IS NULL OR homologado = 0)";

    $stmt1 = $pdo->query($sql1);
    $stmt2 = $pdo->query($sql2);
    $stmt3 = $pdo->query($sql3);

    echo json_encode([
        'status' => 'sucesso',
        'militares' => $stmt1->fetch()['total'],
        'veiculos' => $stmt2->fetch()['frota'],
        'pendentes' => $stmt3->fetch()['pendentes']
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>