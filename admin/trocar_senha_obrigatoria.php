<?php
/**
 * SISTEMA MERCADO INTELIGENTE
 * Arquivo: admin/trocar_senha_obrigatoria.php
 */
session_start();
require_once '../core/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($nova_senha === $confirmar_senha && strlen($nova_senha) >= 6) {
        $senha_criptografada = password_hash($nova_senha, PASSWORD_DEFAULT);
        $comando_update = $pdo->prepare("UPDATE usuarios SET senha = ?, trocar_senha = 0 WHERE id = ?");
        $comando_update->execute([$senha_criptografada, $_SESSION['usuario_id']]);

        header("Location: ../cliente/simulador.php?senha_atualizada=sucesso");
        exit;
    } else {
        $mensagem_erro = "As senhas não conferem ou são muito curtas (mínimo 6 caracteres).";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-troca { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 400px; width: 100%; }
    </style>
</head>
<body>
<div class="card card-troca p-4">
    <div class="text-center mb-4">
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
        <h5 class="fw-bold mt-3">Segurança da Conta</h5>
        <p class="text-muted small">Por favor, defina uma nova senha para o seu primeiro acesso.</p>
    </div>

    <?php if (isset($mensagem_erro)): ?>
        <div class="alert alert-danger small"><?php echo $mensagem_erro; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">NOVA SENHA</label>
            <input type="password" name="nova_senha" class="form-control form-control-lg" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">CONFIRMAR NOVA SENHA</label>
            <input type="password" name="confirmar_senha" class="form-control form-control-lg" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold">ATUALIZAR E ENTRAR</button>
    </form>
</div>
</body>
</html>