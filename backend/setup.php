<?php
// ARQUIVO: backend/setup.php
require 'db_connect.php'; // Chama o arquivo que criamos acima

// Seus dados de acesso
$login = '000000000-0';
$senha = 'admin123';
$nivel = 'admin';

// Criptografa a senha (Segurança)
$senha_secreta = password_hash($senha, PASSWORD_DEFAULT);

try {
    // Insere no banco
    $sql = "INSERT INTO tb_usuarios (identidade, senha_hash, nivel_acesso) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$login, $senha_secreta, $nivel]);

    echo "<h1>TUDO CERTO! ✅</h1>";
    echo "Usuário Admin criado com sucesso.<br>";
    echo "Login: <strong>$login</strong><br>";
    echo "Senha: <strong>$senha</strong><br>";
    echo "<br>Agora pode fechar essa tela.";

} catch (PDOException $e) {
    echo "<h1>OPS! ⚠️</h1>";
    echo "Erro: Provavelmente você já criou esse usuário antes.<br>";
    echo "Detalhe do erro: " . $e->getMessage();
}
?>