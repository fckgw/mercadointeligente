<?php
session_start();
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pegamos os dados e limpamos espaços extras
    $email_digitado = trim($_POST['email']);
    $senha_digitada = $_POST['senha'];

    // 1. Busca o usuário pelo e-mail
    $stmt = $pdo->prepare("SELECT id, nome, senha, perfil FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email_digitado]);
    $usuario = $stmt->fetch();

    // 2. Validação
    if ($usuario) {
        // Verifica se a senha bate com o Hash criptografado
        if (password_verify($senha_digitada, $usuario['senha'])) {
            
            // Verifica se é administrador
            if ($usuario['perfil'] === 'admin') {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];

                header("Location: dashboard.php");
                exit;
            } else {
                // Se for um cliente tentando entrar no admin
                header("Location: login.php?erro=permissao");
                exit;
            }
        }
    }

    // Se chegou aqui, algo falhou
    header("Location: login.php?erro=1");
    exit;
}