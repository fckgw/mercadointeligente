<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../core/db.php';

$usuario_logado_atualmente = isset($_SESSION['usuario_id']);
$nome_do_usuario_logado       = $usuario_logado_atualmente ? $_SESSION['usuario_nome'] : "Visitante";
// Cabeçalho com Data e Hora
$data_ultimo_acesso_usuario   = date('d/m/Y') . " às " . date('H:i');

try {
    $instrucao_sql_mercados = "SELECT id, nome, regiao FROM mercados ORDER BY nome ASC";
    $comando_busca_mercados = $pdo->query($instrucao_sql_mercados);
    $lista_de_mercados_disponiveis = $comando_busca_mercados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $excecao_banco) {
    $lista_de_mercados_disponiveis = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador - Mercado Inteligente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 160px; font-family: 'Segoe UI', sans-serif; }
        .navbar-identidade { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 10px 0; position: sticky; top: 0; z-index: 1050; }
        .texto-identificacao { font-size: 0.75rem; line-height: 1.2; color: #555; }
        .nome-destaque { color: #0d6efd; font-weight: 700; text-transform: uppercase; }
        .cartao-estilizado { border: none; border-radius: 20px; box-shadow: 0 4px 25px rgba(0,0,0,0.06); background: #fff; }
        .campo-entrada { padding: 15px; border-radius: 12px; font-size: 1.1rem; border: 1px solid #ced4da; }
        .area-recibo-cupom { background-color: #ffffff; padding: 25px; border: 1px solid #e0e0e0; font-family: 'Courier New', Courier, monospace; position: relative; border-radius: 4px; }
        .linha-divisor-item { border-bottom: 1px dashed #bbb; padding: 12px 0; }
        .bloco-finalizar-fixo { position: fixed; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, #f4f7f6 80%, transparent); padding: 20px; z-index: 2000; display: flex; justify-content: center; }
        .botao-salvar-bi-grande { width: 100%; max-width: 450px; border-radius: 50px; padding: 18px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4); border: 2px solid #fff; }
        #container-sugestoes-base-dados { position: absolute; width: 100%; z-index: 2100; background: #fff; border-radius: 0 0 15px 15px; box-shadow: 0 12px 25px rgba(0,0,0,0.15); display: none; max-height: 250px; overflow-y: auto; border: 1px solid #ddd; }
        .item-sugestao-clicavel { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; text-align: left; width: 100%; display: block; background: none; border: none; }
        .item-sugestao-clicavel:hover { background-color: #e7f1ff; color: #0d6efd; }
    </style>
</head>
<body>

<nav class="navbar-identidade shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="../index.php"><img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 38px;"></a>
        
        <div class="d-flex align-items-center text-end">
            <div class="texto-identificacao me-2">
                <span>Olá, <b class="nome-destaque"><?php echo $nome_do_usuario_logado; ?></b></span><br>
                <span>Acesso: <?php echo $data_ultimo_acesso_usuario; ?></span>
            </div>
            
            <?php if ($usuario_logado_atualmente): ?>
                <a href="historico.php" class="btn btn-outline-primary btn-sm rounded-circle me-1"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-outline-danger btn-sm rounded-circle"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">

    <!-- BOTÕES DE ATALHO RÁPIDO (COLETA E NOTA FISCAL) -->
    <div class="row g-2 mb-4 px-2" id="atalhos-topo">
        <div class="col-6">
            <a href="coleta.php" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-primary fw-bold text-decoration-none text-center">
                <i class="bi bi-pencil-square d-block h4"></i><small>COLETA MANUAL</small>
            </a>
        </div>
        <div class="col-6">
            <button onclick="funcaoAbrirLeitorNotaFiscalSefaz()" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-dark fw-bold text-center">
                <i class="bi bi-qr-code-scan d-block h4"></i><small>LER NOTA FISCAL</small>
            </button>
        </div>
    </div>

    <!-- PASSO 1: SELEÇÃO -->
    <div class="card cartao-estilizado p-4 mb-4" id="sessao-seletor-mercado">
        <h6 class="fw-bold text-primary mb-3 text-center text-uppercase small">Onde você está agora?</h6>
        <div class="input-group mb-4">
            <select id="campo_id_mercado_selecionado" class="form-select campo-entrada border-primary shadow-sm">
                <option value="">Clique para selecionar o mercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoValidarEAvancarParaInterface()">
            ABRIR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: INTERFACE -->
    <div id="sessao-interface-lancamento" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill bg-white shadow-sm" onclick="window.location.reload()"><i class="bi bi-arrow-left"></i> Trocar Mercado</button>
            <span class="badge bg-primary text-white px-3 py-2" id="etiqueta_mercado_nome_visual">---</span>
        </div>

        <div class="card cartao-estilizado p-4 mb-4 border-top border-primary border-5">
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <div class="input-group">
                    <input type="text" id="input_nome_produto_principal" class="form-control campo-entrada" placeholder="Nome do produto..." autocomplete="off" onkeyup="funcaoPesquisarBaseLocalDinamica(this.value)">
                    <button class="btn btn-warning px-3" onclick="funcaoAbrirModalIAGoogle()"><i class="bi bi-google"></i> IA</button>
                </div>
                <div id="container-sugestoes-base-dados"></div>
            </div>

            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="unidade_medida" id="un_unidade" value="UN" checked onchange="funcaoAjustarMascaraEPrevia()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="un_unidade">UNID.</label>
                <input type="radio" class="btn-check" name="unidade_medida" id="un_quilo" value="KG" onchange="funcaoAjustarMascaraEPrevia()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="un_quilo">KG</label>
                <input type="radio" class="btn-check" name="unidade_medida" id="un_litro" value="L" onchange="funcaoAjustarMascaraEPrevia()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="un_litro">LITRO</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">PREÇO UNITÁRIO</label>
                    <input type="text" id="input_valor_unitario" class="form-control campo-entrada fw-bold text-success" inputmode="numeric" onkeyup="funcaoAplicarMascaraDinheiro(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted" id="label-quantidade-instrucao">QTD / PESO</label>
                    <input type="text" id="input_quantidade_lancamento" class="form-control campo-entrada text-center" inputmode="numeric" value="1" onkeyup="funcaoAplicarMascaraPeso(this)">
                </div>
            </div>

            <div class="alert alert-light border text-center p-2 mb-3 shadow-sm">
                <small class="text-muted">Subtotal previsto: </small>
                <span class="fw-bold text-dark h5" id="label_previa_valor_total">R$ 0,00</span>
            </div>

            <button onclick="funcaoAdicionarNovoItemNoRecibo()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <!-- GRID DO CUPOM FISCAL -->
        <div class="area-recibo-cupom shadow-sm mb-5">
            <div id="container-itens-renderizados-lista"></div>
            <div class="mt-4 border-top border-2 border-dark pt-3 d-flex justify-content-between h3 fw-bold">
                <span>TOTAL R$</span>
                <span id="valor_total_cupom_label">0,00</span>
            </div>
        </div>
    </div>
</div>

<div class="bloco-finalizar-fixo" id="bloco-botao-finalizar-bi" style="display: none;">
    <button class="btn btn-success botao-salvar-bi-grande shadow-lg" onclick="funcaoFinalizarGravacaoNoBI()">
        <i class="bi bi-cloud-arrow-up-fill me-2"></i> SALVAR COMPRA NO BI
    </button>
</div>

<!-- MODAL GOOGLE IA -->
<div class="modal fade" id="modalGoogleIA" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-dark"><i class="bi bi-google text-primary"></i> Pesquisa Inteligente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><div class="gcse-search"></div></div>
        </div>
    </div>
</div>

<script>
let lista_carrinho_dinamica = [];
let identificador_mercado_referencia = "";

function funcaoValidarEAvancarParaInterface() {
    const sel = document.getElementById('campo_id_mercado_selecionado');
    identificador_mercado_referencia = sel.value;
    if (!identificador_mercado_referencia) { Swal.fire('Aviso', 'Selecione o mercado.', 'info'); return; }
    document.getElementById('sessao-seletor-mercado').style.display = 'none';
    document.getElementById('sessao-interface-lancamento').style.display = 'block';
    document.getElementById('bloco-botao-finalizar-bi').style.display = 'flex';
    document.getElementById('etiqueta_mercado_nome_visual').innerText = sel.options[sel.selectedIndex].text.toUpperCase();
}

function funcaoAdicionarNovoItemNoRecibo() {
    const nome = document.getElementById('input_nome_produto_principal').value;
    const precoTxt = document.getElementById('input_valor_unitario').value;
    const qtdTxt = document.getElementById('input_quantidade_lancamento').value;
    const unidade = document.querySelector('input[name="unidade_medida"]:checked').value;

    if (!nome || !precoTxt || precoTxt === "0,00") {
        Swal.fire('Aviso', 'Preencha o nome e o preço.', 'warning'); return;
    }

    const vUnit = parseFloat(precoTxt.replace(/\./g, '').replace(',', '.'));
    const vQtd = parseFloat(qtdTxt.replace(',', '.'));

    lista_carrinho_dinamica.push({
        nome: nome,
        preco: vUnit,
        quantidade: vQtd,
        unidade: unidade,
        subtotal: vUnit * vQtd
    });

    funcaoRenderVisual();
    document.getElementById('input_nome_produto_principal').value = "";
    document.getElementById('input_valor_unitario').value = "";
    document.getElementById('label_previa_valor_total').innerText = "R$ 0,00";
    document.getElementById('input_nome_produto_principal').focus();
}

function funcaoRenderVisual() {
    const container = document.getElementById('container-itens-renderizados-lista');
    container.innerHTML = "";
    let total = 0;

    lista_carrinho_dinamica.forEach((item, i) => {
        total += item.subtotal;
        const dec = (item.unidade === 'UN') ? 0 : 3;
        container.innerHTML += `
            <div class="linha-divisor-item d-flex justify-content-between align-items-center">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small>${item.quantidade.toLocaleString('pt-BR', {minimumFractionDigits: dec})} ${item.unidade} x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">R$ ${item.subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    <div class="mt-1">
                        <button class="btn btn-sm btn-outline-primary border-0" onclick="funcaoEditarItem(${i})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="funcaoRemoverItem(${i})" title="Remover"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>`;
    });
    document.getElementById('valor_total_cupom_label').innerText = total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function funcaoRemoverItem(idx) {
    lista_carrinho_dinamica.splice(idx, 1);
    funcaoRenderVisual();
}

/**
 * FUNÇÃO DE EDIÇÃO (LÁPIS)
 */
function funcaoEditarItem(idx) {
    const item = lista_carrinho_dinamica[idx];
    
    // Preenche os campos novamente
    document.getElementById('input_nome_produto_principal').value = item.nome;
    document.getElementById('input_valor_unitario').value = item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    // Seleciona a unidade correta
    if(item.unidade === 'KG') document.getElementById('un_quilo').checked = true;
    else if(item.unidade === 'L') document.getElementById('un_litro').checked = true;
    else document.getElementById('un_unidade').checked = true;
    
    funcaoAjustarMascaraEPrevia();
    document.getElementById('input_quantidade_lancamento').value = item.quantidade.toLocaleString('pt-BR', {minimumFractionDigits: (item.unidade === 'UN' ? 0 : 3)});
    
    // Remove da lista para "re-adicionar"
    funcaoRemoverItem(idx);
    
    // Rola para o topo para facilitar a correção
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function funcaoCalcularPreviaGastoItem() {
    const p = document.getElementById('input_valor_unitario').value;
    const q = document.getElementById('input_quantidade_lancamento').value;
    if (p && q) {
        const vP = parseFloat(p.replace(/\./g, '').replace(',', '.'));
        const vQ = parseFloat(q.replace(',', '.'));
        const res = (vP * vQ) || 0;
        document.getElementById('label_previa_valor_total').innerText = "R$ " + res.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function funcaoAplicarMascaraDinheiro(c) {
    let v = c.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2).replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    c.value = v;
    funcaoCalcularPreviaGastoItem();
}

function funcaoAplicarMascaraPeso(c) {
    const med = document.querySelector('input[name="unidade_medida"]:checked').value;
    if (med === 'UN') {
        c.value = c.value.replace(/\D/g, "");
    } else {
        let v = c.value.replace(/\D/g, "");
        c.value = (v / 1000).toFixed(3).replace(".", ",");
    }
    funcaoCalcularPreviaGastoItem();
}

function funcaoAjustarMascaraEPrevia() {
    const input = document.getElementById('input_quantidade_lancamento');
    const med = document.querySelector('input[name="unidade_medida"]:checked').value;
    input.value = (med === 'UN') ? "1" : "0,000";
    funcaoCalcularPreviaGastoItem();
}

function funcaoPesquisarBaseLocalDinamica(t) {
    const cx = document.getElementById('container-sugestoes-base-dados');
    if (t.length < 2) { cx.style.display = 'none'; return; }
    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(t)}`)
    .then(r => r.json()).then(dados => {
        cx.innerHTML = "";
        if (dados.length > 0) {
            cx.style.display = 'block';
            dados.forEach(p => {
                const b = document.createElement('button');
                b.className = "item-sugestao-clicavel";
                b.innerHTML = `<strong>${p.nome}</strong>`;
                b.onclick = () => { 
                    document.getElementById('input_nome_produto_principal').value = p.nome;
                    cx.style.display = 'none';
                };
                cx.appendChild(b);
            });
        }
    });
}

/**
 * FUNÇÃO LER NOTA FISCAL (QR CODE)
 */
function funcaoAbrirLeitorNotaFiscalSefaz() {
    Swal.fire({ title: 'Importar Nota Fiscal SP', input: 'text', inputLabel: 'Link ou URL do QR Code', showCancelButton: true, confirmButtonText: 'Processar' }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({ title: 'Extraindo dados...', didOpen: () => { Swal.showLoading(); } });
            let f = new FormData(); f.append('url_nfce', result.value);
            fetch('../api/processar_nfce.php', { method: 'POST', body: f }).then(r => r.json()).then(data => {
                if (data.sucesso) {
                    data.itens.forEach(i => {
                        const v = parseFloat(i.valor) || 0; const q = parseFloat(i.quantidade) || 0;
                        lista_carrinho_dinamica.push({ nome: i.nome, preco: v, quantidade: q, unidade: i.unidade || 'UN', subtotal: v * q });
                    });
                    document.getElementById('sessao-seletor-mercado').style.display = 'none';
                    document.getElementById('sessao-interface-lancamento').style.display = 'block';
                    document.getElementById('bloco-botao-finalizar-bi').style.display = 'flex';
                    funcaoRenderVisual();
                    Swal.fire('Pronto!', 'Itens da nota carregados.', 'success');
                }
            });
        }
    });
}

function funcaoFinalizarGravacaoNoBI() {
    if (lista_carrinho_dinamica.length === 0) return;
    Swal.fire({ title: 'Salvando no BI...', didOpen: () => { Swal.showLoading(); } });
    fetch('salvar_carrinho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mercado_id: identificador_mercado_referencia, itens: lista_carrinho_dinamica })
    }).then(r => r.json()).then(data => {
        if (data.sucesso) {
            Swal.fire('Sucesso!', 'Compra salva com sucesso.', 'success').then(() => location.reload());
        }
    });
}

function funcaoAbrirModalIAGoogle() { new bootstrap.Modal(document.getElementById('modalGoogleIA')).show(); }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>