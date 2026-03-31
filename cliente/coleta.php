<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/coleta.php
 * Finalidade: Registro manual de preços (pesquisa de campo) com inteligência de dados.
 */

session_start();
require_once '../core/db.php';

// Busca mercados para o formulário
$instrucao_sql_mercados = "SELECT id, nome, regiao FROM mercados ORDER BY nome ASC";
$lista_de_mercados_cadastrados = $pdo->query($instrucao_sql_mercados)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Coleta Manual - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f7f6; padding-bottom: 50px; font-family: 'Segoe UI', sans-serif; }
        .card-coleta { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .input-estilizado { padding: 15px; border-radius: 12px; font-size: 1.1rem; }
        .sessao-atacado { background-color: #fff3cd; border: 1px solid #ffe69c; border-radius: 15px; padding: 15px; }
        
        /* Estilo do Autocomplete */
        #container-sugestoes-coleta { 
            position: absolute; width: 100%; z-index: 2000; background: #fff; 
            border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            display: none; max-height: 250px; overflow-y: auto; border: 1px solid #eee;
        }
        .item-sugestao-clicavel { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
        .item-sugestao-clicavel:hover { background-color: #e7f1ff; color: #0d6efd; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="simulador.php" class="btn btn-light shadow-sm border rounded-circle"><i class="bi bi-arrow-left"></i></a>
        <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
    </div>

    <div class="card card-coleta p-4">
        <h5 class="fw-bold text-dark mb-4 text-center">Registrar Pesquisa de Preço</h5>
        
        <form action="processar_coleta_manual.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">SUPERMERCADO</label>
                <select name="mercado_id" class="form-select input-estilizado border-primary" required>
                    <option value="">Onde você está pesquisando?</option>
                    <?php foreach($lista_de_mercados_cadastrados as $mercado): ?>
                        <option value="<?php echo $mercado['id']; ?>"><?php echo $mercado['nome']; ?> (<?php echo $mercado['regiao']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label small fw-bold text-muted">NOME DO PRODUTO</label>
                <input type="text" name="nome_produto" id="input_nome_coleta" class="form-control input-estilizado" placeholder="Ex: Arroz Tio João" autocomplete="off" onkeyup="funcaoBuscarProdutosLocal(this.value)" required>
                <!-- Caixa de Autocomplete -->
                <div id="container-sugestoes-coleta"></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">UNIDADE</label>
                    <select name="unidade" class="form-select input-estilizado">
                        <option value="UN">Unidade (UN)</option>
                        <option value="KG">Quilo (KG)</option>
                        <option value="L">Litro (L)</option>
                        <option value="CX">Caixa (CX)</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-success">VALOR UNITÁRIO</label>
                    <input type="text" name="valor_unitario" class="form-control input-estilizado fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraMoeda(this)" required>
                </div>
            </div>

            <!-- SEÇÃO DE ATACADO (BI ADICIONAL) -->
            <div class="sessao-atacado mb-4 shadow-sm">
                <h6 class="fw-bold mb-3 text-warning-emphasis"><i class="bi bi-tags-fill"></i> Oferta de Atacado?</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">QTD MÍNIMA</label>
                        <input type="number" name="quantidade_minima_atacado" class="form-control border-warning" placeholder="Ex: 6">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">VALOR ATACADO</label>
                        <input type="text" name="valor_atacado" class="form-control border-warning" placeholder="0,00" onkeyup="funcaoMascaraMoeda(this)">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold py-3">
                <i class="bi bi-cloud-upload"></i> SALVAR NO BANCO DE DADOS
            </button>
        </form>
    </div>
</div>

<script>
/**
 * Mascara de Moeda
 */
function funcaoMascaraMoeda(campo) {
    let valor_limpo = campo.value.replace(/\D/g, "");
    valor_limpo = (valor_limpo / 100).toFixed(2) + "";
    valor_limpo = valor_limpo.replace(".", ",");
    valor_limpo = valor_limpo.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor_limpo = valor_limpo.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo.value = valor_limpo;
}

/**
 * Autocomplete para Coleta Manual
 */
function funcaoBuscarProdutosLocal(texto) {
    const box = document.getElementById('container-sugestoes-coleta');
    if (texto.length < 2) { box.style.display = 'none'; return; }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(texto)}`)
        .then(res => res.json())
        .then(dados => {
            box.innerHTML = "";
            if (dados.length > 0) {
                box.style.display = 'block';
                dados.forEach(item => {
                    const div = document.createElement('div');
                    div.className = "item-sugestao-clicavel";
                    div.innerHTML = `<strong>${item.nome}</strong><br><small class='text-muted'>${item.marca}</small>`;
                    div.onclick = () => {
                        document.getElementById('input_nome_coleta').value = item.nome;
                        box.style.display = 'none';
                    };
                    box.appendChild(div);
                });
            } else { box.style.display = 'none'; }
        });
}
</script>
</body>
</html>