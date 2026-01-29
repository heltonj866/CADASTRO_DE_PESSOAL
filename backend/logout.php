<?php
// ARQUIVO: backend/logout.php
session_start();
session_destroy(); // AQUI ESTÁ A MÁGICA: Destrói os dados do servidor
header('Content-Type: application/json');
echo json_encode(['status' => 'sucesso']);
?>