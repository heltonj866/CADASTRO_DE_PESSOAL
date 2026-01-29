<?php
// ARQUIVO: backend/delete_militar.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
session_start();

// 1. SEGURANÇA ATUALIZADA
// Verifica se é admin OU sargenteacao
$role = $_SESSION['usuario_role'] ?? '';
$permitidos = ['admin', 'sargenteacao'];

if (!in_array($role, $permitidos)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Acesso negado. Permissão insuficiente.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(['status' => 'erro', 'msg' => 'ID não informado.']);
    exit;
}

try {
    // 2. RECUPERAR A FOTO
    $stmt = $pdo->prepare("SELECT foto_path FROM tb_militares WHERE id = ?");
    $stmt->execute([$id]);
    $militar = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. APAGAR O MILITAR
    $stmtDel = $pdo->prepare("DELETE FROM tb_militares WHERE id = ?");
    
    if ($stmtDel->execute([$id])) {
        // 4. APAGAR FOTO
        if ($militar && !empty($militar['foto_path'])) {
            $arquivo = '../uploads/' . $militar['foto_path'];
            if (file_exists($arquivo)) {
                unlink($arquivo);
            }
        }
        echo json_encode(['status' => 'sucesso', 'msg' => 'Excluído com sucesso.']);
    } else {
        throw new Exception("Erro ao excluir do banco.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>