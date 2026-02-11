<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// Segurança
if (!isset($_SESSION['usuario_role']) || !in_array(strtolower($_SESSION['usuario_role']), ['admin', 'sargenteacao'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Sem permissão.']); exit;
}

// Recebe os dados
$id = $_POST['s1_edit_id'] ?? 0; // ID do registro para editar
$cat = $_POST['s1_cat'];
$tipo = $_POST['s1_tipo'];
$data = $_POST['s1_data'];
$desc = $_POST['s1_desc'];
$doc = $_POST['s1_doc'] ?? '';
$dias = $_POST['s1_dias'] ?? 0;

try {
    // Atualiza os dados
    $sql = "UPDATE tb_alteracoes SET categoria=?, tipo_detalhe=?, data_fato=?, descricao=?, documento_ref=?, qtd_dias=? WHERE id=?";
    $params = [$cat, $tipo, $data, $desc, $doc, $dias, $id];
    
    // Se enviou arquivo novo, atualiza o caminho também
    if (isset($_FILES['s1_file']) && $_FILES['s1_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['s1_file']['name'], PATHINFO_EXTENSION));
        $novoNome = "doc_" . $id . "_" . time() . "." . $ext;
        if (!is_dir("../uploads/docs")) mkdir("../uploads/docs", 0777, true);
        if (move_uploaded_file($_FILES['s1_file']['tmp_name'], "../uploads/docs/" . $novoNome)) {
            $sql = "UPDATE tb_alteracoes SET categoria=?, tipo_detalhe=?, data_fato=?, descricao=?, documento_ref=?, qtd_dias=?, arquivo_path=? WHERE id=?";
            $params = [$cat, $tipo, $data, $desc, $doc, $dias, $novoNome, $id];
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['status' => 'sucesso']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>