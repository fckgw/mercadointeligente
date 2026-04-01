<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Interface mestre com Edição, Nota Fiscal, Alerta de Preço e BI Oficial.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../core/db.php';

$usuario_esta_logado_no_sistema = isset($_SESSION['usuario_id']);
$nome_do_usuario_para_exibicao   = $usuario_esta_logado_no_sistema ? $_SESSION['usuario_nome'] : "Visitante";
$data_do_ultimo_acesso_usuario   = $usuario_esta_logado_no_sistema ? ($_SESSION['ultimo_acesso_formatado'] ?? date('d/m/Y H:i')) : "";

try {
    $instrucao_sql_mercados = "SELECT id, nome FROM mercados ORDER BY nome ASC";
    $lista_de_mercados_disponiveis = $pdo->query($instrucao_sql_mercados)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $excecao_mercados) { $lista_de_mercados_disponiveis = []; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador Inteligente - Mercado Inteligente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 150px; font-family: 'Segoe UI', sans-serif; }
        .barra-superior { background: #fff; border-bottom: 1px solid #ddd; padding: 12px 0; position: sticky; top: 0; z-index: 1050; }
        .nome-destaque { color: #0d6efd; font-weight: 700; text-transform: uppercase; }
        .cartao-estilizado { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .campo-entrada { padding: 15px; border-radius: 12px; border: 1px solid #ced4da; }
        .area-recibo { background: #fff; padding: 25px; border: 1px solid #e0e0e0; font-family: monospace; border-radius: 4px; }
        .linha-item { border-bottom: 1px dashed #bbb; padding: 10px 0; }
        .item-atencao { background-color: #fff5f5; border-left: 5px solid #dc3545; }
        #container-sugestoes { position: absolute; width: 100%; z-index: 2100; background: #fff; display: none; max-height: 250px; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; }
        .item-sugestao { padding: 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; text-align: left; width: 100%; display: block; background: none; border: none; }
        .painel-finalizar { position: fixed; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, #f4f7f6 80%, transparent); padding: 20px; z-index: 2000; display: flex; justify-content: center; }
    </style>
</head>
<body>

<nav class="barra-superior shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="../index.php"><img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 40px;"></a>
        
        <div class="d-flex align-items-center text-end">
            <div class="me-3">
                <small>Olá, <b class="nome-destaque"><?php echo $nome_do_usuario_para_exibicao; ?></b></small><br>
                <small class="text-muted">Acesso: <?php echo $data_do_ultimo_acesso_usuario; ?></small>
            </div>
            
            <?php if ($usuario_esta_logado_no_sistema): ?>
                <a href="historico.php" class="btn btn-outline-primary btn-sm rounded-circle me-1" title="Histórico"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-outline-danger btn-sm rounded-circle" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row g-2 mb-4 px-2">
        <div class="col-6"><a href="coleta.php" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-primary fw-bold text-decoration-none text-center"><i class="bi bi-pencil-square d-block h4"></i><small>COLETA MANUAL</small></a></div>
        <div class="col-6"><button onclick="funcaoEscolherMetodoLeituraNotaFiscal()" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-dark fw-bold text-center"><i class="bi bi-qr-code-scan d-block h4"></i><small>LER NOTA FISCAL</small></button></div>
    </div>

    <!-- PASSO 1: MERCADO -->
    <div class="card cartao-estilizado p-4 mb-4" id="sessao-mercado">
        <h6 class="fw-bold text-primary mb-3 text-center text-uppercase small">Estabelecimento de Compra</h6>
        <div class="input-group mb-3">
            <select id="campo_identificador_mercado_selecionado" class="form-select campo-entrada shadow-sm">
                <option value="">Selecione o Supermercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary px-3 shadow-sm" onclick="funcaoExibirCadastroNovoMercado()"><i class="bi bi-plus-lg"></i></button>
        </div>

        <div id="container-cadastro-novo-mercado" style="display:none;" class="mt-2 p-3 bg-light rounded-3 border mb-3">
            <input type="text" id="input_nome_mercado_novo" class="form-control mb-2" placeholder="Nome do Mercado">
            <button class="btn btn-success btn-sm w-100 py-2 fw-bold" onclick="funcaoRegistrarNovoMercadoNoBancoDados()">SALVAR E USAR</button>
        </div>

        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoIniciarSimulador()">ABRIR MEU CARRINHO</button>
    </div>

    <!-- PASSO 2: INTERFACE -->
    <div id="sessao-interface-ativa" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill bg-white shadow-sm" onclick="window.location.reload()"><i class="bi bi-arrow-left"></i> Trocar Local</button>
            <span class="badge bg-primary px-3 py-2" id="etiqueta-mercado-visual">---</span>
        </div>

        <div class="card cartao-estilizado p-4 mb-4 border-top border-primary border-5">
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <input type="text" id="input_nome_produto_atual" class="form-control campo-entrada" placeholder="Procure o produto..." onkeyup="funcaoPesquisarProdutosNaBaseDinamica(this.value)">
                <input type="hidden" id="input_id_produto_selecionado">
                <div id="container-sugestoes"></div>
            </div>

            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="unid_med" id="u_un" value="UN" checked onchange="funcaoAjustarMascara()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="u_un">UNID.</label>
                <input type="radio" class="btn-check" name="unid_med" id="u_kg" value="KG" onchange="funcaoAjustarMascara()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="u_kg">QUILO</label>
                <input type="radio" class="btn-check" name="unid_med" id="u_litro" value="L" onchange="funcaoAjustarMascara()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="u_litro">LITRO</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">PREÇO UNITÁRIO</label>
                    <input type="text" id="input_valor_unitario" class="form-control campo-entrada fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoAplicarMascaraMoeda(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted" id="label-qtd-instr">QUANTIDADE</label>
                    <input type="text" id="input_quantidade" class="form-control campo-entrada text-center" inputmode="numeric" value="1" onkeyup="funcaoAplicarMascaraPeso(this)">
                </div>
            </div>

            <div class="alert alert-light border text-center p-2 mb-3">
                <small class="text-muted">Subtotal item: </small>
                <span class="fw-bold h5" id="label_previa_subtotal">R$ 0,00</span>
            </div>

            <button onclick="funcaoAdicionarAoCarrinho()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">ADICIONAR ITEM</button>
        </div>

        <div class="area-recibo shadow-sm mb-5">
            <div id="container-lista-recibo"></div>
            <div class="mt-4 border-top border-2 border-dark pt-3 d-flex justify-content-between h3 fw-bold">
                <span>TOTAL R$</span>
                <span id="label-valor-total">0,00</span>
            </div>
        </div>
    </div>
</div>

<div class="painel-finalizar" id="bloco-finalizar" style="display: none;">
    <button class="btn btn-success w-100 max-width-450 rounded-pill py-3 fw-bold shadow-lg" onclick="funcaoFinalizarGravacaoOficialNoBI()">FECHAR E SALVAR COMPRA</button>
</div>

<!-- MODAL CÂMERA -->
<div class="modal fade" id="modalCamera" tabindex="-1"><div class="modal-dialog modal-dialog-centered text-center"><div class="modal-content"><div class="modal-body"><div id="video-qr"></div><button class="btn btn-danger mt-3" data-bs-dismiss="modal" onclick="pararCamera()">CANCELAR</button></div></div></div></div>

<script>
let lista_itens_sessao = [];
let id_mercado_referencia = "";
let leitor_instancia = null;

function funcaoIniciarSimulador() {
    const sel = document.getElementById('campo_identificador_mercado_selecionado');
    id_mercado_referencia = sel.value;
    if (!id_mercado_referencia) return Swal.fire('Aviso', 'Selecione o mercado.', 'info');

    document.getElementById('sessao-mercado').style.display = 'none';
    document.getElementById('sessao-interface-ativa').style.display = 'block';
    document.getElementById('bloco-finalizar').style.display = 'flex';
    document.getElementById('etiqueta-mercado-visual').innerText = sel.options[sel.selectedIndex].text.toUpperCase();

    funcaoSincronizarServidor();
}

function funcaoSincronizarServidor() {
    if(<?php echo $usuario_esta_logado_no_sistema ? 'true' : 'false'; ?>) {
        fetch(`../api/gerenciar_temporarios.php?acao=listar_itens_temporarios&mercado_id=${id_mercado_referencia}`)
        .then(r => r.json()).then(d => { if(d.sucesso) { lista_itens_sessao = d.lista_itens; funcaoRenderizarGrade(); } });
    }
}

function funcaoAdicionarAoCarrinho() {
    const nome = document.getElementById('input_nome_produto_atual').value;
    const prodId = document.getElementById('input_id_produto_selecionado').value;
    const precoTxt = document.getElementById('input_valor_unitario').value;
    const qtdTxt = document.getElementById('input_quantidade').value;
    const unid = document.querySelector('input[name="unid_med"]:checked').value;

    if (!nome || !precoTxt || precoTxt === "0,00") return Swal.fire('Erro', 'Nome e Preço são obrigatórios.', 'warning');

    const vP = parseFloat(precoTxt.replace(/\./g, '').replace(',', '.'));
    const vQ = parseFloat(qtdTxt.replace(',', '.'));
    const sub = parseFloat((vP * vQ).toFixed(2));

    const item = { nome: nome, produto_id: prodId, preco: vP, quantidade: vQ, unidade: unid, subtotal: sub };

    fetch('../api/gerenciar_temporarios.php?acao=adicionar_item_temporario', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({...item, mercado_id: id_mercado_referencia})
    }).then(() => {
        funcaoSincronizarServidor();
        document.getElementById('input_nome_produto_atual').value = "";
        document.getElementById('input_id_produto_selecionado').value = "";
        document.getElementById('input_valor_unitario').value = "";
        document.getElementById('label_previa_subtotal').innerText = "R$ 0,00";
    });
}

function funcaoRenderizarGrade() {
    const container = document.getElementById('container-lista-recibo');
    container.innerHTML = "";
    let total = 0;

    lista_itens_sessao.forEach((item, i) => {
        const sub = Number(item.subtotal);
        total += sub;
        
        const isPrecoZero = (Number(item.preco) <= 0);
        const classeAlerta = isPrecoZero ? 'item-atencao' : '';
        const avisoPreco = isPrecoZero ? '<br><small class="text-danger fw-bold">Preço não identificado! Edite no lápis.</small>' : '';

        container.innerHTML += `
            <div class="linha-item d-flex justify-content-between align-items-center p-2 ${classeAlerta}">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small>${item.quantidade} ${item.unidade} x R$ ${Number(item.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                    ${avisoPreco}
                </div>
                <div class="text-end">
                    <div class="fw-bold">R$ ${sub.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <div class="mt-1">
                        <button class="btn btn-sm btn-light border" onclick="funcaoEditarItem(${i})"><i class="bi bi-pencil text-primary"></i></button>
                        <button class="btn btn-sm btn-light border" onclick="funcaoRemoverItem(${i}, ${item.id || 0})"><i class="bi bi-trash text-danger"></i></button>
                    </div>
                </div>
            </div>`;
    });
    document.getElementById('label-valor-total').innerText = total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverItem(idx, idB) {
    if(idB > 0) fetch(`../api/gerenciar_temporarios.php?acao=remover_item_rascunho&id=${idB}`);
    lista_itens_sessao.splice(idx, 1);
    funcaoRenderizarGrade();
}

function funcaoEditarItem(idx) {
    const it = lista_itens_sessao[idx];
    document.getElementById('input_nome_produto_atual').value = it.nome;
    document.getElementById('input_id_produto_selecionado').value = it.produto_id || "";
    document.getElementById('input_valor_unitario').value = Number(it.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    if(it.unidade === 'KG') document.getElementById('u_kg').checked = true;
    else if(it.unidade === 'L') document.getElementById('u_litro').checked = true;
    else document.getElementById('u_un').checked = true;
    
    funcaoAjustarMascara();
    document.getElementById('input_quantidade').value = it.quantidade.toLocaleString('pt-BR', {minimumFractionDigits: (it.unidade === 'UN' ? 0 : 3)});
    
    funcaoRemoverItem(idx, it.id || 0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function funcaoEscolherMetodoLeituraNotaFiscal() {
    if(!id_mercado_referencia) return Swal.fire('Aviso', 'Selecione o mercado primeiro.', 'warning');
    Swal.fire({
        title: 'Importar Nota',
        showDenyButton: true, showCancelButton: true,
        confirmButtonText: 'Colar Link', denyButtonText: 'Abrir Câmera'
    }).then((res) => {
        if(res.isConfirmed) {
            Swal.fire({ title: 'URL da Nota', input: 'text' }).then(r => { if(r.value) processarNota(r.value); });
        } else if(res.isDenied) {
            new bootstrap.Modal(document.getElementById('modalCamera')).show();
            leitor_instancia = new Html5Qrcode("video-qr");
            leitor_instancia.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, (txt) => {
                pararCamera();
                bootstrap.Modal.getInstance(document.getElementById('modalCamera')).hide();
                processarNota(txt);
            });
        }
    });
}

function processarNota(url) {
    Swal.fire({ title: 'Importando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('../api/processar_nfce.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ url: url, mercado_id: id_mercado_referencia })
    }).then(r => r.json()).then(d => {
        if(d.sucesso) { Swal.fire('Pronto!', 'Itens carregados. Verifique preços zerados!', 'success').then(() => funcaoSincronizarServidor()); }
        else { Swal.fire('Erro', d.mensagem_erro, 'error'); }
    });
}

function pararCamera() { if(leitor_instancia) leitor_instancia.stop(); }

function funcaoFinalizarGravacaoOficialNoBI() {
    if (lista_itens_sessao.length === 0) return;
    
    // VALIDAR PREÇOS ZERADOS
    const temPrecoZero = lista_itens_sessao.some(i => Number(i.preco) <= 0);
    if(temPrecoZero) return Swal.fire('Atenção', 'Existem itens com preço R$ 0,00. Corrija-os clicando no lápis antes de salvar.', 'error');

    Swal.fire({ title: 'Gravando BI...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('../api/gerenciar_temporarios.php?acao=finalizar_e_gravar_oficial', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ mercado_id: id_mercado_referencia })
    }).then(r => r.json()).then(d => {
        if(d.sucesso) { Swal.fire('Sucesso!', 'BI Atualizado!', 'success').then(() => location.reload()); }
        else { Swal.fire('Erro', d.mensagem_erro, 'error'); }
    });
}

function funcaoPesquisarProdutosNaBaseDinamica(termo) {
    const cx = document.getElementById('container-sugestoes');
    if (termo.length < 2) return cx.style.display = 'none';
    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(termo)}`)
    .then(r => r.json()).then(produtos => {
        cx.innerHTML = "";
        if (produtos.length > 0) {
            cx.style.display = 'block';
            produtos.forEach(p => {
                const b = document.createElement('button');
                b.className = "item-sugestao";
                b.innerHTML = `<strong>${p.nome}</strong> - <small>${p.marca}</small>`;
                b.onclick = () => { 
                    document.getElementById('input_nome_produto_atual').value = p.nome;
                    document.getElementById('input_id_produto_selecionado').value = p.id;
                    cx.style.display = 'none';
                };
                cx.appendChild(b);
            });
        }
    });
}

function funcaoMascaraMoeda(c) {
    let v = c.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2).replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    c.value = v;
    funcaoCalcularSubtotal();
}
function funcaoAplicarMascaraMoeda(c) { funcaoMascaraMoeda(c); }

function funcaoAplicarMascaraPeso(c) {
    const med = document.querySelector('input[name="unid_med"]:checked').value;
    if (med === 'UN') c.value = c.value.replace(/\D/g, "");
    else { let v = c.value.replace(/\D/g, ""); c.value = (v / 1000).toFixed(3).replace(".", ","); }
    funcaoCalcularSubtotal();
}

function funcaoCalcularSubtotal() {
    const p = document.getElementById('input_valor_unitario').value;
    const q = document.getElementById('input_quantidade').value;
    if (p && q) {
        const nP = parseFloat(p.replace(/\./g, '').replace(',', '.'));
        const nQ = parseFloat(q.replace(',', '.'));
        document.getElementById('label_previa_subtotal').innerText = "R$ " + (nP * nQ).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }
}

function funcaoAjustarMascara() {
    const m = document.querySelector('input[name="unid_med"]:checked').value;
    document.getElementById('label-qtd-instr').innerText = (m === 'UN') ? 'QUANTIDADE' : 'PESO / LITRO';
    document.getElementById('input_quantidade').value = (m === 'UN') ? "1" : "0,000";
    funcaoCalcularSubtotal();
}

function funcaoExibirCadastroNovoMercado() { document.getElementById('container-cadastro-novo-mercado').style.display = 'block'; }
function funcaoRegistrarNovoMercadoNoBancoDados() {
    const nome = document.getElementById('input_nome_mercado_novo').value;
    if(!nome) return;
    fetch('../api/registrar_mercado.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ nome: nome }) })
    .then(r => r.json()).then(data => {
        if (data.sucesso) {
            const select = document.getElementById('campo_identificador_mercado_selecionado');
            const option = new Option(nome, data.id, true, true);
            select.add(option);
            document.getElementById('container-cadastro-novo-mercado').style.display = 'none';
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>