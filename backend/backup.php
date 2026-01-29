<?php
// ARQUIVO: backend/backup.php
require 'db_connect.php';
session_start();

// 1. Segurança: Só Admin pode baixar
if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
    die("Acesso Negado.");
}

// 2. Configura o Download do Arquivo
$data = date('d-m-Y_H-i');
$filename = "backup_sismil_{$data}.sql";

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"{$filename}\""); 

// 3. Inicia o conteúdo do SQL
echo "-- BACKUP DO SISTEMA SISMIL\n";
echo "-- Data: " . date('d/m/Y H:i:s') . "\n";
echo "-- Gerado por: " . $_SESSION['usuario_nome'] . "\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

// 4. Pega todas as tabelas
$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// 5. Gera o SQL de cada tabela
foreach ($tables as $table) {
    // A. Estrutura (CREATE TABLE)
    $stmt = $pdo->query("SHOW CREATE TABLE $table");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    echo "\n\n-- Estrutura da tabela: $table --\n";
    echo "DROP TABLE IF EXISTS $table;\n";
    echo $row[1] . ";\n\n";

    // B. Dados (INSERT INTO)
    $stmt = $pdo->query("SELECT * FROM $table");
    $num_fields = $stmt->columnCount();
    
    if ($stmt->rowCount() > 0) {
        echo "-- Dados da tabela: $table --\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]); // Protege aspas
                $row[$j] = str_replace("\n", "\\n", $row[$j]); // Protege quebras de linha
                
                if (isset($row[$j])) {
                    echo '"' . $row[$j] . '"';
                } else {
                    echo '""';
                }
                
                if ($j < ($num_fields - 1)) { 
                    echo ','; 
                }
            }
            echo ");\n";
        }
    }
}

echo "\nSET FOREIGN_KEY_CHECKS=1;";
exit;
?>