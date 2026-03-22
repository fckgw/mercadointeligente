<?php 
require_once 'sessao.php';
require_once '../core/db.php';

$produto_id = $_GET['id'] ?? 0;

// 1. Busca os dados do produto
$produto = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$produto->execute([$produto_id]);
$dados_produto = $produto->fetch();

if (!$dados_produto) die("Produto não encontrado.");

// 2. Busca histórico de preços ordenado por data para o gráfico
$historico = $pdo->prepare("
    SELECT valor_unitario, data_da_coleta, mercados.nome as mercado
    FROM precos 
    JOIN mercados ON precos.mercado_id = mercados.id
    WHERE produto_id = ? 
    ORDER BY data_da_coleta ASC
");
$historico->execute([$produto_id]);
$dados_historico = $historico->fetchAll();

// Prepara os dados para o Javascript do gráfico
$labels = [];
$valores = [];
foreach ($dados_historico as $h) {
    $labels[] = date('d/m/y', strtotime($h['data_da_coleta']));
    $valores[] = $h['valor_unitario'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BI - Histórico de Preços</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="produtos.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
        <span class="badge bg-info text-dark w-auto d-inline-block mb-2"><?php echo $dados_produto['marca']; ?></span>
        <h3 class="fw-bold"><?php echo $dados_produto['nome']; ?></h3>
        <p class="text-muted">Análise de variação de preços - São José dos Campos</p>
        
        <hr>

        <!-- Container do Gráfico -->
        <div style="height: 300px;">
            <canvas id="graficoPrecos"></canvas>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Detalhamento das Coletas</h5>
    <div class="table-responsive bg-white rounded-3 shadow-sm">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Data</th>
                    <th>Supermercado</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados_historico as $h): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($h['data_da_coleta'])); ?></td>
                        <td><?php echo $h['mercado']; ?></td>
                        <td class="fw-bold text-success">R$ <?php echo number_format($h['valor_unitario'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('graficoPrecos').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Preço em R$',
            data: <?php echo json_encode($valores); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: false }
        }
    }
});
</script>
</body>
</html>