<?php
session_start();
require 'db_connect.php'; 
header('Content-Type: application/json');

// Debug para evitar tela branca em caso de erro
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. Segurança (Adaptada para seu login.php)
// O login.php usa 'usuario_role', não 'role'
if (!isset($_SESSION['usuario_role']) || !in_array(strtolower($_SESSION['usuario_role']), ['admin', 'sargenteacao'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Sem permissão.']); exit;
}

// 2. Recebe os dados
$id_militar = $_POST['s1_militar_id'] ?? '';
$categoria = $_POST['s1_cat'] ?? '';
$tipo = $_POST['s1_tipo'] ?? '';
$data = $_POST['s1_data'] ?? '';
$desc = $_POST['s1_desc'] ?? '';
$doc = $_POST['s1_doc'] ?? '';
$dias = $_POST['s1_dias'] ?? 0;

// O login.php não salva o nome, então usamos a Identidade ou 'Sistema'
$autor = $_SESSION['usuario_idt'] ?? 'Sistema';

if(empty($id_militar) || empty($categoria)) {
    echo json_encode(['status' => 'erro', 'msg' => 'Dados obrigatórios faltando.']); exit;
}

// 3. Upload
$arquivo_path = null;
if (isset($_FILES['s1_file']) && $_FILES['s1_file']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['s1_file']['name'], PATHINFO_EXTENSION));
    $novoNome = "doc_" . $id_militar . "_" . time() . "." . $ext;
    
    // Garante que a pasta existe
    if (!is_dir("../uploads/docs")) mkdir("../uploads/docs", 0777, true);
    
    if (move_uploaded_file($_FILES['s1_file']['tmp_name'], "../uploads/docs/" . $novoNome)) {
        $arquivo_path = $novoNome;
    }
}

// 4. Salva no Banco
try {
    $sql = "INSERT INTO tb_alteracoes (militar_id, categoria, tipo_detalhe, data_fato, descricao, documento_ref, qtd_dias, arquivo_path, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_militar, $categoria, $tipo, $data, $desc, $doc, $dias, $arquivo_path, $autor]);
    
    echo json_encode(['status' => 'sucesso']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro Banco: ' . $e->getMessage()]);
}
?>