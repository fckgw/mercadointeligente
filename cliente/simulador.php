<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Interface de simulação de compras e coleta de dados BI.
 * Localização: São José dos Campos - SP
 */

// Inicia a sessão para controle de usuário logado e histórico
session_start();

// Importação da conexão com o banco de dados
require_once '../core/db.php';

// Verifica se existe um usuário logado para habilitar as funções exclusivas (Offline e Histórico)
$usuario_logado_identificador = $_SESSION['usuario_id'] ?? null;
$usuario_logado_nome          = $_SESSION['usuario_nome'] ?? "Visitante";
$data_ultimo_acesso_usuario   = $_SESSION['ultimo_acesso_formatado'] ?? "";

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
    
    <!-- Dependências Visuais (Bootstrap e Ícones) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 para Mensagens Amigáveis -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Custom Search Engine -->
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 120px; font-family: 'Segoe UI', Tahoma, sans-serif; }
        
        /* Cabeçalho superior personalizado */
        .barra-navegacao-superior { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 10px 0; }
        .texto-saudacao { font-size: 0.85rem; line-height: 1.2; }
        .nome-usuario-destaque { color: #0d6efd; font-weight: 700; }
        
        .cartao-estilizado { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #fff; }
        .input-grande { padding: 15px; border-radius: 12px; font-size: 1.1rem; }
        
        /* Estilo do Cupom Fiscal */
        .visual-cupom-fiscal {
            background-color: #ffffff;
            padding: 25px;
            border: 1px solid #e0e0e0;
            font-family: 'Courier New', Courier, monospace;
            position: relative;
            border-radius: 4px;
        }
        .visual-cupom-fiscal::before {
            content: ""; position: absolute; top: -10px; left: 0; width: 100%; height: 10px;
            background: linear-gradient(-45deg, transparent 5px, #fff 0), linear-gradient(45deg, transparent 5px, #fff 0);
            background-size: 10px 20px;
        }
        .linha-item-carrinho { border-bottom: 1px dashed #ccc; padding: 12px 0; }
        
        /* Botão Finalizar Fixo no Rodapé */
        .botao-finalizar-fixo {
            position: fixed; bottom: 20px; left: 20px; right: 20px; 
            border-radius: 50px; padding: 18px; font-weight: bold; z-index: 1000;
            box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4);
        }

        /* Sugestões do Banco de Dados */
        #container-sugestoes-locais { 
            position: absolute; width: 100%; z-index: 2000; background: #fff; 
            border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            display: none; max-height: 200px; overflow-y: auto; border: 1px solid #eee;
        }
        .item-sugestao { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
        .item-sugestao:hover { background-color: #e7f1ff; color: #0d6efd; }

        .item-resultado-google { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; border-radius: 12px; margin-bottom: 5px; transition: 0.2s; }
        .item-resultado-google:hover { background-color: #eef6ff; color: #0d6efd; }
    </style>
</head>
<body>

<!-- BARRA SUPERIOR -->
<nav class="barra-navegacao-superior shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="../index.php">
            <img src="../images/Logo_MercadoInteligente.png" alt="Mercado Inteligente" style="max-height: 40px;">
        </a>

        <div class="text-end d-flex align-items-center">
            <div class="texto-saudacao me-3 d-none d-sm-block">
                <span class="text-muted">Olá,</span> 
                <span class="nome-usuario-destaque"><?php echo $usuario_logado_nome; ?></span><br>
                <small class="text-muted" style="font-size: 0.7rem;"><?php echo $data_ultimo_acesso_usuario; ?></small>
            </div>
            
            <?php if ($usuario_logado_identificador): ?>
                <a href="historico.php" class="btn btn-outline-primary btn-sm rounded-circle me-1" title="Histórico"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-outline-danger btn-sm rounded-circle" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <!-- PASSO 1: SELECIONAR OU CADASTRAR MERCADO -->
    <div class="card cartao-estilizado p-4 mb-4" id="sessao-selecao-mercado">
        <div class="text-center mb-3">
            <h5 class="fw-bold text-dark">Simulador de Inteligência</h5>
            <p class="text-muted small">Selecione onde você está agora:</p>
        </div>
        
        <div class="input-group mb-3">
            <select id="campo_mercado_selecionado_id" class="form-select input-grande border-primary">
                <option value="">Escolha o Supermercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $mercado_singular): ?>
                    <option value="<?php echo $mercado_singular['id']; ?>"><?php echo $mercado_singular['nome']; ?></option>
                <?php endforeach; ?>
            </select>
            <!-- BOTÃO PARA ADICIONAR NOVO MERCADO -->
            <button class="btn btn-primary px-3 shadow-sm" onclick="funcaoAbrirCadastroNovoMercado()" title="Cadastrar Novo Mercado">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>

        <!-- Painel de Cadastro Rápido de Mercado (Inicialmente Oculto) -->
        <div id="container-cadastro-mercado-novo" style="display:none;" class="mt-2 p-3 bg-light rounded-3 border mb-3">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Nome do Novo Supermercado</label>
            <input type="text" id="input_nome_mercado_novo" class="form-control mb-2" placeholder="Ex: Supermercado São José">
            <button class="btn btn-primary btn-sm w-100 py-2 fw-bold" onclick="funcaoGravarNovoMercadoViaApi()">SALVAR E USAR ESTE</button>
        </div>

        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoValidarEIniciarSimulador()">
            INICIAR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: INTERFACE DO CARRINHO -->
    <div id="sessao-carrinho-ativo" style="display: none;">
        
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="window.location.reload()"><i class="bi bi-arrow-left"></i> Trocar Mercado</button>
            <span class="badge bg-white text-dark border shadow-sm" id="etiqueta_mercado_atual">---</span>
        </div>

        <div class="card cartao-estilizado p-4 mb-4 border-top border-primary border-5">
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <div class="input-group">
                    <input type="text" id="campo_nome_do_produto" class="form-control input-grande" placeholder="O que deseja comprar?" autocomplete="off" onkeyup="funcaoBuscarSugestoesNaBaseLocal(this.value)">
                    <button class="btn btn-warning px-3 shadow-sm" onclick="funcaoAbrirModalIA()"><i class="bi bi-google"></i> <small class="fw-bold">IA</small></button>
                </div>
                <div id="container-sugestoes-locais"></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">VALOR (R$)</label>
                    <input type="text" id="campo_valor_do_produto" class="form-control input-grande fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraMoedaParaIdosos(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted">QUANTIDADE</label>
                    <input type="number" id="campo_quantidade_do_produto" class="form-control input-grande text-center" value="1">
                </div>
            </div>

            <button onclick="funcaoAdicionarProdutoNaLista()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <!-- CUPOM FISCAL -->
        <div class="visual-cupom-fiscal shadow-sm mb-5">
            <div class="text-center mb-4">
                <h5 class="fw-bold mb-0">CUPOM DE CONFERÊNCIA</h5>
                <small class="text-muted">MERCADO INTELIGENTE</small>
                <div class="mt-2 border-top border-1 border-dark"></div>
            </div>

            <div id="container-itens-do-recibo">
                <div class="text-center py-4 text-muted small italic">Nenhum item lançado</div>
            </div>

            <div class="mt-4 border-top border-2 border-dark pt-3">
                <div class="d-flex justify-content-between h3 fw-bold">
                    <span>TOTAL R$</span>
                    <span id="valor_total_calculado">0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTÃO FINALIZAR -->
<button id="btn-finalizar-compra-bi" class="btn btn-success botao-finalizar-compra-fixo shadow-lg border-white border-2" style="display: none;" onclick="funcaoFinalizarEEnviarParaBase()">
    <i class="bi bi-cloud-check-fill me-2"></i> FECHAR E SALVAR COMPRA
</button>

<!-- MODAL GOOGLE IA -->
<div class="modal fade" id="modalGoogleIA" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-dark"><i class="bi bi-google text-primary"></i> Sugestões Inteligentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="corpo_resultados_ia" style="max-height: 400px; overflow-y: auto;">
                <!-- Preenchido via AJAX Google -->
            </div>
        </div>
    </div>
</div>

<script>
/**
 * ESTADO DO SISTEMA
 */
const usuario_esta_logado = <?php echo $usuario_logado_identificador ? 'true' : 'false'; ?>;
let array_carrinho_atual = [];
let identificador_mercado_selecionado = "";

/**
 * LÓGICA DE PERSISTÊNCIA OFFLINE (LocalStorage)
 */
document.addEventListener('DOMContentLoaded', function() {
    if (usuario_esta_logado) {
        const dados_recuperados_offline = localStorage.getItem('carrinho_offline_mercado_inteligente');
        if (dados_recuperados_offline) {
            array_carrinho_atual = JSON.parse(dados_recuperados_offline);
            identificador_mercado_selecionado = localStorage.getItem('carrinho_mercado_id');
            if (array_carrinho_atual.length > 0) {
                document.getElementById('sessao-selecao-mercado').style.display = 'none';
                document.getElementById('sessao-carrinho-ativo').style.display = 'block';
                document.getElementById('btn-finalizar-compra-bi').style.display = 'block';
                funcaoRenderizarCupomFiscal();
            }
        }
    }
});

/**
 * LÓGICA DE MERCADOS
 */
function funcaoAbrirCadastroNovoMercado() {
    document.getElementById('container-cadastro-mercado-novo').style.display = 'block';
    document.getElementById('input_nome_mercado_novo').focus();
}

function funcaoGravarNovoMercadoViaApi() {
    const nome_mercado_novo = document.getElementById('input_nome_mercado_novo').value;
    if (!nome_mercado_novo) return;

    fetch('../api/registrar_mercado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: nome_mercado_novo })
    })
    .then(resposta => resposta.json())
    .then(retorno => {
        if (retorno.sucesso) {
            const seletor = document.getElementById('campo_mercado_selecionado_id');
            const nova_opcao = new Option(retorno.nome, retorno.id, true, true);
            seletor.add(nova_opcao);
            document.getElementById('container-cadastro-mercado-novo').style.display = 'none';
            Swal.fire('Cadastrado!', 'Novo mercado pronto para uso.', 'success');
        }
    });
}

function funcaoValidarEIniciarSimulador() {
    identificador_mercado_selecionado = document.getElementById('campo_mercado_selecionado_id').value;
    const nome_do_mercado_texto = document.getElementById('campo_mercado_selecionado_id').options[document.getElementById('campo_mercado_selecionado_id').selectedIndex].text;
    
    if (!identificador_mercado_selecionado) {
        Swal.fire('Atenção', 'Por favor, selecione um supermercado.', 'info');
        return;
    }

    document.getElementById('sessao-selecao-mercado').style.display = 'none';
    document.getElementById('sessao-carrinho-ativo').style.display = 'block';
    document.getElementById('btn-finalizar-compra-bi').style.display = 'block';
    document.getElementById('etiqueta_mercado_atual').innerText = nome_do_mercado_texto.toUpperCase();
}

/**
 * UTILITÁRIOS: MÁSCARA E BUSCAS
 */
function funcaoMascaraMoedaParaIdosos(campo_elemento) {
    let valor_bruto = campo_elemento.value.replace(/\D/g, "");
    valor_bruto = (valor_bruto / 100).toFixed(2) + "";
    valor_bruto = valor_bruto.replace(".", ",");
    valor_bruto = valor_bruto.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor_bruto = valor_bruto.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo_elemento.value = valor_bruto;
}

function funcaoBuscarSugestoesNaBaseLocal(termo_de_busca) {
    const box_sugestoes = document.getElementById('container-sugestoes-locais');
    if (termo_de_busca.length < 2) { box_sugestoes.style.display = 'none'; return; }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(termo_de_busca)}`)
        .then(r => r.json())
        .then(lista_dados => {
            box_sugestoes.innerHTML = "";
            if (lista_dados.length > 0) {
                box_sugestoes.style.display = 'block';
                lista_dados.forEach(produto => {
                    const div_item = document.createElement('div');
                    div_item.className = "item-sugestao";
                    div_item.innerHTML = `<strong>${produto.nome}</strong><br><small class='text-muted'>${produto.marca}</small>`;
                    div_item.onclick = () => {
                        document.getElementById('campo_nome_do_produto').value = produto.nome;
                        box_sugestoes.style.display = 'none';
                    };
                    box_sugestoes.appendChild(div_item);
                });
            } else { box_sugestoes.style.display = 'none'; }
        });
}

function funcaoAbrirModalIA() {
    const termo_original = document.getElementById('campo_nome_do_produto').value;
    const modal = new bootstrap.Modal(document.getElementById('modalGoogleIA'));
    const container = document.getElementById('corpo_resultados_ia');
    
    container.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Consultando IA do Google...</p></div>';
    modal.show();

    fetch(`../api/google_busca.php?termo=${encodeURIComponent(termo_original)}`)
        .then(res => res.json())
        .then(data => {
            container.innerHTML = "";
            if (data.items) {
                data.items.forEach(item => {
                    const div_ia = document.createElement('div');
                    div_ia.className = "item-resultado-google shadow-sm border mb-2";
                    div_ia.innerHTML = `<strong>${item.title}</strong><br><small class='text-muted'>${item.snippet}</small>`;
                    div_ia.onclick = () => {
                        document.getElementById('campo_nome_do_produto').value = item.title;
                        modal.hide();
                    };
                    container.appendChild(div_ia);
                });
            } else {
                container.innerHTML = "<p class='text-center p-4'>Nenhuma sugestão encontrada.</p>";
            }
        });
}

/**
 * GESTÃO DO CARRINHO
 */
function funcaoAdicionarProdutoNaLista() {
    const nome_item = document.getElementById('campo_nome_do_produto').value;
    const preco_item_texto = document.getElementById('campo_valor_do_produto').value;
    const quantidade_item = parseInt(document.getElementById('campo_quantidade_do_produto').value);

    if (!nome_item || preco_item_texto === "" || preco_item_texto === "0,00") {
        Swal.fire('Campos Vazios', 'Preencha o nome e o preço corretamente.', 'warning');
        return;
    }

    const valor_convertido = parseFloat(preco_item_texto.replace(".", "").replace(",", "."));
    array_carrinho_atual.push({ nome: nome_item, preco: valor_convertido, quantidade: quantidade_item });
    
    if (usuario_esta_logado) {
        localStorage.setItem('carrinho_offline_mercado_inteligente', JSON.stringify(array_carrinho_atual));
        localStorage.setItem('carrinho_mercado_id', identificador_mercado_selecionado);
    }

    funcaoRenderizarCupomFiscal();
    
    // Limpeza
    document.getElementById('campo_nome_do_produto').value = "";
    document.getElementById('campo_valor_do_produto').value = "";
    document.getElementById('campo_quantidade_do_produto').value = "1";
    document.getElementById('campo_nome_do_produto').focus();
}

function funcaoRenderizarCupomFiscal() {
    const conteiner_recibo = document.getElementById('container-itens-do-recibo');
    conteiner_recibo.innerHTML = "";
    let soma_acumulada = 0;

    array_carrinho_atual.forEach((item_lista, indice_lista) => {
        const subtotal_linha = item_lista.preco * item_lista.quantidade;
        soma_acumulada += subtotal_linha;

        conteiner_recibo.innerHTML += `
            <div class="linha-item-carrinho d-flex justify-content-between align-items-center">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item_lista.nome}</div>
                    <small class="text-muted">${item_lista.quantidade} UN x R$ ${item_lista.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end" style="min-width:110px;">
                    <div class="fw-bold">R$ ${subtotal_linha.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <button class="btn btn-sm text-danger p-0" onclick="funcaoRemoverProduto(${indice_lista})"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    });

    if (array_carrinho_atual.length === 0) {
        conteiner_recibo.innerHTML = '<div class="text-center py-4 text-muted small">Carrinho vazio</div>';
    }

    document.getElementById('valor_total_calculado').innerText = soma_acumulada.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverProduto(indice) {
    array_carrinho_atual.splice(indice, 1);
    if (usuario_esta_logado) {
        localStorage.setItem('carrinho_offline_mercado_inteligente', JSON.stringify(array_carrinho_atual));
    }
    funcaoRenderizarCupomFiscal();
}

/**
 * FINALIZAÇÃO E BI
 */
function funcaoFinalizarEEnviarParaBase() {
    if (array_carrinho_atual.length === 0) return;

    Swal.fire({
        title: 'Mercado Inteligente',
        text: 'Alimentando nossa Base de Dados BI...',
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
            localStorage.removeItem('carrinho_offline_mercado_inteligente');
            Swal.fire({
                title: 'Concluído!',
                html: 'Muito obrigado por colaborar com o nosso sistema.<br><br><b>Sempre utilize o Mercado Inteligente!</b>',
                icon: 'success',
                confirmButtonText: 'Nova Compra'
            }).then(() => location.reload());
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>