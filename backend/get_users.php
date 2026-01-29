<?php
// ARQUIVO: backend/get_users.php

// 1. DESLIGA ERROS VISUAIS (Para não sujar o JSON)
error_reporting(0);
ini_set('display_errors', 0);

// 2. INICIA O BUFFER (Segura o conteúdo)
ob_start();

require 'db_connect.php';
session_start();

// 3. LIMPA QUALQUER SUJEIRA ANTERIOR (Espaços, warnings, notices)
if (ob_get_length()) ob_clean();

// 4. DEFINE CABEÇALHO JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Verifica Admin
    if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
        throw new Exception("Acesso negado.");
    }

    // BUSCA TUDO (SELECT *)
    // O PHP vai pegar o que tiver lá. Se for tabela antiga, pega tudo. Se for nova, pega só o básico.
    $sql = "SELECT * FROM tb_usuarios";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    // TRATAMENTO MANUAL
    // Verifica linha por linha o que existe e preenche o que falta
    foreach($users as $u) {
        $data[] = [
            'id' => $u['id'],
            'identidade' => $u['identidade'],
            'role' => $u['role'],
            // Usa 'isset' para verificar se a coluna existe antes de tentar ler
            'ativo' => isset($u['ativo']) ? $u['ativo'] : 1,
            'posto_grad' => isset($u['posto_grad']) ? $u['posto_grad'] : '',
            'nome_guerra' => isset($u['nome_guerra']) ? $u['nome_guerra'] : 'Usuário',
            'subunidade' => isset($u['subunidade']) ? $u['subunidade'] : '---'
        ];
    }

    // Envia o JSON Limpo
    echo json_encode(['status' => 'sucesso', 'data' => $data]);

} catch (Exception $e) {
    // Se der erro grave, envia JSON de erro
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>