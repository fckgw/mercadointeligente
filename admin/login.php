<?php
session_start();
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_perfil'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ícones do Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { width: 100%; max-width: 400px; padding: 2.5rem; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); background: #fff; border:none; }
        .logo-img { max-width: 160px; height: auto; margin-bottom: 20px; }
        .input-group-text { background: none; border-left: none; cursor: pointer; }
        .password-input { border-right: none; }
        .btn-primary { padding: 12px; font-weight: bold; border-radius: 10px; background-color: #0056b3; }
        .form-control { padding: 12px; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center px-3">
    <div class="login-card text-center">
        <img src="../images/Logo_MercadoInteligente.png" alt="Mercado Inteligente" class="logo-img">
        <h5 class="fw-bold text-dark mb-4">Painel Administrativo</h5>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger py-2 small">E-mail ou senha incorretos.</div>
        <?php endif; ?>

        <form action="autenticar.php" method="POST">
            <div class="mb-3 text-start">
                <label class="form-label small fw-bold">E-mail</label>
                <input type="email" name="email" class="form-control" placeholder="felipe@bdsoft.com.br" required>
            </div>
            
            <div class="mb-3 text-start">
                <label class="form-label small fw-bold">Senha</label>
                <div class="input-group">
                    <input type="password" name="senha" id="senha" class="form-control password-input" placeholder="••••••••" required>
                    <span class="input-group-text border" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="text-end mb-4">
                <a href="recuperar_senha.php" class="text-decoration-none small text-primary">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-sm">Entrar no Sistema</button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <a href="../index.php" class="text-decoration-none small text-muted">← Voltar para o site</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const senhaInput = document.getElementById('senha');
    const toggleIcon = document.getElementById('toggleIcon');
    if (senhaInput.type === 'password') {
        senhaInput.type = 'text';
        toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        senhaInput.type = 'password';
        toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>