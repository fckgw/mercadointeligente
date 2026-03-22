<?php
require_once 'sessao.php';
require_once '../core/db.php';
require_once '../core/scraper.php';

// Função cURL para buscar URL
function buscarConteudoRemoto($url_alvo) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_alvo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resultado = curl_exec($ch);
    curl_close($ch);
    return $resultado;
}

$novos_itens = 0;
$itens_atualizados = 0;
$total_processado = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mercado_id = $_POST['mercado_id'];
    $url = $_POST['url'] ?? '';
    $html_manual = $_POST['html'] ?? '';

    $html_final = !empty($url) ? buscarConteudoRemoto($url) : $html_manual;

    if (empty($html_final)) {
        die("Erro: Conteúdo não obtido. Verifique a URL ou cole o HTML manualmente.");
    }

    $produtos = extrairProdutosDaPaginaHtml($html_final);

    try {
        $pdo->beginTransaction();
        foreach ($produtos as $p) {
            $total_processado++;
            
            // 1. Lógica de Produto Único
            $stmt = $pdo->prepare("SELECT id FROM produtos WHERE nome = ? LIMIT 1");
            $stmt->execute([$p['nome']]);
            $produto_existente = $stmt->fetch();

            if ($produto_existente) {
                $produto_id = $produto_existente['id'];
                // Atualiza marca/categoria se necessário
                $upd = $pdo->prepare("UPDATE produtos SET marca = ?, categoria = ? WHERE id = ?");
                $upd->execute([$p['marca'], $p['categoria'], $produto_id]);
                $itens_atualizados++;
            } else {
                // Cadastra novo
                $ins = $pdo->prepare("INSERT INTO produtos (nome, marca, categoria) VALUES (?, ?, ?)");
                $ins->execute([$p['nome'], $p['marca'], $p['categoria']]);
                $produto_id = $pdo->lastInsertId();
                $novos_itens++;
            }

            // 2. Lógica de Histórico (BI)
            // IMPORTANTE: Sempre inserimos um novo preço. 
            // O gráfico de variação usará esses dados no futuro.
            $ins_preco = $pdo->prepare("INSERT INTO precos (produto_id, mercado_id, valor_unitario, data_da_coleta) VALUES (?, ?, ?, NOW())");
            $ins_preco->execute([$produto_id, $mercado_id, $p['preco']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro no banco: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo da Importação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card shadow border-0 p-4" style="border-radius: 20px;">
                <div class="display-1 text-success mb-3">✅</div>
                <h2 class="fw-bold">Importação Concluída!</h2>
                <p class="text-muted">A base de São José dos Campos foi atualizada.</p>

                <div class="list-group list-group-flush text-start my-4">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total de itens lidos:</span>
                        <span class="fw-bold"><?php echo $total_processado; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between text-primary">
                        <span>Novos produtos cadastrados:</span>
                        <span class="fw-bold"><?php echo $novos_itens; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between text-success">
                        <span>Preços/Cotações salvas:</span>
                        <span class="fw-bold"><?php echo $total_processado; ?></span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="produtos.php" class="btn btn-primary btn-lg shadow">Ver Base de Dados</a>
                    <a href="importar.php" class="btn btn-outline-secondary">Nova Importação</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>