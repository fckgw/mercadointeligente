<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .recover-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 20px; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="recover-card text-center">
        <h4 class="fw-bold mb-3">Recuperar Acesso</h4>
        <p class="text-muted small mb-4">Informe seu e-mail cadastrado para receber as instruções de reset de senha.</p>
        
        <form action="processar_recuperacao.php" method="POST">
            <div class="mb-3 text-start">
                <label class="form-label small fw-bold">E-mail Administrativo</label>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Enviar Link de Recuperação</button>
            <a href="login.php" class="btn btn-link btn-sm text-decoration-none text-muted">Voltar ao login</a>
        </form>
    </div>
</body>
</html>