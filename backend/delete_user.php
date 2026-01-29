<?php
// ARQUIVO: backend/delete_user.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    // CORREÇÃO: usuario_role
    if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
        throw new Exception("Acesso negado.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $idParaApagar = $input['id'] ?? null;
    $idLogado = $_SESSION['usuario_id'] ?? null;

    if (!$idParaApagar) throw new Exception("ID inválido.");
    if ($idParaApagar == $idLogado) throw new Exception("Não apague a si mesmo!");

    // Trava do último admin
    $stmtCheck = $pdo->prepare("SELECT role FROM tb_usuarios WHERE id = ?");
    $stmtCheck->execute([$idParaApagar]);
    $alvo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($alvo && $alvo['role'] === 'admin') {
        $total = $pdo->query("SELECT COUNT(*) FROM tb_usuarios WHERE role='admin'")->fetchColumn();
        if ($total <= 1) throw new Exception("Não pode apagar o último admin.");
    }

    $pdo->prepare("DELETE FROM tb_usuarios WHERE id = ?")->execute([$idParaApagar]);
    
    echo json_encode(['status' => 'sucesso', 'msg' => 'Excluído com sucesso.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>