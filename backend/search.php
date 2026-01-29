<?php
// ARQUIVO: backend/search.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

// Captura os parâmetros da URL
$tipo_busca = $_GET['tipo_busca'] ?? 'geral';
$termo = $_GET['termo'] ?? '';
$posto = $_GET['posto'] ?? '';
$qmg = $_GET['qmg'] ?? '';
$filtro_cnh = $_GET['filtro_cnh'] ?? 'TODAS';

try {
    $sql = "SELECT * FROM tb_militares WHERE 1=1";
    $params = [];

    // --- LÓGICA DA BUSCA GERAL ---
    if ($tipo_busca === 'geral') {
        
        // 1. Filtro por Nome (Guerra ou Completo) ou Número
        if (!empty($termo)) {
            // Se o termo for numérico, busca pelo número do militar
            if (is_numeric($termo)) {
                $sql .= " AND numero = ?";
                $params[] = $termo;
            } else {
                // Se for texto, busca no Nome de Guerra OU Nome Completo
                $sql .= " AND (nome_guerra LIKE ? OR nome_completo LIKE ?)";
                $params[] = "%$termo%";
                $params[] = "%$termo%";
            }
        }

        // 2. Filtro por Posto/Graduação
        if (!empty($posto) && $posto !== 'Todos') {
            $sql .= " AND posto_grad = ?";
            $params[] = $posto;
        }

        // 3. Filtro por QMG (Arma/Quadro)
        if (!empty($qmg) && $qmg !== 'Todas') {
            $sql .= " AND qmg = ?";
            $params[] = $qmg;
        }

        // Ordenação padrão: Por Posto (lógica visual) e depois Antiguidade (Data Praça)
        // Como 'posto_grad' é texto, a ordem alfabética pode falhar (Cabo vem antes de Coronel).
        // Vamos ordenar apenas por Nome de Guerra para simplificar nesta correção.
        $sql .= " ORDER BY nome_guerra ASC";

    } 
    // --- LÓGICA DA BUSCA CNH (HOMOLOGAÇÃO) ---
    else if ($tipo_busca === 'cnh') {
        
        if ($filtro_cnh === 'PRO') {
            // Motoristas Profissionais (C, D, E)
            $sql .= " AND (cat_cnh LIKE '%C%' OR cat_cnh LIKE '%D%' OR cat_cnh LIKE '%E%')";
        } 
        elseif ($filtro_cnh !== 'TODAS' && !empty($filtro_cnh)) {
            // Categorias específicas (A, B, AB)
            // Usamos LIKE para garantir que quem tem "AD" apareça na busca de "A" se necessário, 
            // mas aqui vamos ser estritos conforme seu botão.
            $sql .= " AND cat_cnh = ?";
            $params[] = $filtro_cnh;
        } else {
            // Se for TODAS, traz apenas quem TEM carteira preenchida
            $sql .= " AND cat_cnh IS NOT NULL AND cat_cnh != ''";
        }

        $sql .= " ORDER BY validade_cnh ASC";
    }

    // EXECUÇÃO
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'dados' => $resultados]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro SQL: ' . $e->getMessage()]);
}
?>