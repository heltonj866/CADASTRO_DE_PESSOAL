<?php
// ARQUIVO: backend/login.php
header('Content-Type: application/json');
require 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$identidade = $input['identidade'] ?? '';
$senha = $input['senha'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM tb_usuarios WHERE identidade = ? LIMIT 1");
    $stmt->execute([$identidade]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha_hash'])) {
        if ($user['ativo'] == 0) {
            echo json_encode(['status' => 'erro', 'msg' => 'Conta inativa.']);
            exit;
        }

        session_start();
        // PADRÃO DE SESSÃO: 'usuario_role'
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_role'] = $user['role']; 
        $_SESSION['usuario_idt'] = $user['identidade'];

        echo json_encode(['status' => 'sucesso', 'role' => $user['role']]);
    } else {
        echo json_encode(['status' => 'erro', 'msg' => 'Login ou senha incorretos.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro interno.']);
}
?>