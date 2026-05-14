<?php
// ARQUIVO: backend/save_militar.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    function getPost($key) {
        $val = $_POST[$key] ?? null;
        return ($val === '' || $val === 'null') ? null : trim($val);
    }

    $id = getPost('id_militar') ?? getPost('militarId'); 
    $cpf = getPost('cpf') ?? getPost('identidade');      

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

    // TRATAMENTO DOS PDFs (CNH e CRLV)
    $dir_docs = __DIR__ . '/../uploads/documentos/';
    if (!is_dir($dir_docs)) mkdir($dir_docs, 0777, true);

    $pdf_hab_path = null;
    $sqlPdfHab = "";
    if (isset($_FILES['pdf_habilitacao']) && $_FILES['pdf_habilitacao']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pdf_habilitacao']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $novo_nome_pdf_hab = uniqid('cnh_') . '.pdf';
            if (move_uploaded_file($_FILES['pdf_habilitacao']['tmp_name'], $dir_docs . $novo_nome_pdf_hab)) {
                $pdf_hab_path = $novo_nome_pdf_hab;
                $sqlPdfHab = ", pdf_habilitacao = :pdf_hab";
            }
        } else {
            throw new Exception("A Habilitação deve ser um arquivo PDF.");
        }
    }

    $pdf_veic_path = null;
    $sqlPdfVeic = "";
    if (isset($_FILES['pdf_veiculo']) && $_FILES['pdf_veiculo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pdf_veiculo']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $novo_nome_pdf_veic = uniqid('crlv_') . '.pdf';
            if (move_uploaded_file($_FILES['pdf_veiculo']['tmp_name'], $dir_docs . $novo_nome_pdf_veic)) {
                $pdf_veic_path = $novo_nome_pdf_veic;
                $sqlPdfVeic = ", pdf_veiculo = :pdf_veic";
            }
        } else {
            throw new Exception("O documento do veículo deve ser um arquivo PDF.");
        }
    }

    // OS DADOS (Nota: 'homologado' foi retirado de propósito desta lista para a S2)
    $dados = [
        ':identidade' => $cpf,
        ':posto_grad' => getPost('posto_grad'),
        ':nome_guerra' => getPost('nome_guerra'),
        ':numero' => getPost('numero'),
        ':subunidade' => getPost('subunidade'),
        ':pelotao' => getPost('pelotao'),
        ':secao' => getPost('secao'),
        ':nome_completo' => getPost('nome_completo'),
        ':qmg' => getPost('qmg'),
        ':dt_nascimento' => getPost('dt_nascimento'),
        ':tipo_sanguineo' => getPost('tipo_sanguineo'),
        ':dt_praca' => getPost('dt_praca'),
        ':idt_militar' => getPost('idt_militar'),
        ':email' => getPost('email'),
        ':celular_princ' => getPost('celular_princ'),
        ':celular_sec' => getPost('celular_sec'),
        ':nome_resp' => getPost('nome_resp'),
        ':tel_resp' => getPost('tel_resp'),
        ':cep' => getPost('cep'),
        ':endereco' => getPost('endereco'),
        ':num_residencia' => getPost('num_residencia'),
        ':bairro' => getPost('bairro'),
        ':cidade' => getPost('cidade'),
        ':estado' => getPost('estado'),
        ':cat_cnh' => getPost('cat_cnh'),
        ':validade_cnh' => getPost('validade_cnh'),
        ':tipo_veiculo' => getPost('tipo_veic') ?? getPost('tipo_veiculo'),
        ':placa' => getPost('placa'),
        ':modelo' => getPost('modelo'),
        ':cor' => getPost('cor'),
        ':validade_crlv' => getPost('validade_crlv')
    ];

    if ($id) {
        // --- EDIÇÃO DO MILITAR ---
        $dados[':id'] = $id;
        if ($foto_path) $dados[':foto'] = $foto_path;
        if ($pdf_hab_path) $dados[':pdf_hab'] = $pdf_hab_path;
        if ($pdf_veic_path) $dados[':pdf_veic'] = $pdf_veic_path;

        // Regra de Segurança: Verifica se a placa foi alterada
        $stmtCheck = $pdo->prepare("SELECT placa FROM tb_militares WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $veiculoAtual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $nova_placa = getPost('placa');
        $sqlResetHomologacao = "";
        
        // Se já tinha veículo e a placa mudou, derruba a homologação para 0
        if ($veiculoAtual && $veiculoAtual['placa'] !== $nova_placa) {
            $sqlResetHomologacao = ", homologado = 0";
        }

        $sql = "UPDATE tb_militares SET 
            identidade=:identidade, posto_grad=:posto_grad, numero=:numero, nome_guerra=:nome_guerra, 
            subunidade=:subunidade, pelotao=:pelotao, secao=:secao, nome_completo=:nome_completo, 
            qmg=:qmg, dt_nascimento=:dt_nascimento, tipo_sanguineo=:tipo_sanguineo, dt_praca=:dt_praca, 
            idt_militar=:idt_militar, email=:email, celular_princ=:celular_princ, celular_sec=:celular_sec, 
            nome_resp=:nome_resp, tel_resp=:tel_resp, cep=:cep, endereco=:endereco, num_residencia=:num_residencia, 
            bairro=:bairro, cidade=:cidade, estado=:estado, cat_cnh=:cat_cnh, validade_cnh=:validade_cnh, 
            tipo_veiculo=:tipo_veiculo, placa=:placa, modelo=:modelo, cor=:cor, validade_crlv=:validade_crlv
            $sqlFoto $sqlPdfHab $sqlPdfVeic $sqlResetHomologacao
            WHERE id=:id";
    } else {
        // --- NOVO CADASTRO ---
        $check = $pdo->prepare("SELECT id FROM tb_militares WHERE identidade = ?");
        $check->execute([$cpf]);
        if ($check->rowCount() > 0) throw new Exception("CPF já cadastrado.");

        $dados[':foto'] = $foto_path; 
        $dados[':pdf_hab'] = $pdf_hab_path; 
        $dados[':pdf_veic'] = $pdf_veic_path; 

        // Força "homologado = 0" no código SQL de Inserção
        $sql = "INSERT INTO tb_militares (
            identidade, posto_grad, numero, nome_guerra, subunidade, pelotao, secao, nome_completo, 
            qmg, dt_nascimento, tipo_sanguineo, dt_praca, idt_militar, email, celular_princ, celular_sec, 
            nome_resp, tel_resp, cep, endereco, num_residencia, bairro, cidade, estado, cat_cnh, 
            validade_cnh, tipo_veiculo, placa, modelo, cor, validade_crlv, homologado, foto_path, pdf_habilitacao, pdf_veiculo, status
        ) VALUES (
            :identidade, :posto_grad, :numero, :nome_guerra, :subunidade, :pelotao, :secao, :nome_completo, 
            :qmg, :dt_nascimento, :tipo_sanguineo, :dt_praca, :idt_militar, :email, :celular_princ, :celular_sec, 
            :nome_resp, :tel_resp, :cep, :endereco, :num_residencia, :bairro, :cidade, :estado, :cat_cnh, 
            :validade_cnh, :tipo_veiculo, :placa, :modelo, :cor, :validade_crlv, 0, :foto, :pdf_hab, :pdf_veic, 'ativo'
        )";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    echo json_encode(['status' => 'sucesso', 'msg' => 'Salvo com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro: ' . $e->getMessage()]);
}
?>