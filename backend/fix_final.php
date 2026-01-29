<?php
// ARQUIVO: backend/fix_final.php
header('Content-Type: text/html; charset=utf-8');
require 'db_connect.php';

echo "<h3>🔨 Reconstruindo Tabela de Usuários...</h3>";

try {
    // 1. Apaga a tabela antiga para evitar conflitos
    // (Não se preocupe, isso não apaga os Militares, só os logins)
    $pdo->exec("DROP TABLE IF EXISTS tb_usuarios");
    echo "<p>🗑️ Tabela antiga removida.</p>";

    // 2. Cria a tabela nova com a estrutura PERFEITA
    $sql = "CREATE TABLE tb_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identidade VARCHAR(20) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',  -- Coluna que estava faltando
        ativo TINYINT(1) DEFAULT 1                 -- Coluna para ativar/desativar
    )";
    $pdo->exec($sql);
    echo "<p>✨ Nova tabela criada com colunas 'role' e 'senha_hash'.</p>";

    // 3. Cria o Admin Padrão
    $senha_segura = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO tb_usuarios (identidade, senha_hash, role, ativo) VALUES (?, ?, 'admin', 1)");
    $stmt->execute(['000000000-0', $senha_segura]);
    
    echo "<p>✅ <b>Usuário Admin criado!</b></p>";
    echo "<ul>";
    echo "<li>Identidade: <b>000000000-0</b></li>";
    echo "<li>Senha: <b>admin123</b></li>";
    echo "</ul>";

    echo "<hr>";
    echo "<a href='../index.html' style='font-size: 20px; font-weight: bold;'>VOLTAR PARA O LOGIN</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>ERRO: " . $e->getMessage() . "</h3>";
}
?>