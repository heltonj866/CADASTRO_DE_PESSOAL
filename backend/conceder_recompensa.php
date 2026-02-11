<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Adaptação: Verifica 'usuario_role'
if (!isset($_SESSION['usuario_role']) || !in_array(strtolower($_SESSION['usuario_role']), ['admin', 'sargenteacao'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Sem permissão.']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['militar_id'] ?? 0;
// Adaptação: Usa usuario_idt como autor
$autor = $_SESSION['usuario_idt'] ?? 'Sistema';

try {
    $pdo->beginTransaction();
    
    // Verifica Saldo
    $stmt = $pdo->prepare("SELECT id FROM tb_alteracoes WHERE militar_id = ? AND categoria = 'ELOGIO' AND tipo_detalhe = 'FO+' AND consumido = 0 LIMIT 5");
    $stmt->execute([$id]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($ids) < 5) throw new Exception("Saldo insuficiente (Mínimo 5 FO+).");

    // Consome
    $listaIds = implode(',', $ids);
    $pdo->exec("UPDATE tb_alteracoes SET consumido = 1 WHERE id IN ($listaIds)");
    
    // Gera Dispensa
    $stmtIns = $pdo->prepare("INSERT INTO tb_alteracoes (militar_id, categoria, tipo_detalhe, data_fato, descricao, qtd_dias, registrado_por) VALUES (?, 'SAUDE', 'Dispensa Recompensa', CURDATE(), 'Recompensa automática (5 FO+ atingidos).', 1, ?)");
    $stmtIns->execute([$id, $autor]);

    $pdo->commit();
    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>