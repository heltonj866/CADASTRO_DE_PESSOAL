<?php
// ARQUIVO: backend/dossier_militar.php
session_start();
require 'db_connect.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID do militar não informado.");

try {
    // 1. Busca os Dados do Militar
    $stmt = $pdo->prepare("SELECT * FROM tb_militares WHERE id = ?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) die("Militar não encontrado.");

    // 2. Busca os Veículos
    $stmtV = $pdo->prepare("SELECT * FROM tb_veiculos WHERE militar_id = ? ORDER BY id DESC");
    $stmtV->execute([$id]);
    $veiculos = $stmtV->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca o Histórico de Alterações (S1)
    // NOTA: Ajuste o nome da tabela 'tb_alteracoes' se for diferente na sua base de dados
    $stmtH = $pdo->prepare("SELECT * FROM tb_alteracoes WHERE militar_id = ? ORDER BY data_fato DESC");
    $stmtH->execute([$id]);
    $historico = $stmtH->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dossier: " . $e->getMessage());
}

function dataBR($data) { return (!empty($data) && $data != '0000-00-00') ? date('d/m/Y', strtotime($data)) : '---'; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dossier Militar - <?php echo $m['posto_grad'] . ' ' . $m['nome_guerra']; ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; background: #525659; }
        .page { background: #fff; width: 210mm; min-height: 297mm; margin: 20px auto; padding: 20mm; box-shadow: 0 0 10px rgba(0,0,0,0.5); box-sizing: border-box; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { width: 80px; position: absolute; left: 20mm; top: 20mm; }
        .header h3, .header h4 { margin: 3px 0; font-weight: bold; text-transform: uppercase; font-size: 14px;}
        .section-title { background: #e0e0e0; font-weight: bold; text-transform: uppercase; padding: 5px; margin: 20px 0 10px 0; font-size: 13px; border: 1px solid #000; clear: both; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 5px; }
        .col { flex: 1; padding: 0 5px; }
        .col-2 { flex: 2; } .col-3 { flex: 3; }
        .label { font-size: 10px; color: #555; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 13px; font-weight: normal; border-bottom: 1px dotted #ccc; padding-bottom: 2px; }
        .foto-container { width: 110px; height: 140px; border: 1px solid #000; display: flex; justify-content: center; align-items: center; float: right; margin-left: 20px; background-size: cover; background-position: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background: #f0f0f0; text-transform: uppercase; }
        
        .status-stamp { color: #d32f2f; font-size: 24px; font-weight: bold; border: 3px solid #d32f2f; padding: 5px 15px; display: inline-block; transform: rotate(-10deg); position: absolute; top: 30mm; right: 50mm; opacity: 0.8;}
        
        @media print { body { background: #fff; } .page { margin: 0; padding: 15mm; box-shadow: none; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; padding: 15px; background: #333; position: sticky; top: 0; z-index: 1000;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; font-weight: bold; cursor: pointer;">🖨️ IMPRIMIR / SALVAR PDF</button>
</div>

<div class="page">
    <div class="header">
        <img src="../uploads/brasao.png" alt="Brasão">
        <h4>MINISTÉRIO DA DEFESA</h4>
        <h4>EXÉRCITO BRASILEIRO</h4>
        <h4>2º BATALHÃO DE ENGENHARIA DE CONSTRUÇÃO</h4>
        <h4 style="margin-top: 15px; text-decoration: underline;">DOSSIER INDIVIDUAL DE PESSOAL (SISMIL)</h4>
    </div>

    <?php if($m['status_ativo'] == 0): ?>
        <div class="status-stamp">DESLIGADO</div>
    <?php endif; ?>

    <div class="section-title">1. Dados Pessoais e Organizacionais</div>
    
    <div style="display: flex; gap: 20px; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        
        <div style="flex: 1;">
            <div class="row">
                <div class="col-3"><div class="label">Nome Completo</div><div class="value fw-bold"><?php echo strtoupper($m['nome_completo']); ?></div></div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col"><div class="label">Posto / Grad</div><div class="value"><?php echo strtoupper($m['posto_grad']); ?></div></div>
                <div class="col"><div class="label">Nome de Guerra</div><div class="value fw-bold"><?php echo strtoupper($m['nome_guerra']); ?></div></div>
                <div class="col"><div class="label">Número</div><div class="value"><?php echo $m['numero'] ?: '---'; ?></div></div>
            </div>
            <div class="row">
                <div class="col"><div class="label">Idt Militar</div><div class="value"><?php echo $m['idt_militar']; ?></div></div>
                <div class="col"><div class="label">CPF</div><div class="value"><?php echo $m['identidade']; ?></div></div>
                <div class="col"><div class="label">Nascimento</div><div class="value"><?php echo dataBR($m['dt_nascimento']); ?></div></div>
                <div class="col"><div class="label">Tipo Sanguíneo</div><div class="value"><?php echo $m['tipo_sanguineo']; ?></div></div>
            </div>
            <div class="row">
                <div class="col"><div class="label">Subunidade</div><div class="value"><?php echo $m['subunidade']; ?></div></div>
                <div class="col"><div class="label">Pelotão/Seção</div><div class="value"><?php echo $m['pelotao'] . ' / ' . $m['secao']; ?></div></div>
                <div class="col"><div class="label">Arma / QMG</div><div class="value"><?php echo $m['qmg']; ?></div></div>
                <div class="col"><div class="label">Data de Praça</div><div class="value"><?php echo dataBR($m['dt_praca']); ?></div></div>
            </div>
        </div>

        <div style="width: 110px; flex-shrink: 0; display: flex; justify-content: center;">
            <div class="foto-container" style="background-image: url('../uploads/<?php echo $m['foto_path'] ?: '../assets/sem_foto.png'; ?>'); float: none; margin: 0;">
                <?php if(!$m['foto_path']) echo "SEM FOTO"; ?>
            </div>
        </div>
        
    </div>

    <div class="section-title">2. Contato e Plano de Chamada</div>
    <div class="row">
        <div class="col"><div class="label">Celular Principal</div><div class="value"><?php echo $m['celular_princ']; ?></div></div>
        <div class="col"><div class="label">Celular Secundário</div><div class="value"><?php echo $m['celular_sec']; ?></div></div>
        <div class="col"><div class="label">Email</div><div class="value"><?php echo $m['email']; ?></div></div>
    </div>
    <div class="row">
        <div class="col-3"><div class="label">Endereço</div><div class="value"><?php echo $m['endereco'] . ' Nº ' . $m['num_residencia'] . ' - ' . $m['bairro']; ?></div></div>
        <div class="col"><div class="label">Cidade/UF</div><div class="value"><?php echo $m['cidade'] . '/' . $m['estado']; ?></div></div>
    </div>
    <div class="row">
        <div class="col"><div class="label">Pessoa de Referência (Emergência)</div><div class="value"><?php echo $m['nome_resp'] ?: '---'; ?></div></div>
        <div class="col"><div class="label">Telefone da Referência</div><div class="value"><?php echo $m['tel_resp'] ?: '---'; ?></div></div>
    </div>

    <div class="section-title">3. Documentação Anexada ao SISMIL</div>
    <ul>
        <?php if($m['pdf_habilitacao']): ?> <li>CNH do Militar (Digitalizada)</li> <?php endif; ?>
        <?php if($m['status_ativo'] == 0 && $m['pdf_nada_consta']): ?> <li style="color:red; font-weight:bold;">Ficha de Nada Consta (Desligamento) anexada ao sistema.</li> <?php endif; ?>
        <?php foreach($veiculos as $v): if($v['pdf_veiculo']): ?> <li>CRLV - Placa <?php echo strtoupper($v['placa']); ?></li> <?php endif; endforeach; ?>
        <?php if(empty($m['pdf_habilitacao']) && empty($m['pdf_nada_consta']) && count($veiculos) == 0): ?> <li style="color:#666; font-style:italic;">Nenhum documento PDF anexado.</li> <?php endif; ?>
    </ul>

    <div class="section-title">4. Veículos Cadastrados e Trânsito</div>
    <div class="row">
        <div class="col"><div class="label">Categoria da CNH</div><div class="value"><?php echo $m['cat_cnh'] ?: 'NÃO POSSUI'; ?></div></div>
        <div class="col"><div class="label">Validade CNH</div><div class="value"><?php echo dataBR($m['validade_cnh']); ?></div></div>
    </div>
    <table>
        <thead><tr><th>Tipo</th><th>Placa</th><th>Marca/Modelo/Cor</th><th>Validade CRLV</th><th>Status S2</th></tr></thead>
        <tbody>
            <?php if(count($veiculos) > 0): foreach($veiculos as $v): ?>
                <tr>
                    <td><?php echo strtoupper($v['tipo_veiculo']); ?></td>
                    <td style="font-family:monospace; font-weight:bold;"><?php echo strtoupper($v['placa']); ?></td>
                    <td><?php echo strtoupper($v['marca'] . ' ' . $v['modelo'] . ' - ' . $v['cor']); ?></td>
                    <td><?php echo dataBR($v['validade_crlv']); ?></td>
                    <td><?php echo $v['homologado'] == 1 ? 'HOMOLOGADO' : 'PENDENTE'; ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center; color:#555;">Nenhum veículo cadastrado na base de dados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">5. Histórico de Alterações (S1 / Sargenteação)</div>
    <table>
        <thead><tr><th width="80">Data Fato</th><th width="100">Categoria</th><th>Tipo / Detalhe</th><th>Descrição da Alteração</th></tr></thead>
        <tbody>
            <?php if(count($historico) > 0): foreach($historico as $h): ?>
                <tr>
                    <td><?php echo dataBR($h['data_fato']); ?></td>
                    <td><?php echo strtoupper($h['categoria']); ?></td>
                    <td style="font-weight:bold;"><?php echo strtoupper($h['tipo_detalhe']); ?></td>
                    <td><?php echo $h['descricao']; ?> <?php if($h['documento_ref']) echo "<br><i>Ref: " . $h['documento_ref'] . "</i>"; ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="4" style="text-align:center; color:#555;">Nenhuma alteração registrada no histórico.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: center; font-size: 11px; color: #666;">
        <p>______________________________________________________</p>
        <p>Gerado pelo SISMIL em <?php echo date('d/m/Y H:i'); ?><br>Documento de uso interno administrativo.</p>
    </div>
</div>

</body>
</html>