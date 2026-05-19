<?php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    $id = $_POST['veiculo_id'] ?? null;
    $militar_id = $_POST['v_militar_id'] ?? null;
    $placa = strtoupper(trim($_POST['v_placa'] ?? ''));

    if (!$militar_id || !$placa) throw new Exception("Identificação do militar e placa são obrigatórios.");

    $pdf_path = null;
    $sqlPdf = "";
    if (isset($_FILES['v_pdf']) && $_FILES['v_pdf']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../uploads/documentos/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['v_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') throw new Exception("O documento deve ser PDF.");
        $pdf_path = uniqid('crlv_') . '.pdf';
        if (move_uploaded_file($_FILES['v_pdf']['tmp_name'], $dir . $pdf_path)) {
            $sqlPdf = ", pdf_veiculo = :pdf";
        }
    }

    $dados = [
        ':militar_id' => $militar_id,
        ':tipo' => $_POST['v_tipo'] ?? 'Carro',
        ':placa' => $placa,
        ':marca' => $_POST['v_marca'] ?? '',
        ':modelo' => $_POST['v_modelo'] ?? '',
        ':cor' => $_POST['v_cor'] ?? '',
        ':validade' => (!empty($_POST['v_validade'])) ? $_POST['v_validade'] : null
    ];

    if ($id && $id !== "") {
        $dados[':id'] = $id;
        if ($pdf_path) $dados[':pdf'] = $pdf_path;
        $sql = "UPDATE tb_veiculos SET tipo_veiculo=:tipo, placa=:placa, marca=:marca, modelo=:modelo, cor=:cor, validade_crlv=:validade, homologado=0 $sqlPdf WHERE id=:id";
    } else {
        $dados[':pdf'] = $pdf_path;
        $sql = "INSERT INTO tb_veiculos (militar_id, tipo_veiculo, placa, marca, modelo, cor, validade_crlv, pdf_veiculo, homologado) VALUES (:militar_id, :tipo, :placa, :marca, :modelo, :cor, :validade, :pdf, 0)";
    }
    $pdo->prepare($sql)->execute($dados);
    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) { echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]); }
?>