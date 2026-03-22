<?php
require_once '../core/db.php';

$nome = "Administrator";
$email = "souzafelipe@bdsoft.com.br";
$senha_plana = "Fckgw!@151289";
$senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);
$perfil = "admin";

try {
    // Tenta atualizar se já existir, senão insere
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $upd = $pdo->prepare("UPDATE usuarios SET senha = ?, nome = ?, perfil = ? WHERE email = ?");
        $upd->execute([$senha_hash, $nome, $perfil, $email]);
        echo "Usuário Administrador ATUALIZADO com sucesso!";
    } else {
        $ins = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)");
        $ins->execute([$nome, $email, $senha_hash, $perfil]);
        echo "Usuário Administrador CRIADO com sucesso!";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

// AVISO: Delete este arquivo do servidor após ver a mensagem de sucesso!