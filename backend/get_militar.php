<?php
// ARQUIVO: backend/get_militar.php
// Cabeçalhos para evitar cache do navegador e garantir JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require 'db_connect.php';

$id = $_GET['id'] ?? '';

if(empty($id)) {
    echo json_encode(['status' => 'erro', 'msg' => 'ID não informado']);
    exit;
}

try {
    // Busca direta e simples
    $sql = "SELECT * FROM tb_militares WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (ob_get_length()) ob_clean(); // Limpa sujeira do buffer

    if ($dados) {
        // Converte NULL em VAZIO para não quebrar o Javascript
        foreach ($dados as $key => $value) {
            if (is_null($value)) $dados[$key] = "";
        }
        echo json_encode(['status' => 'sucesso', 'dados' => $dados]);
    } else {
        echo json_encode(['status' => 'erro', 'msg' => 'Militar não encontrado no banco.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro SQL: ' . $e->getMessage()]);
}
?>