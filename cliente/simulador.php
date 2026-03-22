<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Interface completa do cliente para montagem de carrinho e coleta de dados BI.
 */

// Conexão com o banco de dados
require_once '../core/db.php';

/**
 * Recupera os mercados cadastrados para a seleção inicial do usuário.
 */
$instrucao_recuperar_mercados = "SELECT id, nome, regiao FROM mercados ORDER BY nome ASC";
$comando_preparado_mercados = $pdo->query($instrucao_recuperar_mercados);
$lista_completa_de_mercados = $comando_preparado_mercados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador de Compras - Mercado Inteligente</title>
    
    <!-- Bibliotecas de Design e Ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 para Pop-ups de interação -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script do seu Google Custom Search Engine -->
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 120px; font-family: 'Segoe UI', sans-serif; }
        .cartao-simulador { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .input-estilizado { padding: 15px; border-radius: 12px; font-size: 1.1rem; border: 1px solid #ced4da; }
        
        /* Dropdown de sugestões da nossa base de dados */
        #container-sugestoes-autocomplete { 
            position: absolute; width: 100%; z-index: 2000; background: #fff; 
            border-radius: 0 0 15px 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            display: none; max-height: 250px; overflow-y: auto; border: 1px solid #eee;
        }
        .item-sugestao-clicavel { padding: 15px 20px; border-bottom: 1px solid #f8f9fa; cursor: pointer; }
        .item-sugestao-clicavel:hover { background-color: #e7f1ff; color: #0d6efd; }

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
        .linha-separadora-item { border-bottom: 1px dashed #bbb; padding: 12px 0; }
        
        /* Botão Finalizar Fixo no Rodapé */
        .botao-finalizar-compra-fixo {
            position: fixed; bottom: 20px; left: 20px; right: 20px; 
            border-radius: 50px; padding: 18px; font-weight: bold; z-index: 1000;
            box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4);
        }

        .resultado-google-clicavel { padding: 15px; border-bottom: 1px solid #f1f1f1; cursor: pointer; border-radius: 12px; margin-bottom: 5px; }
        .resultado-google-clicavel:hover { background: #e7f1ff; }
        .gsc-control-cse { padding: 0 !important; border: none !important; }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Logomarca -->
    <div class="text-center mb-4">
        <a href="../index.php"><img src="../images/Logo_MercadoInteligente.png" alt="Mercado Inteligente" style="max-height: 55px;"></a>
    </div>

    <!-- PASSO 1: SELEÇÃO DE MERCADO -->
    <div class="card cartao-simulador p-4 mb-4" id="bloco-selecao-mercado">
        <h6 class="fw-bold text-primary mb-3 text-uppercase small text-center"><i class="bi bi-shop"></i> Onde você está agora?</h6>
        
        <div class="input-group mb-3">
            <select id="input_mercado_id" class="form-select input-estilizado border-primary shadow-sm">
                <option value="">Clique para selecionar o mercado...</option>
                <?php foreach($lista_completa_de_mercados as $mercado): ?>
                    <option value="<?php echo $mercado['id']; ?>"><?php echo $mercado['nome']; ?> (<?php echo $mercado['regiao']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary px-3" onclick="funcaoAbrirCadastroMercado()"><i class="bi bi-plus-circle"></i></button>
        </div>

        <!-- Cadastro rápido de mercado -->
        <div id="container-cadastro-mercado" style="display:none;" class="mt-2 p-3 bg-light rounded-3 border mb-3">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Nome do Novo Mercado</label>
            <input type="text" id="input_nome_novo_mercado" class="form-control mb-2" placeholder="Ex: Supermercado São José">
            <button class="btn btn-primary btn-sm w-100 py-2" onclick="funcaoRegistrarMercadoNoBanco()">Salvar e Selecionar</button>
        </div>

        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoValidarEIniciarCompra()">
            INICIAR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: INTERFACE DE COMPRA (CARRINHO ATIVO) -->
    <div id="bloco-carrinho-ativo" style="display: none;">
        
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="funcaoVoltarParaInicio()"><i class="bi bi-arrow-left"></i> Trocar Mercado</button>
            <span class="badge bg-white text-dark border shadow-sm" id="rotulo_mercado_selecionado">---</span>
        </div>

        <div class="card cartao-simulador p-4 mb-4 border-top border-primary border-5">
            
            <!-- Nome do Produto com Autocomplete e IA -->
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase">O que está levando?</label>
                <div class="input-group">
                    <input type="text" id="campo_nome_produto" class="form-control input-estilizado" placeholder="Digite o nome do item..." autocomplete="off" onkeyup="funcaoBuscarNaNossaBase(this.value)">
                    <button class="btn btn-warning px-3 shadow-sm" onclick="funcaoAbrirIA()" title="Consultar Google"><i class="bi bi-google"></i> <small class="fw-bold">IA</small></button>
                </div>
                <div id="container-sugestoes-autocomplete"></div>
            </div>

            <!-- Valor e Qtd -->
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">PREÇO (R$)</label>
                    <input type="text" id="campo_valor_produto" class="form-control input-estilizado fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraDinheiro(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted">QUANTIDADE</label>
                    <input type="number" id="campo_quantidade_produto" class="form-control input-estilizado text-center" value="1">
                </div>
            </div>

            <button onclick="funcaoAdicionarItem()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR AO CARRINHO
            </button>
        </div>

        <!-- VISUALIZAÇÃO DO CUPOM -->
        <div class="visual-cupom-fiscal shadow-sm mb-5">
            <div class="text-center mb-4">
                <h5 class="fw-bold mb-0">CUPOM DE CONFERÊNCIA</h5>
                <small class="text-muted">MERCADO INTELIGENTE</small>
                <div class="mt-2 border-top border-1 border-dark"></div>
            </div>

            <div id="renderizacao-itens-cupom">
                <!-- Itens via JS -->
            </div>

            <div class="mt-4 border-top border-2 border-dark pt-3">
                <div class="d-flex justify-content-between h3 fw-bold mb-0">
                    <span>TOTAL R$</span>
                    <span id="valor_total_cupom">0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTÃO FINALIZAR -->
<button id="btn-salvar-bi-final" class="btn btn-success botao-finalizar-compra-fixo shadow-lg border-white border-2" style="display: none;" onclick="funcaoFinalizarEGravarDados()">
    <i class="bi bi-cloud-arrow-up-fill me-1"></i> FECHAR E SALVAR COMPRA
</button>

<!-- MODAL GOOGLE IA -->
<div class="modal fade" id="modalGoogleIA" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-dark"><i class="bi bi-google text-primary"></i> Selecione a Sugestão Correta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="container-resultados-ia" style="max-height: 400px; overflow-y: auto;">
                <!-- O código da div gcse-search também pode ser usado aqui ou a API JSON conforme configurado no google_busca.php -->
                <div class="gcse-search"></div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * LÓGICA DO SISTEMA
 */
let carrinho_local = [];
let id_mercado_ativo = "";

// 1. Inicia o simulador
function funcaoValidarEIniciarCompra() {
    id_mercado_ativo = document.getElementById('input_mercado_id').value;
    const nome_mercado = document.getElementById('input_mercado_id').options[document.getElementById('input_mercado_id').selectedIndex].text;
    
    if (!id_mercado_ativo) {
        Swal.fire('Aviso', 'Por favor, selecione um supermercado.', 'info');
        return;
    }

    // TRANSICAO DE TELAS (CORRIGIDO)
    document.getElementById('bloco-selecao-mercado').style.display = 'none';
    document.getElementById('bloco-carrinho-ativo').style.display = 'block';
    document.getElementById('btn-salvar-bi-final').style.display = 'block';
    document.getElementById('rotulo_mercado_selecionado').innerText = nome_mercado;
}

// 2. Cadastro de mercado
function funcaoAbrirCadastroMercado() {
    document.getElementById('container-cadastro-mercado').style.display = 'block';
    document.getElementById('input_nome_novo_mercado').focus();
}

function funcaoRegistrarMercadoNoBanco() {
    const nome = document.getElementById('input_nome_novo_mercado').value;
    if (!nome) return;

    fetch('../api/registrar_mercado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: nome })
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            const select = document.getElementById('input_mercado_id');
            const opt = new Option(data.nome, data.id, true, true);
            select.add(opt);
            document.getElementById('container-cadastro-mercado').style.display = 'none';
            Swal.fire('Sucesso', 'Mercado adicionado!', 'success');
        }
    });
}

// 3. Mascara de Dinheiro para Idosos
function funcaoMascaraDinheiro(campo) {
    let v = campo.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2) + "";
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo.value = v;
}

// 4. Autocomplete da Base Local
function funcaoBuscarNaNossaBase(termo) {
    const box = document.getElementById('container-sugestoes-autocomplete');
    if (termo.length < 2) { box.style.display = 'none'; return; }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(termo)}`)
        .then(r => r.json())
        .then(dados => {
            box.innerHTML = "";
            if (dados.length > 0) {
                box.style.display = 'block';
                dados.forEach(p => {
                    const d = document.createElement('div');
                    d.className = "item-sugestao-clicavel";
                    d.innerHTML = `<strong>${p.nome}</strong><br><small class='text-muted'>${p.marca} | ${p.categoria}</small>`;
                    d.onclick = () => {
                        document.getElementById('campo_nome_produto').value = p.nome;
                        box.style.display = 'none';
                    };
                    box.appendChild(d);
                });
            } else {
                box.style.display = 'none';
            }
        });
}

// 5. Google IA
function funcaoAbrirIA() {
    const termo = document.getElementById('campo_nome_produto').value;
    const modal = new bootstrap.Modal(document.getElementById('modalGoogleIA'));
    modal.show();
    
    // Se você estiver usando a API JSON que criamos (google_busca.php):
    if (termo.length >= 3) {
        const corpo = document.getElementById('container-resultados-ia');
        corpo.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';
        
        fetch(`../api/google_busca.php?termo=${encodeURIComponent(termo)}`)
            .then(res => res.json())
            .then(data => {
                corpo.innerHTML = "";
                if (data.items) {
                    data.items.forEach(item => {
                        const div = document.createElement('div');
                        div.className = "resultado-google-clicavel border mb-2 shadow-sm p-3";
                        div.innerHTML = `<strong>${item.title}</strong><br><small class='text-muted'>${item.snippet}</small>`;
                        div.onclick = () => {
                            document.getElementById('campo_nome_produto').value = item.title;
                            modal.hide();
                        };
                        corpo.appendChild(div);
                    });
                }
            });
    }
}

// 6. Gestão de Carrinho
function funcaoAdicionarItem() {
    const nome = document.getElementById('campo_nome_produto').value;
    const preco = document.getElementById('campo_valor_produto').value;
    const qtd = parseInt(document.getElementById('campo_quantidade_produto').value);

    if (!nome || preco === "" || preco === "0,00") {
        Swal.fire('Aviso', 'Preencha nome e preço.', 'warning');
        return;
    }

    const preco_limpo = parseFloat(preco.replace(".", "").replace(",", "."));
    carrinho_local.push({ nome, preco: preco_limpo, quantidade: qtd });
    
    funcaoAtualizarCupom();
    
    // Limpeza
    document.getElementById('campo_nome_produto').value = "";
    document.getElementById('campo_valor_produto').value = "";
    document.getElementById('campo_quantidade_produto').value = "1";
    document.getElementById('campo_nome_produto').focus();
}

function funcaoAtualizarCupom() {
    const container = document.getElementById('renderizacao-itens-cupom');
    container.innerHTML = "";
    let total = 0;

    carrinho_local.forEach((item, index) => {
        const sub = item.preco * item.quantidade;
        total += sub;
        container.innerHTML += `
            <div class="linha-separadora-item d-flex justify-content-between align-items-center">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small class="text-muted">${item.quantidade} x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits:2})}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">R$ ${sub.toLocaleString('pt-BR', {minimumFractionDigits:2})}</div>
                    <button class="btn btn-sm text-danger p-0" onclick="funcaoRemover(${index})"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    });

    if (carrinho_local.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted small">Carrinho vazio</div>';
    }
    document.getElementById('valor_total_cupom').innerText = total.toLocaleString('pt-BR', {minimumFractionDigits:2});
}

function funcaoRemover(index) {
    carrinho_local.splice(index, 1);
    funcaoAtualizarCupom();
}

function funcaoVoltarParaInicio() {
    location.reload();
}

// 7. Finalização e Agradecimento
function funcaoFinalizarEGravarDados() {
    if (carrinho_local.length === 0) return;

    Swal.fire({
        title: 'Alimentando BI...',
        text: 'Aguarde um momento.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('salvar_carrinho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mercado_id: id_mercado_ativo, itens: carrinho_local })
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            Swal.fire({
                title: 'Concluído!',
                html: 'Obrigado por colaborar com nossa Coleta de Dados.<br><br>Sua contribuição ajuda o <strong>Mercado Inteligente</strong> a crescer!<br><br>Sempre utilize nosso sistema.',
                icon: 'success'
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