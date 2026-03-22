<?php 
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/mercados.php
 * Finalidade: Cadastrar e gerenciar supermercados e suas regiões.
 */

require_once 'sessao.php';
require_once '../core/db.php';

$mensagem = "";

// 1. Lógica para Cadastrar Mercado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $nome_mercado = trim($_POST['nome']);
    $regiao_mercado = trim($_POST['regiao']);

    if (!empty($nome_mercado) && !empty($regiao_mercado)) {
        $comando_insert = $pdo->prepare("INSERT INTO mercados (nome, regiao) VALUES (?, ?)");
        $comando_insert->execute([$nome_mercado, $regiao_mercado]);
        $mensagem = "Mercado cadastrado com sucesso!";
    }
}

// 2. Lógica para Excluir Mercado
if (isset($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    $comando_delete = $pdo->prepare("DELETE FROM mercados WHERE id = ?");
    $comando_delete->execute([$id_excluir]);
    header("Location: mercados.php?msg=excluido");
    exit;
}

// 3. Busca todos os mercados cadastrados
$lista_mercados = $pdo->query("SELECT * FROM mercados ORDER BY nome ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Mercados - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .card-mercado { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-add { border-radius: 10px; padding: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Painel</a>
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
    </div>

    <!-- Formulário de Cadastro -->
    <div class="card card-mercado p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-shop"></i> Cadastrar Novo Mercado</h5>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-success small py-2"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="row g-3">
            <input type="hidden" name="acao" value="cadastrar">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Nome do Mercado</label>
                <input type="text" name="nome" class="form-control" placeholder="Ex: Carrefour" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Região / Unidade</label>
                <input type="text" name="regiao" class="form-control" placeholder="Ex: SJC - Aquarius" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 btn-add">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Lista de Mercados -->
    <h5 class="fw-bold mb-3">Mercados Cadastrados</h5>
    <div class="row g-3">
        <?php if (empty($lista_mercados)): ?>
            <div class="col-12 text-center py-4 text-muted">Nenhum mercado cadastrado ainda.</div>
        <?php endif; ?>

        <?php foreach ($lista_mercados as $mercado): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-mercado p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><?php echo $mercado['nome']; ?></h6>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo $mercado['regiao']; ?></small>
                        </div>
                        <a href="?excluir=<?php echo $mercado['id']; ?>" class="btn btn-outline-danger btn-sm border-0" onclick="return confirm('Deseja excluir este mercado?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>