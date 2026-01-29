<?php
// ARQUIVO: backend/fix_db.php
header('Content-Type: text/html; charset=utf-8');
require 'db_connect.php';

echo "<h3>Iniciando atualização do Banco de Dados...</h3>";

try {
    // 1. Tenta renomear a coluna 'senha' para 'senha_hash' e aumentar o tamanho
    // Se der erro, é porque já foi feito ou a coluna não existe
    try {
        $sql = "ALTER TABLE tb_usuarios CHANGE senha senha_hash VARCHAR(255) NOT NULL";
        $pdo->exec($sql);
        echo "<p>✅ Coluna 'senha' renomeada para 'senha_hash'.</p>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Aviso (Coluna): " . $e->getMessage() . " (Talvez já tenha sido renomeada)</p>";
    }

    // 2. Cria a senha Hash para o Admin
    $nova_senha = 'admin123'; // Senha padrão
    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $identidade_admin = '000000000-0'; // Sua identidade de admin

    // 3. Atualiza o Admin
    $sql_update = "UPDATE tb_usuarios SET senha_hash = ?, role = 'admin', ativo = 1 WHERE identidade = ?";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([$hash, $identidade_admin]);

    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Senha do Admin atualizada para criptografia moderna!</p>";
    } else {
        echo "<p>⚠️ Nenhuma linha alterada. Verifique se a identidade do admin é mesmo <b>$identidade_admin</b>.</p>";
        
        // Se não achou o admin, vamos tentar criar um de emergência
        $check = $pdo->query("SELECT count(*) FROM tb_usuarios WHERE identidade = '$identidade_admin'")->fetchColumn();
        if ($check == 0) {
            $pdo->prepare("INSERT INTO tb_usuarios (identidade, senha_hash, role, ativo) VALUES (?, ?, 'admin', 1)")
                ->execute([$identidade_admin, $hash]);
            echo "<p>✅ Usuário Admin de emergência criado!</p>";
        }
    }

    echo "<h4>🎉 TUDO PRONTO! Tente logar agora.</h4>";
    echo "<a href='../index.html'>Voltar para o Login</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>ERRO FATAL: " . $e->getMessage() . "</h3>";
}
?>