<?php
// ARQUIVO: backend/update_user.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';
session_start();

if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
    echo json_encode(['status' => 'erro', 'msg' => 'Acesso negado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['edit_id'] ?? '';
$role = $input['new_user_role'] ?? 'user';
$nova_senha = $input['new_user_pass'] ?? '';

if (empty($id)) {
    echo json_encode(['status' => 'erro', 'msg' => 'ID não encontrado.']);
    exit;
}

try {
    if (empty($nova_senha)) {
        $sql = "UPDATE tb_usuarios SET role=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $id]);
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE tb_usuarios SET role=?, senha_hash=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $hash, $id]);
    }
    echo json_encode(['status' => 'sucesso', 'msg' => 'Atualizado com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro: ' . $e->getMessage()]);
}
?>