<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Simulador de compras para visitantes e clientes cadastrados.
 * Regra: Clientes logados possuem modo Offline e Histórico. Visitantes apenas Online.
 */

// Inicia a sessão para verificar se o usuário está autenticado
session_start();

// Importação da conexão com o banco de dados
require_once '../core/db.php';

// Verifica se o usuário está logado para definir as permissões de Histórico e Offline
$usuario_esta_logado = isset($_SESSION['usuario_id']);
$nome_do_usuario_logado = $usuario_esta_logado ? $_SESSION['usuario_nome'] : "Visitante";

/**
 * Consulta para carregar os supermercados disponíveis para a simulação.
 */
$instrucao_sql_mercados = "SELECT id, nome, regiao FROM mercados ORDER BY nome ASC";
$consulta_mercados = $pdo->query($instrucao_sql_mercados);
$lista_de_mercados_disponiveis = $consulta_mercados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador - Mercado Inteligente</title>
    
    <!-- CSS e Ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Programmable Search Engine -->
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 120px; font-family: 'Segoe UI', sans-serif; }
        .cartao-estilizado { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #fff; }
        .input-grande { padding: 15px; border-radius: 12px; font-size: 1.1rem; }
        .cupom-fiscal { background: #fff; padding: 25px; border: 1px solid #ddd; font-family: 'Courier New', monospace; position: relative; }
        .linha-item-carrinho { border-bottom: 1px dashed #ccc; padding: 10px 0; }
        .botao-finalizar-fixo { position: fixed; bottom: 20px; left: 20px; right: 20px; border-radius: 50px; padding: 18px; font-weight: bold; z-index: 1000; box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4); }
        #container-sugestoes-locais { position: absolute; width: 100%; z-index: 2000; background: #fff; border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: none; max-height: 200px; overflow-y: auto; }
        .item-sugestao { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
        .item-sugestao:hover { background-color: #e7f1ff; }
        .item-resultado-ia { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; border-radius: 12px; margin-bottom: 5px; }
        .item-resultado-ia:hover { background-color: #f0f7ff; }
    </style>
</head>
<body>

<!-- CABEÇALHO DINÂMICO -->
<div class="bg-white p-3 shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 40px;">
        <div>
            <?php if ($usuario_esta_logado): ?>
                <a href="historico.php" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Meu Histórico"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-sm btn-outline-danger rounded-pill" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <!-- PASSO 1: SELECIONAR MERCADO -->
    <div class="card cartao-estilizado p-4 mb-4" id="sessao-selecao-mercado">
        <div class="text-center mb-3">
            <h6 class="fw-bold text-primary text-uppercase small">Bem-vindo, <?php echo $nome_do_usuario_logado; ?>!</h6>
            <p class="text-muted small">Onde você está fazendo suas compras?</p>
        </div>
        
        <div class="input-group mb-3">
            <select id="campo_identificador_mercado" class="form-select input-grande border-primary">
                <option value="">Selecione o Supermercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $mercado_singular): ?>
                    <option value="<?php echo $mercado_singular['id']; ?>"><?php echo $mercado_singular['nome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoIniciarCompra()">
            ABRIR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: INTERFACE DE LANÇAMENTO -->
    <div id="sessao-ativa-carrinho" style="display: none;">
        
        <div class="card cartao-estilizado p-4 mb-4 border-top border-primary border-5">
            <!-- Produto com Autocomplete e IA -->
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <div class="input-group">
                    <input type="text" id="campo_nome_do_produto" class="form-control input-grande" placeholder="O que deseja comprar?" autocomplete="off" onkeyup="funcaoBuscarSugestoesLocais(this.value)">
                    <button class="btn btn-warning px-3 shadow-sm" onclick="funcaoAbrirModalInteligenciaGoogle()"><i class="bi bi-google"></i> IA</button>
                </div>
                <div id="container-sugestoes-locais"></div>
            </div>

            <!-- Preço e Quantidade -->
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">VALOR (R$)</label>
                    <input type="text" id="campo_preco_do_produto" class="form-control input-grande fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraMoedaAutomatica(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted">QUANTIDADE</label>
                    <input type="number" id="campo_quantidade_do_produto" class="form-control input-grande text-center" value="1">
                </div>
            </div>

            <button onclick="funcaoAdicionarItemAoCarrinho()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-plus-lg me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <!-- VISUALIZAÇÃO ESTILO CUPOM -->
        <div class="visual-cupom-fiscal recibo-cupom-fiscal shadow-sm mb-5">
            <div class="text-center mb-4">
                <h5 class="fw-bold mb-0" id="exibicao_nome_mercado_cupom">MERCADO</h5>
                <small class="text-muted">CUPOM DE CONFERÊNCIA</small>
                <div class="mt-2 border-top border-1 border-dark"></div>
            </div>

            <div id="container-itens-cupom-fiscal">
                <div class="text-center py-4 text-muted small italic">Nenhum item lançado</div>
            </div>

            <div class="mt-4 border-top border-2 border-dark pt-3">
                <div class="d-flex justify-content-between h3 fw-bold">
                    <span>TOTAL R$</span>
                    <span id="valor_total_do_carrinho">0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTÃO DE FINALIZAÇÃO -->
<button id="btn-salvar-final-bi" class="btn btn-success botao-finalizar-fixo shadow-lg border-white border-2" style="display: none;" onclick="funcaoFinalizarEEnviarParaBase()">
    <i class="bi bi-cloud-check-fill me-2"></i> FECHAR E SALVAR COMPRA
</button>

<!-- MODAL IA GOOGLE -->
<div class="modal fade" id="modalInteligenciaGoogle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-dark"><i class="bi bi-google text-primary"></i> Busca Sugerida pela IA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="corpo-resultados-google" style="max-height: 400px; overflow-y: auto;">
                <!-- Preenchido via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
/**
 * CONFIGURAÇÕES DE AMBIENTE (PHP -> JS)
 */
const configuracao_usuario_logado = <?php echo $usuario_esta_logado ? 'true' : 'false'; ?>;
let array_carrinho_atual = [];
let identificador_mercado_selecionado = "";

/**
 * LÓGICA OFFLINE (APENAS SE LOGADO)
 */
document.addEventListener('DOMContentLoaded', function() {
    if (configuracao_usuario_logado) {
        const dados_armazenados_offline = localStorage.getItem('carrinho_offline_mercado_inteligente');
        if (dados_armazenados_offline) {
            array_carrinho_atual = JSON.parse(dados_armazenados_offline);
            identificador_mercado_selecionado = localStorage.getItem('carrinho_mercado_id');
            if (array_carrinho_atual.length > 0) {
                document.getElementById('sessao-selecao-mercado').style.display = 'none';
                document.getElementById('sessao-ativa-carrinho').style.display = 'block';
                document.getElementById('btn-salvar-final-bi').style.display = 'block';
                funcaoRenderizarCarrinhoNoCupom();
            }
        }
    }
});

function funcaoSalvarBackupOffline() {
    if (configuracao_usuario_logado) {
        localStorage.setItem('carrinho_offline_mercado_inteligente', JSON.stringify(array_carrinho_atual));
        localStorage.setItem('carrinho_mercado_id', identificador_mercado_selecionado);
    }
}

/**
 * INÍCIO DA COMPRA
 */
function funcaoIniciarCompra() {
    identificador_mercado_selecionado = document.getElementById('campo_identificador_mercado').value;
    const nome_mercado_texto = document.getElementById('campo_identificador_mercado').options[document.getElementById('campo_identificador_mercado').selectedIndex].text;
    
    if (!identificador_mercado_selecionado) {
        Swal.fire('Seleção Necessária', 'Por favor, escolha o mercado onde você está.', 'info');
        return;
    }

    document.getElementById('sessao-selecao-mercado').style.display = 'none';
    document.getElementById('sessao-ativa-carrinho').style.display = 'block';
    document.getElementById('btn-salvar-final-bi').style.display = 'block';
    document.getElementById('exibicao_nome_mercado_cupom').innerText = nome_mercado_texto.toUpperCase();
    funcaoSalvarBackupOffline();
}

/**
 * MÁSCARA DE MOEDA (Idoso Amigável)
 */
function funcaoMascaraMoedaParaIdosos(campo) {
    let valor_numerico = campo.value.replace(/\D/g, "");
    valor_numerico = (valor_numerico / 100).toFixed(2) + "";
    valor_numerico = valor_numerico.replace(".", ",");
    valor_numerico = valor_numerico.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor_numerico = valor_numerico.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo.value = valor_numerico;
}

/**
 * AUTOCOMPLETE BASE LOCAL
 */
function funcaoBuscarSugestoesLocais(termo) {
    const container = document.getElementById('container-sugestoes-locais');
    if (termo.length < 2) { container.style.display = 'none'; return; }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(termo)}`)
        .then(r => r.json())
        .then(dados => {
            container.innerHTML = "";
            if (dados.length > 0) {
                container.style.display = 'block';
                dados.forEach(p => {
                    const div = document.createElement('div');
                    div.className = "item-sugestao";
                    div.innerHTML = `<strong>${p.nome}</strong><br><small class='text-muted'>${p.marca}</small>`;
                    div.onclick = () => {
                        document.getElementById('campo_nome_do_produto').value = p.nome;
                        container.style.display = 'none';
                    };
                    container.appendChild(div);
                });
            } else { container.style.display = 'none'; }
        });
}

/**
 * IA GOOGLE (Captura o nome do produto)
 */
function funcaoAbrirModalInteligenciaGoogle() {
    const busca = document.getElementById('campo_nome_do_produto').value;
    const modal_google = new bootstrap.Modal(document.getElementById('modalInteligenciaGoogle'));
    const corpo_modal = document.getElementById('corpo-resultados-google');
    
    corpo_modal.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Consultando IA Google...</p></div>';
    modal_google.show();

    fetch(`../api/google_busca.php?termo=${encodeURIComponent(busca)}`)
        .then(res => res.json())
        .then(data => {
            corpo_modal.innerHTML = "";
            if (data.items) {
                data.items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = "item-resultado-ia shadow-sm border mb-2";
                    div.innerHTML = `<strong>${item.title}</strong><br><small class='text-muted'>${item.snippet}</small>`;
                    div.onclick = () => {
                        document.getElementById('campo_nome_do_produto').value = item.title;
                        modal_google.hide();
                    };
                    corpo_modal.appendChild(div);
                });
            } else {
                corpo_modal.innerHTML = "<p class='text-center p-4'>Nenhuma sugestão encontrada.</p>";
            }
        });
}

/**
 * GESTÃO DO CARRINHO
 */
function funcaoAdicionarItemAoCarrinho() {
    const nome = document.getElementById('campo_nome_do_produto').value;
    const preco_texto = document.getElementById('campo_preco_do_produto').value;
    const qtd = parseInt(document.getElementById('campo_quantidade_do_produto').value);

    if (!nome || preco_texto === "" || preco_texto === "0,00") {
        Swal.fire('Ops', 'Informe o nome e o preço do item.', 'warning');
        return;
    }

    const preco_numerico = parseFloat(preco_texto.replace(".", "").replace(",", "."));
    array_carrinho_atual.push({ nome, preco: preco_numerico, quantidade: qtd });
    
    funcaoSalvarBackupOffline();
    funcaoRenderizarCarrinhoNoCupom();
    
    document.getElementById('campo_nome_do_produto').value = "";
    document.getElementById('campo_preco_do_produto').value = "";
    document.getElementById('campo_quantidade_do_produto').value = "1";
    document.getElementById('campo_nome_do_produto').focus();
}

function funcaoRenderizarCarrinhoNoCupom() {
    const container = document.getElementById('container-itens-cupom-fiscal');
    container.innerHTML = "";
    let total_acumulado = 0;

    array_carrinho_atual.forEach((item, index) => {
        const subtotal = item.preco * item.quantidade;
        total_acumulado += subtotal;
        container.innerHTML += `
            <div class="linha-item-carrinho d-flex justify-content-between align-items-center">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small class="text-muted">${item.quantidade} x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end" style="min-width:110px;">
                    <div class="fw-bold">R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <div class="mt-1">
                        <button class="btn btn-sm btn-light border text-primary" onclick="funcaoEditarItem(${index})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-light border text-danger" onclick="funcaoRemoverItem(${index})"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    });

    if (array_carrinho_atual.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted small">Carrinho vazio</div>';
    }
    document.getElementById('valor_total_do_carrinho').innerText = total_acumulado.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverItem(index) {
    array_carrinho_atual.splice(index, 1);
    funcaoSalvarBackupOffline();
    funcaoRenderizarCarrinhoNoCupom();
}

function funcaoEditarItem(index) {
    const item = array_carrinho_atual[index];
    document.getElementById('campo_nome_do_produto').value = item.nome;
    document.getElementById('campo_preco_do_produto').value = item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('campo_quantidade_do_produto').value = item.quantidade;
    funcaoRemoverItem(index);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * FINALIZAÇÃO E AGRADECIMENTO
 */
function funcaoFinalizarEEnviarParaBase() {
    if (array_carrinho_atual.length === 0) return;

    Swal.fire({
        title: 'Alimentando Base de BI...',
        text: 'Aguarde um momento.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('salvar_carrinho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mercado_id: identificador_mercado_selecionado, itens: array_carrinho_atual })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            // Limpa o Offline
            localStorage.removeItem('carrinho_offline_mercado_inteligente');
            localStorage.removeItem('carrinho_mercado_id');
            
            Swal.fire({
                title: 'Concluído com Sucesso!',
                html: 'Obrigado por colaborar com nossa Coleta de Dados.<br><br>Sua contribuição ajuda o <strong>Mercado Inteligente</strong> a crescer!<br><br>Sempre utilize nosso sistema.',
                icon: 'success',
                confirmButtonText: 'Nova Compra',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload();
            });
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>