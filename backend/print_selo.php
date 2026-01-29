<?php
// ARQUIVO: backend/print_selo.php
require 'db_connect.php';

// 1. Verifica ID
if (!isset($_GET['id']) || empty($_GET['id'])) { die("ID não fornecido."); }
$id = $_GET['id'];

try {
    // 2. Busca dados
    $sql = "SELECT * FROM tb_militares WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$m) { die("Militar não encontrado."); }

    if ($m['homologado'] != 1) {
        die("
            <div style='font-family:sans-serif; text-align:center; padding:50px; color:#b30000'>
                <h1>🚫 ACESSO NEGADO 🚫</h1>
                <h3>Veículo aguardando homologação da 2ª Seção.</h3>
                <p>O selo só pode ser gerado após vistoria e liberação no sistema.</p>
            </div>
        ");
    }

    // 3. Lógica de Cores (Hierarquia)
    $cor_tema = '#000'; 
    $posto = $m['posto_grad'];

    $oficiais = ['Gen', 'Cel', 'Ten Cel', 'Maj', 'Cap', '1º Ten', '2º Ten', 'Asp'];
    $graduados = ['Sub Ten', '1º Sgt', '2º Sgt', '3º Sgt'];
    $pracas = ['Cb', 'Sd', 'Sd EP', 'Sd EV'];
    $civis = ['SC'];

    if (in_array($posto, $oficiais)) {
        $cor_tema = '#b30000'; // Vermelho
    } elseif (in_array($posto, $graduados)) {
        $cor_tema = '#0033cc'; // Azul
    } elseif (in_array($posto, $pracas)) {
        $cor_tema = '#006600'; // Verde
    } elseif (in_array($posto, $civis)) {
        $cor_tema = '#e6b800'; // Amarelo
    }

    // 4. Formatação de Texto
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
            /* Bordas Laterais e Inferior (8px) */
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

        /* CABEÇALHO COLORIDO */
        .header-top {
            background-color: <?php echo $cor_tema; ?>;
            height: 45px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 8px; /* Espaçamento lateral */
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
            margin-bottom: 15px;
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
        </div>
    </div>

</body>
</html>