<?php
// ARQUIVO: backend/save_militar.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    // Helper para limpar campos vazios
    function getPost($key) {
        $val = $_POST[$key] ?? null;
        return ($val === '' || $val === 'null') ? null : trim($val);
    }

    $id = getPost('id_militar') ?? getPost('militarId'); // Aceita os dois nomes
    $cpf = getPost('cpf') ?? getPost('identidade');      // Aceita os dois nomes

    if (!$cpf) throw new Exception("O CPF é obrigatório.");

    // TRATAMENTO DA FOTO (CORRIGIDO CAMINHO)
    $foto_path = null;
    $sqlFoto = "";
    
    if (isset($_FILES['foto_militar']) && $_FILES['foto_militar']['error'] === UPLOAD_ERR_OK) {
        // Caminho físico: sai do backend (..) e entra em uploads
        $dir = __DIR__ . '/../uploads/';
        
        // Cria pasta se não existir
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto_militar']['name'], PATHINFO_EXTENSION));
        $novo_nome = uniqid('foto_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['foto_militar']['tmp_name'], $dir . $novo_nome)) {
            $foto_path = $novo_nome;
            $sqlFoto = ", foto_path = :foto";
        }
    }

    // Tratamento do Checkbox (Se não vier, é 0)
    $homologado = isset($_POST['homologado']) ? 1 : 0;

    // Campos do Banco
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
        ':tipo_veiculo' => getPost('tipo_veic') ?? getPost('tipo_veiculo'), // Mapeia 'tipo_veic' do HTML
        ':placa' => getPost('placa'),
        ':modelo' => getPost('modelo'),
        ':cor' => getPost('cor'),
        ':validade_crlv' => getPost('validade_crlv'),
        ':homologado' => $homologado
    ];

    if ($id) {
        // UPDATE
        $dados[':id'] = $id;
        if ($foto_path) $dados[':foto'] = $foto_path;

        $sql = "UPDATE tb_militares SET 
            identidade=:identidade, posto_grad=:posto_grad, numero=:numero, nome_guerra=:nome_guerra, 
            subunidade=:subunidade, pelotao=:pelotao, secao=:secao, nome_completo=:nome_completo, 
            qmg=:qmg, dt_nascimento=:dt_nascimento, tipo_sanguineo=:tipo_sanguineo, dt_praca=:dt_praca, 
            idt_militar=:idt_militar, email=:email, celular_princ=:celular_princ, celular_sec=:celular_sec, 
            nome_resp=:nome_resp, tel_resp=:tel_resp, cep=:cep, endereco=:endereco, num_residencia=:num_residencia, 
            bairro=:bairro, cidade=:cidade, estado=:estado, cat_cnh=:cat_cnh, validade_cnh=:validade_cnh, 
            tipo_veiculo=:tipo_veiculo, placa=:placa, modelo=:modelo, cor=:cor, validade_crlv=:validade_crlv, 
            homologado=:homologado
            $sqlFoto
            WHERE id=:id";
    } else {
        // INSERT
        // Verifica duplicidade antes
        $check = $pdo->prepare("SELECT id FROM tb_militares WHERE identidade = ?");
        $check->execute([$cpf]);
        if ($check->rowCount() > 0) throw new Exception("CPF já cadastrado.");

        $dados[':foto'] = $foto_path; // Pode ser null

        $sql = "INSERT INTO tb_militares (
            identidade, posto_grad, numero, nome_guerra, subunidade, pelotao, secao, nome_completo, 
            qmg, dt_nascimento, tipo_sanguineo, dt_praca, idt_militar, email, celular_princ, celular_sec, 
            nome_resp, tel_resp, cep, endereco, num_residencia, bairro, cidade, estado, cat_cnh, 
            validade_cnh, tipo_veiculo, placa, modelo, cor, validade_crlv, homologado, foto_path, status
        ) VALUES (
            :identidade, :posto_grad, :numero, :nome_guerra, :subunidade, :pelotao, :secao, :nome_completo, 
            :qmg, :dt_nascimento, :tipo_sanguineo, :dt_praca, :idt_militar, :email, :celular_princ, :celular_sec, 
            :nome_resp, :tel_resp, :cep, :endereco, :num_residencia, :bairro, :cidade, :estado, :cat_cnh, 
            :validade_cnh, :tipo_veiculo, :placa, :modelo, :cor, :validade_crlv, :homologado, :foto, 'ativo'
        )";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    echo json_encode(['status' => 'sucesso', 'msg' => 'Salvo com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro: ' . $e->getMessage()]);
}
?>