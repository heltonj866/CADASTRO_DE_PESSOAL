<?php
// ARQUIVO: backend/relatorio_s2.php
session_start();
require 'db_connect.php';

// Controle de Acesso: Apenas S2 e Administradores devem gerar este relatório
$role = $_SESSION['sismil_role'] ?? '';
// Nota: Se você valida o nível de acesso por sessão no PHP, pode descomentar a linha abaixo:
// if (!in_array(strtolower(trim($_SESSION['usuario_role'])), ['admin', 's2'])) { die("Acesso negado."); }

try {
    // Busca todos os veículos e faz o JOIN com a tabela de militares para trazer os donos
    $sql = "SELECT v.*, m.posto_grad, m.nome_guerra, m.numero, m.subunidade, m.celular_princ 
            FROM tb_veiculos v
            JOIN tb_militares m ON v.militar_id = m.id
            ORDER BY m.posto_grad DESC, m.nome_guerra ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao gerar relatório: " . $e->getMessage());
}

$data_atual = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SISMIL - Relatório Geral de Veículos (S2)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        
        body {
            font-family: 'Roboto', sans-serif;
            margin: 20px;
            color: #333;
            background-color: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            font-size: 22px;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 16px;
            margin: 5px 0;
            font-weight: normal;
        }
        .meta-info {
            text-align: right;
            font-size: 12px;
            color: #555;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .badge {
            padding: 3px 6px;
            font-weight: bold;
            border-radius: 3px;
            font-size: 10px;
            text-transform: uppercase;
            display: inline-block;
        }
        .badge-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        
        .placa {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
        }
        
        .no-print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #198754;
            color: white;
            border: none;
            padding: 10px 20px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 9999;
        }

        @media print {
            .no-print-btn { display: none; }
            body { margin: 10px; }
            table { font-size: 11px; }
            th { background-color: #e6e6e6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <button class="no-print-btn" onclick="window.print()">🖨️ IMPRIMIR / SALVAR PDF</button>

    <div class="header">
        <h1>MINISTÉRIO DA DEFESA</h1>
        <h2>EXÉRCITO BRASILEIRO - BATALHÃO DE ENGENHARIA</h2>
        <h1>SISMIL - RELATÓRIO GERAL DE VEÍCULOS CADASTRADOS</h1>
    </div>

    <div class="meta-info">
        <strong>Gerado em:</strong> <?php echo $data_atual; ?> | <strong>Total de Veículos:</strong> <?php echo count($veiculos); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Posto/Grad - Nome de Guerra</th>
                <th>Cia</th>
                <th>Tipo</th>
                <th>Placa</th>
                <th>Marca/Modelo/Cor</th>
                <th>Validade CRLV</th>
                <th>Status S2</th>
                <th>Observações da Pendência</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($veiculos) > 0): ?>
                <?php foreach ($veiculos as $v): ?>
                    <?php 
                        $nome_completo = $v['posto_grad'] . " " . (!empty($v['numero']) ? $v['numero'] . " " : "") . $v['nome_guerra'];
                        $status_badge = $v['homologado'] == 1 ? '<span class="badge badge-success">Liberado</span>' : '<span class="badge badge-danger">Pendente</span>';
                        $validade = $v['validade_crlv'] ? date('d/m/Y', strtotime($v['validade_crlv'])) : '---';
                        $veiculo_detalhes = strtoupper(($v['marca'] ? $v['marca'] . " / " : "") . $v['modelo'] . " (" . $v['cor'] . ")");
                    ?>
                    <tr>
                        <td style="font-weight: bold;"><?php echo strtoupper($nome_completo); ?></td>
                        <td><?php echo strtoupper($v['companhia'] ?? '---'); ?></td>
                        <td><?php echo strtoupper($v['tipo_veiculo']); ?></td>
                        <td class="placa"><?php echo strtoupper($v['placa']); ?></td>
                        <td><?php echo $veiculo_detalhes; ?></td>
                        <td><?php echo $validade; ?></td>
                        <td style="text-align: center;"><?php echo $status_badge; ?></td>
                        <td style="color: #666; font-style: italic;"><?php echo $v['observacao_s2'] ? htmlspecialchars($v['observacao_s2']) : '---'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #666;">Nenhum veículo cadastrado no banco de dados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>