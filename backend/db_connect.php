<?php
// ARQUIVO: backend/db_connect.php

// --- CONFIGURAÇÃO DE AMBIENTE ---
// true  = Seu Computador
// false = Servidor do Batalhão
$em_desenvolvimento = true; 

if ($em_desenvolvimento) {
    // SEU COMPUTADOR (XAMPP)
    $host = 'localhost';
    $db   = 'sismil_db'; // <--- CORRIGIDO: Nome correto do seu banco
    $user = 'root';
    $pass = '';
} else {
    // SERVIDOR DO QUARTEL (Preencher na implantação)
    $host = 'localhost';
    $db   = 'sismil_prod';
    $user = 'admin_sismil';
    $pass = 'senha_servidor';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opções originais para garantir compatibilidade
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Retorna JSON para o JS não travar com tela branca
    header('Content-Type: application/json; charset=utf-8');
    
    $msg = $em_desenvolvimento 
        ? 'Erro Técnico (Dev): ' . $e->getMessage() 
        : 'Erro de conexão com o Banco de Dados.';

    echo json_encode(['status' => 'erro', 'msg' => $msg]);
    exit;
}
?>