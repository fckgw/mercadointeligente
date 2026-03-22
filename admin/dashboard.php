<?php 
require_once 'sessao.php';
require_once '../core/db.php';

// Estatísticas rápidas
$total_produtos = $pdo->query("SELECT count(id) FROM produtos")->fetchColumn();
$total_precos = $pdo->query("SELECT count(id) FROM precos")->fetchColumn();
$total_mercados = $pdo->query("SELECT count(id) FROM mercados")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .dashboard-card { border: none; border-radius: 15px; transition: 0.3s; height: 100%; text-decoration: none; color: inherit; border: 1px solid #eee; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .icon-box { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4 py-3">
    <div class="container">
        <span class="navbar-brand fw-bold">PAINEL ADMINISTRATIVO</span>
        <div class="d-flex align-items-center">
            <span class="text-white-50 small me-3 d-none d-md-block"><?php echo $_SESSION['usuario_nome']; ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center mb-5">
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 60px;">
    </div>

    <!-- Resumo -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm text-center">
                <h3 class="fw-bold mb-0"><?php echo $total_produtos; ?></h3>
                <small class="text-muted">Produtos</small>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm text-center">
                <h3 class="fw-bold mb-0"><?php echo $total_mercados; ?></h3>
                <small class="text-muted">Mercados</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-0 shadow-sm text-center">
                <h3 class="fw-bold mb-0 text-success"><?php echo $total_precos; ?></h3>
                <small class="text-muted">Cotações Realizadas</small>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-4">Gerenciamento</h5>
    
    <div class="row g-4">
        <!-- BOTÃO GESTÃO DE MERCADOS -->
        <div class="col-12 col-md-4">
            <a href="mercados.php" class="card dashboard-card p-4">
                <div class="icon-box bg-info text-white"><i class="bi bi-shop"></i></div>
                <h5 class="fw-bold">Supermercados</h5>
                <p class="text-muted small">Cadastre os mercados de São José dos Campos e região.</p>
            </a>
        </div>

        <!-- BOTÃO IMPORTAR -->
        <div class="col-12 col-md-4">
            <a href="importar.php" class="card dashboard-card p-4">
                <div class="icon-box bg-primary text-white"><i class="bi bi-cloud-arrow-up"></i></div>
                <h5 class="fw-bold">Coleta (Scraper)</h5>
                <p class="text-muted small">Alimente o sistema colando o HTML ou URL dos mercados.</p>
            </a>
        </div>

        <!-- BOTÃO PRODUTOS -->
        <div class="col-12 col-md-4">
            <a href="produtos.php" class="card dashboard-card p-4">
                <div class="icon-box bg-success text-white"><i class="bi bi-box-seam"></i></div>
                <h5 class="fw-bold">Base de Produtos</h5>
                <p class="text-muted small">Visualize, pesquise e filtre produtos categorizados.</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>