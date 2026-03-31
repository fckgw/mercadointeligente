<?php
/**
 * SISTEMA MERCADO INTELIGENTE
 * Arquivo: cliente/coleta.php
 * Finalidade: Registro manual de preços para pesquisa de campo.
 */

session_start();
require_once '../core/db.php';
$lista_mercados = $pdo->query("SELECT * FROM mercados ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
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
        body { background-color: #f4f7f6; padding-bottom: 50px; }
        .card-coleta { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .input-grande { padding: 15px; border-radius: 12px; }
        .sessao-atacado { background-color: #fff3cd; border: 1px solid #ffe69c; border-radius: 15px; padding: 15px; }
        #sugestoes-coleta { position: absolute; width: 100%; z-index: 2000; background: #fff; border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: none; max-height: 250px; overflow-y: auto; border: 1px solid #eee; }
        .item-sugestao { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
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
                <label class="form-label small fw-bold">SUPERMERCADO</label>
                <div class="input-group">
                    <select name="mercado_id" id="select_mercado_coleta" class="form-select input-grande border-primary" required>
                        <option value="">Onde está pesquisando?</option>
                        <?php foreach($lista_mercados as $mercado): ?>
                            <option value="<?php echo $mercado['id']; ?>"><?php echo $mercado['nome']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary px-3" onclick="abrirNovoMercado()"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>

            <div id="painel-mercado-coleta" style="display:none;" class="p-3 bg-light rounded-3 border mb-3">
                <input type="text" id="nome_novo_mercado_coleta" class="form-control mb-2" placeholder="Nome do Mercado">
                <button type="button" class="btn btn-success btn-sm w-100" onclick="gravarMercadoColeta()">CADASTRAR</button>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label small fw-bold">NOME DO PRODUTO</label>
                <input type="text" name="nome_produto" id="input_nome_coleta" class="form-control input-grande" placeholder="Arroz, Feijão..." autocomplete="off" onkeyup="buscarLocal(this.value)" required>
                <div id="sugestoes-coleta"></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">UNIDADE</label>
                    <select name="unidade" class="form-select input-grande"><option value="UN">Unidade</option><option value="KG">Quilo</option><option value="L">Litro</option></select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-success">VALOR UNITÁRIO</label>
                    <input type="text" name="valor_unitario" class="form-control input-grande fw-bold" placeholder="0,00" onkeyup="mascara(this)" required>
                </div>
            </div>

            <div class="sessao-atacado mb-4 shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-tags-fill"></i> Oferta de Atacado?</h6>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">A partir de (Qtd)</label><input type="number" name="qtd_atacado" class="form-control"></div>
                    <div class="col-6"><label class="form-label small">Valor Atacado</label><input type="text" name="valor_atacado" class="form-control" onkeyup="mascara(this)"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold py-3">SALVAR PESQUISA</button>
        </form>
    </div>
</div>

<script>
function mascara(o) {
    let v = o.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2) + "";
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    o.value = v;
}

function buscarLocal(t) {
    const box = document.getElementById('sugestoes-coleta');
    if (t.length < 2) { box.style.display = 'none'; return; }
    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(t)}`)
    .then(r => r.json()).then(dados => {
        box.innerHTML = "";
        if (dados.length > 0) {
            box.style.display = 'block';
            dados.forEach(p => {
                const d = document.createElement('div'); d.className = "item-sugestao";
                d.innerHTML = `<strong>${p.nome}</strong><br><small>${p.marca}</small>`;
                d.onclick = () => { document.getElementById('input_nome_coleta').value = p.nome; box.style.display = 'none'; };
                box.appendChild(d);
            });
        } else { box.style.display = 'none'; }
    });
}

function abrirNovoMercado() { document.getElementById('painel-mercado-coleta').style.display = 'block'; }
function gravarMercadoColeta() {
    const n = document.getElementById('nome_novo_mercado_coleta').value;
    if(!n) return;
    fetch('../api/registrar_mercado.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nome: n }) })
    .then(r => r.json()).then(data => {
        if(data.sucesso) {
            const select = document.getElementById('select_mercado_coleta');
            select.add(new Option(data.nome, data.id, true, true));
            document.getElementById('painel-mercado-coleta').style.display = 'none';
        }
    });
}
</script>
</body>
</html>