<?php 
require_once 'sessao.php';
require_once '../core/db.php';
$lista_mercados = $pdo->query("SELECT * FROM mercados ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coleta Inteligente - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .card-importacao { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        /* Estilo do Overlay de Carregamento */
        #loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.8); z-index: 9999;
            display: none; align-items: center; justify-content: center; flex-direction: column;
        }
    </style>
</head>
<body>

<!-- Overlay de Carregamento -->
<div id="loading-overlay">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
    <h5 class="mt-3 fw-bold">Processando Inteligência de Dados...</h5>
    <p class="text-muted">Isso pode levar alguns segundos dependendo da quantidade de itens.</p>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <img src="../images/Logo_MercadoInteligente.png" style="max-height: 50px;">
            </div>

            <div class="card card-importacao p-4">
                <form id="form-importar" action="processar.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold small">1. SELECIONE O SUPERMERCADO</label>
                        <select name="mercado_id" class="form-select form-select-lg" required>
                            <option value="">Escolha um mercado cadastrado...</option>
                            <?php foreach ($lista_mercados as $mercado): ?>
                                <option value="<?php echo $mercado['id']; ?>"><?php echo $mercado['nome']; ?> (<?php echo $mercado['regiao']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-primary">OPÇÃO A: LINK DA CATEGORIA</label>
                        <input type="url" name="url" class="form-control" placeholder="https://mercado.carrefour.com.br/...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">OPÇÃO B: COLAR CÓDIGO FONTE (HTML)</label>
                        <textarea name="html" class="form-control" rows="5" placeholder="Caso a URL seja bloqueada, cole o HTML aqui..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow">Iniciar Coleta de Preços</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Mostra o loading ao enviar o formulário
    document.getElementById('form-importar').onsubmit = function() {
        document.getElementById('loading-overlay').style.display = 'flex';
    };
</script>
</body>
</html>