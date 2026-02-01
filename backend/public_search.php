<?php
// ARQUIVO: backend/public_search.php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    if (!file_exists('db_connect.php')) throw new Exception("db_connect.php ausente.");
    require 'db_connect.php';

    $termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

    if (empty($termo) || strlen($termo) < 2) {
        echo json_encode(['status' => 'sucesso', 'dados' => []]);
        exit;
    }

    // --- CORREÇÃO AQUI ---
    // Usamos os nomes exatos da sua tabela: 
    // celular_princ, dt_nascimento, dt_praca, secao, subunidade
    $sql = "SELECT id, posto_grad, nome_guerra, nome_completo, 
                   subunidade, pelotao, secao,
                   celular_princ, dt_nascimento, dt_praca,
                   placa, modelo, cor, homologado, foto_path 
            FROM tb_militares 
            WHERE nome_guerra LIKE :t1 
               OR nome_completo LIKE :t2 
               OR placa LIKE :t3 
               OR modelo LIKE :t4
            ORDER BY nome_guerra ASC LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $busca = "%$termo%";
    
    // Passamos o parâmetro 4 vezes (para evitar o erro HY093)
    $stmt->execute([':t1'=>$busca, ':t2'=>$busca, ':t3'=>$busca, ':t4'=>$busca]);
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'dados' => $dados]);

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>