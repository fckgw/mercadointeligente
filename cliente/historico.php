<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/historico.php
 * Finalidade: Exibir histórico privado em GRID e permitir compartilhamento via WhatsApp.
 */

session_start();
require_once '../core/db.php';

// Proteção: Se não estiver logado, redireciona para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../admin/login.php");
    exit;
}

$identificador_usuario_logado = $_SESSION['usuario_id'];

/**
 * Consulta SQL: Agrupamos os itens pela data da coleta (minuto) e mercado
 * para simular o cupom fiscal gerado naquele momento.
 */
$instrucao_sql_historico = "
    SELECT 
        precos.data_da_coleta, 
        mercados.nome AS nome_mercado,
        GROUP_CONCAT(CONCAT(produtos.nome, ' (R$ ', precos.valor_unitario, ')') SEPARATOR '|') AS lista_produtos,
        SUM(precos.valor_unitario) AS valor_total_cupom
    FROM precos
    INNER JOIN produtos ON precos.produto_id = produtos.id
    INNER JOIN mercados ON precos.mercado_id = mercados.id
    WHERE precos.usuario_id = ?
    GROUP BY precos.data_da_coleta, mercados.id
    ORDER BY precos.data_da_coleta DESC
";

$comando_busca = $pdo->prepare($instrucao_sql_historico);
$comando_busca->execute([$identificador_usuario_logado]);
$lista_cupons_historico = $comando_busca->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Histórico - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .cartao-cupom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; background: #fff; }
        .cartao-cupom:hover { transform: translateY(-5px); }
        .estilo-recibo { font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; border-top: 1px dashed #ddd; padding-top: 10px; }
        .btn-whatsapp { background-color: #25D366; color: white; font-weight: bold; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="simulador.php" class="btn btn-sm btn-light border shadow-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
    </div>

    <h4 class="fw-bold mb-4 text-dark text-center">Minha Economia em SJC</h4>

    <div class="row g-3">
        <?php if (empty($lista_cupons_historico)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted"></i>
                <p class="mt-3 text-muted">Você ainda não realizou nenhuma coleta de preços.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($lista_cupons_historico as $cupom): 
            $data_formatada = date('d/m/Y H:i', strtotime($cupom['data_da_coleta']));
            $produtos_array = explode('|', $cupom['lista_produtos']);
        ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card cartao-cupom p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-0"><?php echo $cupom['nome_mercado']; ?></h6>
                            <small class="text-muted"><?php echo $data_formatada; ?></small>
                        </div>
                        <span class="badge bg-success text-white">R$ <?php echo number_format($cupom['valor_total_cupom'], 2, ',', '.'); ?></span>
                    </div>

                    <div class="estilo-recibo mt-2">
                        <?php foreach($produtos_array as $prod): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-truncate" style="max-width: 180px;"><?php echo $prod; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="btn btn-whatsapp btn-sm w-100 mt-3" 
                            onclick="compartilharWhatsApp('<?php echo $cupom['nome_mercado']; ?>', '<?php echo $data_formatada; ?>', '<?php echo number_format($cupom['valor_total_cupom'], 2, ',', '.'); ?>', '<?php echo addslashes($cupom['lista_produtos']); ?>')">
                        <i class="bi bi-whatsapp"></i> Compartilhar Cupom
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
/**
 * LÓGICA DE COMPARTILHAMENTO PROFISSIONAL
 */
function compartilharWhatsApp(mercado, data, total, itensBrutos) {
    // Organiza os itens para o texto do WhatsApp
    const listaFormatada = itensBrutos.split('|').map(item => "✅ " + item).join('%0A');
    
    // Constrói a mensagem com a marca do sistema e da empresa
    let mensagem = "*MERCADO INTELIGENTE - COTAÇÃO*%0A";
    mensagem += "-----------------------------------%0A";
    mensagem += "*Mercado:* " + mercado + "%0A";
    mensagem += "*Data:* " + data + "%0A";
    mensagem += "-----------------------------------%0A";
    mensagem += listaFormatada + "%0A";
    mensagem += "-----------------------------------%0A";
    mensagem += "*VALOR TOTAL: R$ " + total + "*%0A%0A";
    mensagem += "Powered by: _bdsoft.com.br_";

    // Abre o WhatsApp com o texto pronto
    window.open("https://wa.me/?text=" + mensagem, '_blank');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>