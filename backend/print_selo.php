<?php
// ARQUIVO: backend/print_selo.php
require 'db_connect.php';

// Recebemos o veiculo_id enviado pelo novo script.js
$veiculo_id = $_GET['veiculo_id'] ?? null; 

if (!$veiculo_id) {
    die("ID do veículo não fornecido.");
}

try {
    // JOIN entre veículos e militares para buscar os dados corretamente na nova estrutura
    $sql = "SELECT v.*, m.posto_grad, m.nome_guerra, m.numero, m.celular_princ 
            FROM tb_veiculos v 
            JOIN tb_militares m ON v.militar_id = m.id 
            WHERE v.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$veiculo_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$m) die("Veículo ou militar não encontrado.");

    // Verifica se está homologado
    if ($m['homologado'] != 1) {
        die("<div style='text-align:center; padding:50px;'><h1>🚫 VEÍCULO NÃO HOMOLOGADO 🚫</h1></div>");
    }

    // Lógica de Cores por posto
    $cor_tema = '#000'; 
    $posto = $m['posto_grad'];
    
    $oficiais = ['Gen', 'Cel', 'Ten Cel', 'Maj', 'Cap', '1º Ten', '2º Ten', 'Asp'];
    $graduados = ['Sub Ten', '1º Sgt', '2º Sgt', '3º Sgt'];
    
    if (in_array($posto, $oficiais)) $cor_tema = '#b30000'; // Vermelho
    elseif (in_array($posto, $graduados)) $cor_tema = '#0033cc'; // Azul
    else $cor_tema = '#006600'; // Verde

    // Lógica da Validade (CRLV + 1 Ano)
    if (!empty($m['validade_crlv'])) {
        $nova_data = strtotime('+1 year', strtotime($m['validade_crlv']));
        $meses = [1=>'JANEIRO', 2=>'FEVEREIRO', 3=>'MARÇO', 4=>'ABRIL', 5=>'MAIO', 6=>'JUNHO', 7=>'JULHO', 8=>'AGOSTO', 9=>'SETEMBRO', 10=>'OUTUBRO', 11=>'NOVEMBRO', 12=>'DEZEMBRO'];
        $dia = date('d', $nova_data);
        $mes = $meses[(int)date('m', $nova_data)];
        $ano = date('Y', $nova_data);
        $validade_texto = "VALIDADE: $dia DE $mes $ano";
    } else {
        $validade_texto = "VALIDADE: INDEFINIDA";
    }

    // Formatação de variáveis para o layout original
    $placa = !empty($m['placa']) ? strtoupper($m['placa']) : 'SEM VEÍCULO';
    
    $modelo_cor = (!empty($m['modelo']) ? $m['modelo'] : '') . 
                  ((!empty($m['modelo']) && !empty($m['cor'])) ? ' - ' : '') . 
                  (!empty($m['cor']) ? $m['cor'] : '');
    
    $identificacao = $m['posto_grad'] . ' ' . 
                     (!empty($m['numero']) ? $m['numero'] . ' ' : '') . 
                     $m['nome_guerra'];
                     
    $telefone = !empty($m['celular_princ']) ? $m['celular_princ'] : '';

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Selo - <?php echo $placa; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap');

        body {
            margin: 0;
            padding: 20px;
            font-family: 'Roboto', sans-serif;
            background-color: #e0e0e0;
            display: flex;
            justify-content: center;
        }

        .selo-card {
            width: 8.5cm;
            height: 5.5cm;
            background: white;
            border-left: 8px solid <?php echo $cor_tema; ?>;
            border-right: 8px solid <?php echo $cor_tema; ?>;
            border-bottom: 8px solid <?php echo $cor_tema; ?>;
            border-top: none; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .header-top {
            background-color: <?php echo $cor_tema; ?>;
            height: 45px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 8px;
            box-sizing: border-box;
        }

        .img-brasao {
            height: 38px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0px 1px 2px rgba(0,0,0,0.3));
        }

        .conteudo {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 5px;
        }

        .placa-txt {
            font-size: 34px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000;
            line-height: 1;
            margin-top: 5px;
        }

        .veiculo-txt {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #444;
            margin-bottom: 15px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 2px;
            width: 80%;
            text-align: center;
        }

        .dados-container {
            width: 100%;
            padding-left: 20px;
            text-align: left;
            margin-top: auto;
        }

        .linha-dado {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .linha-fone {
            font-size: 15px;
            font-weight: 500;
            color: #333;
        }

        /* Nova classe para a validade centralizada na parte inferior */
        .validade-footer {
            width: 100%;
            text-align: center;
            font-size: 14px;
            font-weight: 900;
            color: #000;
            padding-bottom: 5px;
            padding-top: 5px;
            margin-top: auto; /* Empurra para o fundo do cartão */
        }

        .no-print {
            position: fixed;
            top: 20px; right: 20px;
            background: #333; color: #fff;
            border: none; padding: 10px 20px;
            cursor: pointer; border-radius: 5px;
        }

        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none; }
            .selo-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <button class="no-print" onclick="window.print()">IMPRIMIR</button>

    <div class="selo-card">
        <div class="header-top">
            <img src="../uploads/brasao.png" class="img-brasao" alt="Btl" onerror="this.style.opacity=0">
            <img src="../uploads/brasao_eb.png" class="img-brasao" alt="EB" onerror="this.style.opacity=0">
        </div>

        <div class="conteudo">
            
            <div class="placa-txt"><?php echo $placa; ?></div>
            <div class="veiculo-txt"><?php echo $modelo_cor; ?></div>

            <div class="dados-container">
                <div class="linha-dado"><?php echo $identificacao; ?></div>
                <div class="linha-fone"><?php echo $telefone; ?></div>
            </div>

            <div class="validade-footer"><?php echo $validade_texto; ?></div>
        </div>
    </div>

</body>
</html>