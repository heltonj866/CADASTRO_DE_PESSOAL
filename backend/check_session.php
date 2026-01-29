<?php
// ARQUIVO: backend/check_session.php
header('Content-Type: application/json');
session_start(); // Inicia a verificação da sessão

// Verifica se existe um ID de usuário salvo no servidor
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_role'])) {
    // Se existir, devolve "logado" e o nível de acesso (admin, user, etc)
    echo json_encode([
        'status' => 'logado',
        'role' => $_SESSION['usuario_role'],
        'nome' => $_SESSION['usuario_nome'] ?? 'Militar'
    ]);
} else {
    // Se não existir, devolve "não logado"
    echo json_encode(['status' => 'nao_logado']);
}
?>