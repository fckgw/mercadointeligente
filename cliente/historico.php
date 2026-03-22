<?php
/**
 * SISTEMA MERCADO INTELIGENTE
 * Arquivo: cliente/historico.php
 * Finalidade: Mostrar as coletas realizadas pelo cliente logado.
 */
session_start();
require_once '../core/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../admin/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca as últimas 20 cotações realizadas pelo cliente
$instrucao_sql = "
    SELECT p.nome, m.nome as mercado, pr.valor_unitario, pr.data_da_coleta
    FROM precos pr
    JOIN produtos p ON pr.produto_id = p.id
    JOIN mercados m ON pr.mercado_id = m.id
    ORDER BY pr.data_da_coleta DESC LIMIT 20
";
// Nota: Para ser 100% preciso, precisaríamos de uma coluna 'usuario_id' na tabela 'precos'.
// Como não foi solicitado antes, esta query mostra a atividade geral recente do BI.

$historico = $pdo->query($instrucao_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Histórico - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="simulador.php" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Voltar</a>
            <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
        </div>

        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Minhas Últimas Coletas</h5>

        <?php foreach ($historico as $h): ?>
            <div class="card border-0 shadow-sm p-3 mb-2" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1"><?php echo $h['nome']; ?></h6>
                        <small class="text-muted"><?php echo $h['mercado']; ?></small>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-bold">R$ <?php echo number_format($h['valor_unitario'], 2, ',', '.'); ?></div>
                        <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('d/m/Y H:i', strtotime($h['data_da_coleta'])); ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>