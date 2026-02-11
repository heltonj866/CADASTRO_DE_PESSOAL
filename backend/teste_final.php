<?php
// ARQUIVO: backend/teste_final.php

// 1. Força o PHP a mostrar erros na tela (IGNORA config do servidor)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🛠️ TESTE DE VIDA DO BACKEND</h1>";

// 2. Teste Básico do PHP
echo "<p>✅ PHP está rodando.</p>";

// 3. Teste de Inclusão do Banco (Aqui é onde costuma travar)
echo "<p>🔄 Tentando carregar 'db_connect.php'...</p>";

if (!file_exists('db_connect.php')) {
    die("<h3 style='color:red'>❌ ERRO FATAL: O arquivo 'db_connect.php' NÃO EXISTE na pasta backend!</h3>");
}

try {
    require 'db_connect.php';
    echo "<p>✅ Arquivo carregado com sucesso.</p>";
} catch (Throwable $t) {
    die("<h3 style='color:red'>❌ ERRO DE SINTAXE NO 'db_connect.php': <br>" . $t->getMessage() . "</h3>");
}

// 4. Teste de Conexão Real
if (isset($pdo)) {
    echo "<p>✅ Conexão com Banco ($dbname): SUCESSO!</p>";
} else {
    die("<h3 style='color:red'>❌ ERRO: A variável \$pdo não existe. Verifique o código dentro de db_connect.php</h3>");
}

// 5. Teste da Tabela
try {
    $sql = "SELECT count(*) FROM tb_alteracoes";
    $res = $pdo->query($sql);
    echo "<p>✅ Tabela 'tb_alteracoes' encontrada! Registros: " . $res->fetchColumn() . "</p>";
} catch (Exception $e) {
    die("<h3 style='color:red'>❌ ERRO SQL: A tabela não existe. <br>Mensagem: " . $e->getMessage() . "</h3>");
}

echo "<h2 style='color:green'>🎉 CONCLUSÃO: O SISTEMA ESTÁ PRONTO!</h2>";
echo "<p>Se você viu todas as mensagens verdes acima, o problema era apenas cache ou navegador.</p>";
?>