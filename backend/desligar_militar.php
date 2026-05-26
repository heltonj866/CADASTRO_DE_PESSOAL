<?php
// ARQUIVO: backend/desligar_militar.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

$id = $_POST['militar_id'] ?? null;
$arquivo = $_FILES['nada_consta'] ?? null;

// Verifica se o ID chegou e se o ficheiro foi anexado
if (!$id || !$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'erro', 'msg' => 'ID inválido ou ficheiro PDF não anexado.']);
    exit;
}

// Bloqueia tentativas de subir vírus ou imagens (só aceita PDF)
$extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
if ($extensao !== 'pdf') {
    echo json_encode(['status' => 'erro', 'msg' => 'O arquivo de "Nada Consta" tem de ser obrigatoriamente um PDF.']);
    exit;
}

// Cria um nome único para o PDF e define a pasta (aproveitamos a de documentos)
$novoNome = "nada_consta_militar_" . $id . "_" . time() . ".pdf";
$caminhoDestino = "../uploads/documentos/" . $novoNome;

// Cria a pasta caso ainda não exista
if (!is_dir("../uploads/documentos/")) {
    mkdir("../uploads/documentos/", 0777, true);
}

// Tenta mover o PDF para a pasta e atualizar a base de dados
if (move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
    try {
        $sql = "UPDATE tb_militares SET status_ativo = 0, pdf_nada_consta = ?, data_desligamento = CURDATE() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$novoNome, $id]);
        
        echo json_encode(['status' => 'sucesso', 'msg' => 'Militar desligado com sucesso. Histórico mantido.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'erro', 'msg' => 'Erro no banco: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'erro', 'msg' => 'Falha ao guardar o ficheiro no servidor.']);
}
?>