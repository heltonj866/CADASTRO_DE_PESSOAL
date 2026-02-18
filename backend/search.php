<?php
// ARQUIVO: backend/search.php (CORRIGIDO PARA ORDENAÇÃO MILITAR)
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

$tipo_busca = $_GET['tipo_busca'] ?? 'geral';
$termo = $_GET['termo'] ?? '';
$posto = $_GET['posto'] ?? '';
$qmg = $_GET['qmg'] ?? '';
$filtro_cnh = $_GET['filtro_cnh'] ?? 'TODAS';

try {
    $sql = "SELECT * FROM tb_militares WHERE 1=1";
    $params = [];

    // --- BUSCA GERAL ---
    if ($tipo_busca === 'geral') {
        if (!empty($termo)) {
            if (is_numeric($termo)) {
                $sql .= " AND numero = ?";
                $params[] = $termo;
            } else {
                $sql .= " AND (nome_guerra LIKE ? OR nome_completo LIKE ?)";
                $params[] = "%$termo%";
                $params[] = "%$termo%";
            }
        }
        if (!empty($posto) && $posto !== 'Todos') {
            $sql .= " AND posto_grad = ?";
            $params[] = $posto;
        }
        if (!empty($qmg) && $qmg !== 'Todas') {
            $sql .= " AND qmg = ?";
            $params[] = $qmg;
        }
    } 
    // --- BUSCA CNH ---
    else if ($tipo_busca === 'cnh') {
        if ($filtro_cnh === 'PRO') {
            $sql .= " AND (cat_cnh LIKE '%C%' OR cat_cnh LIKE '%D%' OR cat_cnh LIKE '%E%')";
        } elseif ($filtro_cnh !== 'TODAS' && !empty($filtro_cnh)) {
            $sql .= " AND cat_cnh = ?";
            $params[] = $filtro_cnh;
        } else {
            $sql .= " AND cat_cnh IS NOT NULL AND cat_cnh != ''";
        }
    }

    // --- AQUI ESTÁ A CORREÇÃO DA HIERARQUIA ---
    $sql .= " ORDER BY 
        CASE posto_grad
            WHEN 'Gen Ex' THEN 1
            WHEN 'Gen Div' THEN 2
            WHEN 'Gen Bda' THEN 3
            WHEN 'Cel' THEN 4
            WHEN 'Ten Cel' THEN 5
            WHEN 'Maj' THEN 6
            WHEN 'Cap' THEN 7
            WHEN '1º Ten' THEN 8
            WHEN '2º Ten' THEN 9
            WHEN 'Asp' THEN 10
            WHEN 'Sub Ten' THEN 11
            WHEN 'Subten' THEN 11
            WHEN '1º Sgt' THEN 12
            WHEN '2º Sgt' THEN 13
            WHEN '3º Sgt' THEN 14
            WHEN 'Cb' THEN 15
            WHEN 'Sd EP' THEN 16
            WHEN 'Sd EV' THEN 17
            WHEN 'Sd' THEN 18
            WHEN 'SC' THEN 99
            ELSE 100
        END ASC,
        dt_praca ASC,
        CAST(numero AS UNSIGNED) ASC"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'dados' => $resultados]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro SQL: ' . $e->getMessage()]);
}
?>