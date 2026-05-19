<?php
// ARQUIVO: backend/public_search.php
header('Content-Type: application/json; charset=utf-8');
require 'db_connect.php';

try {
    $termo = "%" . ($_GET['termo'] ?? '') . "%";

    // Removido GROUP BY. Ordenação por ID garante que os veículos do mesmo militar venham juntos.
    $sql = "SELECT m.*, v.placa, v.modelo, v.cor, v.homologado, v.tipo_veiculo 
            FROM tb_militares m 
            LEFT JOIN tb_veiculos v ON m.id = v.militar_id 
            WHERE m.nome_guerra LIKE :t1 
               OR m.nome_completo LIKE :t2 
               OR v.placa LIKE :t3 
            ORDER BY m.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t1'=>$termo, ':t2'=>$termo, ':t3'=>$termo]);
    
    echo json_encode(['status' => 'sucesso', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
?>