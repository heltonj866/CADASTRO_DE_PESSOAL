<?php
// ARQUIVO: backend/dashboard_stats.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    // 1. Estatísticas Básicas (Contando apenas militares ativos)
    $stmtM = $pdo->query("SELECT COUNT(*) as qtd FROM tb_militares WHERE status_ativo = 1");
    $militares = $stmtM->fetch(PDO::FETCH_ASSOC)['qtd'];

    $stmtV = $pdo->query("SELECT COUNT(*) as qtd FROM tb_veiculos");
    $veiculos = $stmtV->fetch(PDO::FETCH_ASSOC)['qtd'];

    $stmtP = $pdo->query("SELECT COUNT(*) as qtd FROM tb_veiculos WHERE homologado = 0");
    $pendentes = $stmtP->fetch(PDO::FETCH_ASSOC)['qtd'];

    // 2. Mapa do Efetivo Pronto (Agrupado por SU e Posto/Graduação)
    $sqlEfetivo = "
        SELECT subunidade, posto_grad, COUNT(*) as qtd
        FROM tb_militares 
        WHERE status_ativo = 1
        GROUP BY subunidade, posto_grad
        ORDER BY 
            subunidade ASC,
            CASE posto_grad
                WHEN 'Gen Ex' THEN 1 WHEN 'Gen Div' THEN 2 WHEN 'Gen Bda' THEN 3
                WHEN 'Cel' THEN 4 WHEN 'Ten Cel' THEN 5 WHEN 'Maj' THEN 6
                WHEN 'Cap' THEN 7 WHEN '1º Ten' THEN 8 WHEN '2º Ten' THEN 9
                WHEN 'Asp' THEN 10 WHEN 'Sub Ten' THEN 11 WHEN 'Subten' THEN 11
                WHEN '1º Sgt' THEN 12 WHEN '2º Sgt' THEN 13 WHEN '3º Sgt' THEN 14
                WHEN 'Cb' THEN 15 WHEN 'Sd EP' THEN 16 WHEN 'Sd EV' THEN 17
                WHEN 'Sd' THEN 18 WHEN 'SC' THEN 99 ELSE 100
            END ASC
    ";
    
    $stmtEf = $pdo->query($sqlEfetivo);
    $efetivoRaw = $stmtEf->fetchAll(PDO::FETCH_ASSOC);

    // Organizar os dados num formato fácil para o JavaScript criar os cartões
    $efetivoSU = [];
    foreach ($efetivoRaw as $row) {
        $su = $row['subunidade'] ?: 'Sem SU';
        $posto = $row['posto_grad'];
        $qtd = $row['qtd'];

        if (!isset($efetivoSU[$su])) {
            $efetivoSU[$su] = [
                'total' => 0,
                'detalhes' => []
            ];
        }
        $efetivoSU[$su]['total'] += $qtd;
        $efetivoSU[$su]['detalhes'][] = ['posto' => $posto, 'qtd' => $qtd];
    }

    echo json_encode([
        'status' => 'sucesso',
        'militares' => $militares,
        'veiculos' => $veiculos,
        'pendentes' => $pendentes,
        'efetivo_su' => $efetivoSU
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro SQL: ' . $e->getMessage()]);
}
?>