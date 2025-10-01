<?php
require_once 'config/database.php';

echo "<h2>Teste de Senha - Sistema IA</h2>";

// Conectar ao banco
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Erro na conexão com o banco de dados!");
}

// Verificar usuário atual
$query = "SELECT id, email, password FROM users WHERE email = 'admin@teste.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<h3>✅ Usuário encontrado:</h3>";
    echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
    echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
    echo "<p><strong>Hash atual:</strong> " . $user['password'] . "</p>";
    
    // Testar senha atual
    $senha_teste = '123456';
    $verifica = password_verify($senha_teste, $user['password']);
    
    echo "<h3>🔍 Teste da senha '123456':</h3>";
    echo "<p><strong>Resultado:</strong> " . ($verifica ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "</p>";
    
    if (!$verifica) {
        echo "<h3>🔧 Corrigindo senha...</h3>";
        
        // Gerar novo hash correto
        $novo_hash = password_hash($senha_teste, PASSWORD_DEFAULT);
        echo "<p><strong>Novo hash:</strong> " . $novo_hash . "</p>";
        
        // Atualizar no banco
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $novo_hash);
        $stmt->bindParam(':id', $user['id']);
        
        if ($stmt->execute()) {
            echo "<p>✅ <strong>Senha atualizada com sucesso!</strong></p>";
            
            // Testar novamente
            $verifica_novo = password_verify($senha_teste, $novo_hash);
            echo "<p><strong>Teste final:</strong> " . ($verifica_novo ? "✅ FUNCIONANDO" : "❌ ERRO") . "</p>";
        } else {
            echo "<p>❌ <strong>Erro ao atualizar senha</strong></p>";
        }
    }
    
} else {
    echo "<h3>❌ Usuário não encontrado!</h3>";
    echo "<p>Criando usuário de teste...</p>";
    
    // Criar usuário
    $email = 'admin@teste.com';
    $senha = '123456';
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (email, password) VALUES (:email, :password)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hash);
    
    if ($stmt->execute()) {
        echo "<p>✅ <strong>Usuário criado com sucesso!</strong></p>";
        echo "<p><strong>Email:</strong> admin@teste.com</p>";
        echo "<p><strong>Senha:</strong> 123456</p>";
        echo "<p><strong>Hash:</strong> " . $hash . "</p>";
    } else {
        echo "<p>❌ <strong>Erro ao criar usuário</strong></p>";
    }
}

echo "<hr>";
echo "<h3>📋 Informações do Sistema:</h3>";
echo "<p><strong>Versão PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Algoritmo padrão:</strong> " . PASSWORD_DEFAULT . "</p>";

// Testar geração de hash
$teste_hash = password_hash('123456', PASSWORD_DEFAULT);
echo "<p><strong>Hash de teste:</strong> " . $teste_hash . "</p>";
echo "<p><strong>Verificação:</strong> " . (password_verify('123456', $teste_hash) ? "✅ OK" : "❌ ERRO") . "</p>";

echo "<hr>";
echo "<p><a href='login.php'>← Voltar para Login</a></p>";
echo "<p><em>Após confirmar que está funcionando, delete este arquivo por segurança!</em></p>";
?>