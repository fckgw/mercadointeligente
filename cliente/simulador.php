<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Interface mestre para o cliente. 
 * Funcionalidades: Edição Individual (Lápis), Coleta Manual, NFC-e com aviso de correção, IA Google e BI.
 */

// Inicia a sessão para identificar se o usuário está autenticado e gerenciar metadados
session_start();

// Importação da conexão com o banco de dados via PDO
require_once '../core/db.php';

/**
 * CONFIGURAÇÃO DE IDENTIDADE VISUAL E ACESSO
 */
$identificador_do_usuario_logado = $_SESSION['usuario_id'] ?? null;
$nome_do_usuario_para_exibicao   = $_SESSION['usuario_nome'] ?? "Visitante";
$data_do_ultimo_acesso_usuario   = $_SESSION['ultimo_acesso_formatado'] ?? "Primeiro Acesso";

/**
 * CONSULTA DE ESTABELECIMENTOS
 * Recupera os mercados cadastrados para São José dos Campos.
 */
$instrucao_sql_mercados = "SELECT id, nome, regiao FROM mercados ORDER BY nome ASC";
$comando_recuperar_mercados = $pdo->query($instrucao_sql_mercados);
$lista_de_mercados_cadastrados = $comando_recuperar_mercados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador - Mercado Inteligente</title>
    
    <!-- CSS e Componentes Visuais -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Custom Search Engine ID -->
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 140px; font-family: 'Segoe UI', sans-serif; }
        
        /* Cabeçalho superior personalizado */
        .barra-navegacao-topo { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 12px 0; position: sticky; top: 0; z-index: 1050; }
        .nome-usuario-logado { color: #0d6efd; font-weight: 700; }
        
        /* Interface de Cartões */
        .cartao-estilizado-principal { border: none; border-radius: 20px; box-shadow: 0 4px 25px rgba(0,0,0,0.06); background: #fff; }
        .campo-entrada-estilizado { padding: 15px; border-radius: 12px; font-size: 1.1rem; border: 1px solid #ced4da; }
        
        /* Visual do Cupom Fiscal (Recibo) */
        .area-recibo-cupom-fiscal {
            background-color: #ffffff; padding: 25px; border: 1px solid #e0e0e0;
            font-family: 'Courier New', Courier, monospace; position: relative; border-radius: 4px;
        }
        .area-recibo-cupom-fiscal::before {
            content: ""; position: absolute; top: -10px; left: 0; width: 100%; height: 10px;
            background: linear-gradient(-45deg, transparent 5px, #fff 0), linear-gradient(45deg, transparent 5px, #fff 0);
            background-size: 10px 20px;
        }
        .linha-separacao-item { border-bottom: 1px dashed #bbb; padding: 12px 0; }
        
        /* Autocomplete Local */
        #container-sugestoes-base-local { 
            position: absolute; width: 100%; z-index: 2000; background: #fff; 
            border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            display: none; max-height: 220px; overflow-y: auto; border: 1px solid #eee;
        }
        .item-sugestao-clicavel { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
        .item-sugestao-clicavel:hover { background-color: #e7f1ff; color: #0d6efd; }

        /* Botão Finalizar Fixo e Centralizado */
        .bloco-finalizar-fixo-rodape {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: linear-gradient(to top, #f4f7f6 80%, transparent);
            padding: 20px; z-index: 2000; display: flex; justify-content: center;
        }
        .botao-salvar-bi-grande {
            width: 100%; max-width: 450px; border-radius: 50px; padding: 18px;
            font-weight: bold; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4);
            border: 2px solid #fff;
        }
        .gsc-control-cse { padding: 0 !important; border: none !important; }
    </style>
</head>
<body>

<!-- BARRA DE NAVEGAÇÃO SUPERIOR -->
<nav class="barra-navegacao-topo shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="../index.php">
            <img src="../images/Logo_MercadoInteligente.png" alt="Logo Mercado Inteligente" style="max-height: 42px;">
        </a>

        <div class="d-flex align-items-center text-end">
            <div class="texto-saudacao me-3 d-none d-sm-block">
                <span class="text-muted small">Olá,</span> <b class="nome-usuario-logado small"><?php echo $nome_do_usuario_para_exibicao; ?></b><br>
                <small class="text-muted" style="font-size: 0.6rem;">Acesso: <?php echo $data_do_ultimo_acesso_usuario; ?></small>
            </div>
            <?php if ($identificador_do_usuario_logado): ?>
                <a href="historico.php" class="btn btn-outline-primary btn-sm rounded-circle me-1" title="Meu Histórico"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-outline-danger btn-sm rounded-circle" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">

    <!-- BOTÕES DE ATALHO DE COLETA -->
    <div class="row g-2 mb-4 px-2" id="atalhos-coleta">
        <div class="col-6">
            <a href="coleta.php" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-primary fw-bold text-decoration-none text-center">
                <i class="bi bi-pencil-square d-block h4"></i>
                <small>COLETA MANUAL</small>
            </a>
        </div>
        <div class="col-6">
            <button onclick="funcaoAbrirLeitorNotaFiscal()" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-dark fw-bold text-center">
                <i class="bi bi-qr-code-scan d-block h4"></i>
                <small>LER NOTA FISCAL</small>
            </button>
        </div>
    </div>

    <!-- PASSO 1: SELECIONAR MERCADO -->
    <div class="card cartao-estilizado-principal p-4 mb-4" id="sessao-selecionar-mercado">
        <h6 class="fw-bold text-primary mb-3 text-center text-uppercase small">Onde você está agora?</h6>
        <div class="input-group mb-4">
            <select id="campo_mercado_identificador" class="form-select campo-entrada-estilizado border-primary shadow-sm text-center">
                <option value="">Clique para selecionar o mercado...</option>
                <?php foreach($lista_de_mercados_cadastrados as $mercado_singular): ?>
                    <option value="<?php echo $mercado_singular['id']; ?>"><?php echo $mercado_singular['nome']; ?> (<?php echo $mercado_singular['regiao']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoIniciarSimulacaoDeCarrinho()">
            ABRIR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: ÁREA DE LANÇAMENTO DOS PRODUTOS -->
    <div id="sessao-interface-carrinho" style="display: none;">
        
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill bg-white shadow-sm" onclick="window.location.reload()"><i class="bi bi-arrow-left"></i> Voltar</button>
            <span class="badge bg-primary text-white border shadow-sm px-3 py-2" id="etiqueta_nome_do_mercado">---</span>
        </div>

        <div class="card cartao-estilizado-principal p-4 mb-4 border-top border-primary border-5">
            <!-- Produto com Autocomplete e IA -->
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <div class="input-group">
                    <input type="text" id="campo_nome_do_produto" class="form-control campo-entrada-estilizado" placeholder="O que deseja comprar?" autocomplete="off" onkeyup="funcaoPesquisarBaseLocal(this.value)">
                    <button class="btn btn-warning px-3 shadow-sm" onclick="funcaoAbrirModalInteligenciaIA()" title="Consultar Google"><i class="bi bi-google"></i> <small class="fw-bold">IA</small></button>
                </div>
                <div id="container-sugestoes-base-local"></div>
            </div>

            <!-- UNIDADES DE MEDIDA -->
            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="medida_venda" id="unidade_un" value="UN" checked>
                <label class="btn btn-outline-primary py-2 fw-bold" for="unidade_un">UNIDADE</label>
                <input type="radio" class="btn-check" name="medida_venda" id="unidade_kg" value="KG">
                <label class="btn btn-outline-primary py-2 fw-bold" for="unidade_kg">PESO (KG)</label>
                <input type="radio" class="btn-check" name="medida_venda" id="unidade_l" value="L">
                <label class="btn btn-outline-primary py-2 fw-bold" for="unidade_l">LITRO (L)</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">VALOR UNITÁRIO (R$)</label>
                    <input type="text" id="campo_valor_unitario" class="form-control campo-entrada-estilizado fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraMoedaParaIdosos(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted">QUANTIDADE</label>
                    <input type="number" id="campo_quantidade_item" class="form-control campo-entrada-estilizado text-center" value="1" step="0.001">
                </div>
            </div>

            <button onclick="funcaoAdicionarNovoItemNoCarrinho()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <!-- CUPOM FISCAL VISUAL -->
        <div class="area-recibo-cupom-fiscal shadow-sm mb-5">
            <div class="text-center mb-4">
                <h6 class="fw-bold mb-0">CUPOM DE CONFERÊNCIA</h6>
                <small class="text-muted text-uppercase" style="font-size: 0.6rem;">Mercado Inteligente - BI</small>
                <div class="mt-2 border-top border-1 border-dark"></div>
            </div>
            <div id="container-itens-renderizados-cupom">
                <!-- Conteúdo via JS -->
            </div>
            <div class="mt-4 border-top border-2 border-dark pt-3">
                <div class="d-flex justify-content-between h3 fw-bold mb-0">
                    <span>TOTAL R$</span>
                    <span id="valor_total_cupom_label">0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARRA FIXA DO BOTÃO FINALIZAR -->
<div class="bloco-finalizar-fixo-rodape" id="bloco-botao-finalizar" style="display: none;">
    <button class="btn btn-success botao-salvar-bi-grande shadow-lg border-white border-2" onclick="funcaoFinalizarEEnviarBI()">
        <i class="bi bi-cloud-arrow-up-fill me-2"></i> FECHAR E SALVAR COMPRA
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
/**
 * ESTADO DO SISTEMA E VARIÁVEIS
 */
const usuario_logado_atualmente = <?php echo $identificador_do_usuario_logado ? 'true' : 'false'; ?>;
let array_carrinho_dinamico = [];
let identificador_mercado_selecionado = "";

/**
 * PERSISTÊNCIA OFFLINE (Apenas Logados)
 */
document.addEventListener('DOMContentLoaded', function() {
    if (usuario_logado_atualmente) {
        const dados_armazenados_offline = localStorage.getItem('carrinho_offline_mercado_inteligente');
        if (dados_armazenados_offline) {
            array_carrinho_dinamico = JSON.parse(dados_armazenados_offline);
            identificador_mercado_selecionado = localStorage.getItem('carrinho_mercado_id');
            if (array_carrinho_dinamico.length > 0) {
                document.getElementById('sessao-selecionar-mercado').style.display = 'none';
                document.getElementById('sessao-interface-carrinho').style.display = 'block';
                document.getElementById('bloco-botao-finalizar').style.display = 'flex';
                funcaoRenderizarVisualRecibo();
            }
        }
    }
});

/**
 * TRANSIÇÃO DE TELAS
 */
function funcaoIniciarSimulacaoDeCarrinho() {
    identificador_mercado_selecionado = document.getElementById('campo_mercado_identificador').value;
    const elemento_select = document.getElementById('campo_mercado_identificador');
    const nome_mercado_selecionado = elemento_select.options[elemento_select.selectedIndex].text;
    
    if (!identificador_mercado_selecionado) {
        Swal.fire('Seleção Necessária', 'Selecione um supermercado para continuar.', 'info');
        return;
    }

    document.getElementById('sessao-selecionar-mercado').style.display = 'none';
    document.getElementById('sessao-interface-carrinho').style.display = 'block';
    document.getElementById('bloco-botao-finalizar').style.display = 'flex';
    document.getElementById('etiqueta_nome_do_mercado').innerText = nome_mercado_selecionado.toUpperCase();
}

/**
 * UTILITÁRIOS: MÁSCARA E AUTOCOMPLETE
 */
function funcaoMascaraMoedaParaIdosos(campo_texto) {
    let valor_bruto = campo_texto.value.replace(/\D/g, "");
    valor_bruto = (valor_bruto / 100).toFixed(2) + "";
    valor_bruto = valor_bruto.replace(".", ",");
    valor_bruto = valor_bruto.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor_bruto = valor_bruto.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo_texto.value = valor_bruto;
}

function funcaoPesquisarBaseLocal(texto_digitado) {
    const box_sugestoes = document.getElementById('container-sugestoes-base-local');
    if (texto_digitado.length < 2) { box_sugestoes.style.display = 'none'; return; }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(texto_digitado)}`)
        .then(res => res.json())
        .then(dados_obtidos => {
            box_sugestoes.innerHTML = "";
            if (dados_obtidos.length > 0) {
                box_sugestoes.style.display = 'block';
                dados_obtidos.forEach(item_produto => {
                    const div_clicavel = document.createElement('div');
                    div_clicavel.className = "item-sugestao-clicavel";
                    div_clicavel.innerHTML = `<strong>${item_produto.nome}</strong><br><small class='text-muted'>${item_produto.marca}</small>`;
                    div_clicavel.onclick = () => {
                        document.getElementById('campo_nome_do_produto').value = item_produto.nome;
                        box_sugestoes.style.display = 'none';
                    };
                    box_sugestoes.appendChild(div_clicavel);
                });
            } else { box_sugestoes.style.display = 'none'; }
        });
}

/**
 * LEITURA DE NOTA FISCAL (NFC-e)
 */
function funcaoAbrirLeitorNotaFiscal() {
    Swal.fire({
        title: 'Importar Nota Fiscal',
        input: 'text',
        inputLabel: 'Cole aqui o link do QR Code da sua Nota de SP',
        showCancelButton: true,
        confirmButtonText: 'Ler Produtos',
        confirmButtonColor: '#0d6efd'
    }).then((resultado_acao) => {
        if (resultado_acao.isConfirmed && resultado_acao.value) {
            Swal.fire({ title: 'Acessando Receita...', didOpen: () => { Swal.showLoading(); } });
            
            let dados_post = new FormData();
            dados_post.append('url_nfce', resultado_acao.value);

            fetch('../api/processar_nfce.php', { method: 'POST', body: dados_post })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    data.itens.forEach(item_lido => {
                        const preco_lido = parseFloat(item_lido.valor) || 0;
                        const qtd_lida = parseFloat(item_lido.quantidade) || 0;
                        array_carrinho_dinamico.push({
                            nome: item_lido.nome,
                            preco: preco_lido,
                            quantidade: qtd_lida,
                            unidade: item_lido.unidade || 'UN',
                            subtotal: preco_lido * qtd_lida
                        });
                    });
                    
                    document.getElementById('sessao-selecionar-mercado').style.display = 'none';
                    document.getElementById('sessao-interface-carrinho').style.display = 'block';
                    document.getElementById('bloco-botao-finalizar').style.display = 'flex';
                    
                    funcaoRenderizarVisualRecibo();
                    Swal.fire({
                        title: 'Nota Importada!',
                        html: `<b>${data.itens.length} produtos carregados.</b><br><br>Favor alterar para o valor real nos itens que estão como 0,00 (clique no lápis).`,
                        icon: 'success'
                    });
                } else {
                    Swal.fire('Erro', 'Não foi possível extrair dados desta nota.', 'error');
                }
            });
        }
    });
}

/**
 * GESTÃO DO CARRINHO (Adicionar, Editar, Remover)
 */
function funcaoAdicionarNovoItemNoCarrinho() {
    const nome_prod = document.getElementById('campo_nome_do_produto').value;
    const preco_prod_texto = document.getElementById('campo_valor_unitario').value;
    const quantidade_item = parseFloat(document.getElementById('campo_quantidade_item').value);
    const unidade_medida = document.querySelector('input[name="unidade_venda"]:checked').value;

    if (!nome_prod || preco_prod_texto === "" || preco_prod_texto === "0,00" || !quantidade_item) {
        Swal.fire('Campos Vazios', 'Informe nome e preço corretamente.', 'warning');
        return;
    }

    const valor_numerico_final = parseFloat(preco_prod_texto.replace(".", "").replace(",", "."));
    array_carrinho_dinamico.push({ 
        nome: nome_prod, 
        preco: valor_numerico_final, 
        quantidade: quantidade_item, 
        unidade: unidade_medida, 
        subtotal: valor_numerico_final * quantidade_item 
    });
    
    if (usuario_logado_atualmente) {
        localStorage.setItem('carrinho_offline_mercado_inteligente', JSON.stringify(array_carrinho_dinamico));
        localStorage.setItem('carrinho_mercado_id', identificador_mercado_selecionado);
    }

    funcaoRenderizarVisualRecibo();
    
    // Reset de campos
    document.getElementById('campo_nome_do_produto').value = "";
    document.getElementById('campo_valor_unitario').value = "";
    document.getElementById('campo_quantidade_item').value = "1";
    document.getElementById('campo_nome_do_produto').focus();
}

function funcaoRenderizarVisualRecibo() {
    const container = document.getElementById('container-itens-renderizados-cupom');
    container.innerHTML = "";
    let soma_total_geral = 0;

    array_carrinho_dinamico.forEach((item, index) => {
        soma_total_geral += item.subtotal;
        container.innerHTML += `
            <div class="linha-separacao-item d-flex justify-content-between align-items-center">
                <div style="flex:1; padding-right: 10px;">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small class="text-muted">${item.quantidade} ${item.unidade} x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end" style="min-width:110px;">
                    <div class="fw-bold">R$ ${item.subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <div class="mt-1">
                        <!-- BOTÃO LÁPIS (EDITAR) RESTAURADO -->
                        <button class="btn btn-sm btn-light border text-primary" onclick="funcaoEditarItemCarrinho(${index})" title="Corrigir Preço"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-light border text-danger" onclick="funcaoRemoverItemCarrinho(${index})" title="Remover"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    });

    if (array_carrinho_dinamico.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted small italic">Carrinho vazio</div>';
    }

    document.getElementById('valor_total_cupom_label').innerText = soma_total_geral.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverItemCarrinho(indice) {
    array_carrinho_dinamico.splice(indice, 1);
    if (usuario_logado_atualmente) {
        localStorage.setItem('carrinho_offline_mercado_inteligente', JSON.stringify(array_carrinho_dinamico));
    }
    funcaoRenderizarVisualRecibo();
}

function funcaoEditarItemCarrinho(indice) {
    const item_referencia = array_carrinho_dinamico[indice];
    document.getElementById('campo_nome_do_produto').value = item_referencia.nome;
    document.getElementById('campo_valor_unitario').value = item_referencia.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('campo_quantidade_item').value = item_referencia.quantidade;
    
    // Remove o item da lista para ser re-adicionado após a correção
    funcaoRemoverItemCarrinho(indice);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * FINALIZAÇÃO E BI
 */
function funcaoFinalizarEEnviarBI() {
    if (array_carrinho_dinamico.length === 0) return;

    Swal.fire({
        title: 'Mercado Inteligente',
        text: 'Alimentando nossa Base de Dados BI...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('salvar_carrinho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            mercado_id: identificador_mercado_selecionado, 
            itens: array_carrinho_dinamico 
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            localStorage.removeItem('carrinho_offline_mercado_inteligente');
            Swal.fire({
                title: 'Concluído!',
                html: 'Muito obrigado por colaborar com o nosso sistema.<br><br><b>Sempre utilize o Mercado Inteligente!</b>',
                icon: 'success'
            }).then(() => location.reload());
        }
    });
}

function funcaoAbrirModalIA() { new bootstrap.Modal(document.getElementById('modalGoogleIA')).show(); }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>