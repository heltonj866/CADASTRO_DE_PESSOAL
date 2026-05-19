<?php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    function getPost($key) {
        $val = $_POST[$key] ?? null;
        return ($val === '' || $val === 'null') ? null : trim($val);
    }

    $id = getPost('id_militar') ?? getPost('militarId'); 
    $cpf = getPost('cpf');

    if (!$cpf) throw new Exception("O CPF é obrigatório.");

    // TRATAMENTO DA FOTO
    $foto_path = null;
    $sqlFoto = "";
    if (isset($_FILES['foto_militar']) && $_FILES['foto_militar']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../uploads/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['foto_militar']['name'], PATHINFO_EXTENSION));
        $novo_nome = uniqid('foto_') . '.' . $ext;
        if (move_uploaded_file($_FILES['foto_militar']['tmp_name'], $dir . $novo_nome)) {
            $foto_path = $novo_nome;
            $sqlFoto = ", foto_path = :foto";
        }
    }

    // TRATAMENTO DO PDF DA CNH
    $pdf_hab_path = null;
    $sqlPdfHab = "";
    if (isset($_FILES['pdf_habilitacao']) && $_FILES['pdf_habilitacao']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pdf_habilitacao']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $novo_nome_pdf_hab = uniqid('cnh_') . '.pdf';
            if (move_uploaded_file($_FILES['pdf_habilitacao']['tmp_name'], "../uploads/documentos/" . $novo_nome_pdf_hab)) {
                $pdf_hab_path = $novo_nome_pdf_hab;
                $sqlPdfHab = ", pdf_habilitacao = :pdf_hab";
            }
        }
    }

    $dados = [
        ':identidade' => $cpf, ':posto_grad' => getPost('posto_grad'), ':nome_guerra' => getPost('nome_guerra'),
        ':numero' => getPost('numero'), ':subunidade' => getPost('subunidade'), ':pelotao' => getPost('pelotao'),
        ':secao' => getPost('secao'), ':nome_completo' => getPost('nome_completo'), ':qmg' => getPost('qmg'),
        ':dt_nascimento' => getPost('dt_nascimento'), ':tipo_sanguineo' => getPost('tipo_sanguineo'),
        ':dt_praca' => getPost('dt_praca'), ':idt_militar' => getPost('idt_militar'),
        ':email' => getPost('email'), ':celular_princ' => getPost('celular_princ'),
        ':celular_sec' => getPost('celular_sec'), ':nome_resp' => getPost('nome_resp'),
        ':tel_resp' => getPost('tel_resp'), ':cep' => getPost('cep'),
        ':endereco' => getPost('endereco'), ':num_residencia' => getPost('num_residencia'),
        ':bairro' => getPost('bairro'), ':cidade' => getPost('cidade'), ':estado' => getPost('estado'),
        ':cat_cnh' => getPost('cat_cnh'), ':validade_cnh' => getPost('validade_cnh')
    ];

    if ($id) {
        $dados[':id'] = $id;
        if ($foto_path) $dados[':foto'] = $foto_path;
        if ($pdf_hab_path) $dados[':pdf_hab'] = $pdf_hab_path;
        $sql = "UPDATE tb_militares SET identidade=:identidade, posto_grad=:posto_grad, numero=:numero, nome_guerra=:nome_guerra, subunidade=:subunidade, pelotao=:pelotao, secao=:secao, nome_completo=:nome_completo, qmg=:qmg, dt_nascimento=:dt_nascimento, tipo_sanguineo=:tipo_sanguineo, dt_praca=:dt_praca, idt_militar=:idt_militar, email=:email, celular_princ=:celular_princ, celular_sec=:celular_sec, nome_resp=:nome_resp, tel_resp=:tel_resp, cep=:cep, endereco=:endereco, num_residencia=:num_residencia, bairro=:bairro, cidade=:cidade, estado=:estado, cat_cnh=:cat_cnh, validade_cnh=:validade_cnh $sqlFoto $sqlPdfHab WHERE id=:id";
    } else {
        $dados[':foto'] = $foto_path; $dados[':pdf_hab'] = $pdf_hab_path;
        $sql = "INSERT INTO tb_militares (identidade, posto_grad, numero, nome_guerra, subunidade, pelotao, secao, nome_completo, qmg, dt_nascimento, tipo_sanguineo, dt_praca, idt_militar, email, celular_princ, celular_sec, nome_resp, tel_resp, cep, endereco, num_residencia, bairro, cidade, estado, cat_cnh, validade_cnh, foto_path, pdf_habilitacao, status) VALUES (:identidade, :posto_grad, :numero, :nome_guerra, :subunidade, :pelotao, :secao, :nome_completo, :qmg, :dt_nascimento, :tipo_sanguineo, :dt_praca, :idt_militar, :email, :celular_princ, :celular_sec, :nome_resp, :tel_resp, :cep, :endereco, :num_residencia, :bairro, :cidade, :estado, :cat_cnh, :validade_cnh, :foto, :pdf_hab, 'ativo')";
    }
    $pdo->prepare($sql)->execute($dados);
    echo json_encode(['status' => 'sucesso', 'msg' => 'Salvo com sucesso!']);
} catch (Exception $e) { echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]); }
?>