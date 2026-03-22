<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; height: 100vh; display: flex; align-items: center; }
        .hero-section { text-align: center; width: 100%; }
        .logo-home { max-width: 250px; margin-bottom: 2rem; }
        .btn-main { padding: 15px 30px; border-radius: 15px; font-weight: bold; width: 280px; margin: 10px; transition: 0.3s; }
    </style>
</head>
<body>

<div class="hero-section container">
    <img src="images/Logo_MercadoInteligente.png" alt="Mercado Inteligente" class="logo-home img-fluid">
    <h2 class="mb-5 text-secondary">Escolha como deseja prosseguir:</h2>

    <div class="d-flex flex-wrap justify-content-center">
        <a href="cliente/simulador.php" class="btn btn-primary btn-main shadow">🛒 Simulador de Compras</a>
        <a href="admin/login.php" class="btn btn-dark btn-main shadow">⚙️ Painel Administrativo</a>
    </div>
</div>

</body>
</html>