<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Simulador de compras com fluxo direto de salvamento de dados para o BI.
 */

require_once '../core/db.php';

// Busca a lista de mercados cadastrados para São José dos Campos
$comando_preparado_mercados = $pdo->query("SELECT id, nome, regiao FROM mercados ORDER BY nome ASC");
$lista_de_mercados_disponiveis = $comando_preparado_mercados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador de Compras - Mercado Inteligente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script do Google Custom Search Engine -->
    <script async src="https://cse.google.com/cse.js?cx=025203d8a65434468"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 120px; font-family: 'Segoe UI', sans-serif; }
        .cartao-principal { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: #fff; }
        .input-mobile { padding: 14px; border-radius: 12px; font-size: 1.1rem; border: 1px solid #ced4da; }
        .cupom-fiscal { background: #ffffff; padding: 25px; border: 1px solid #ddd; font-family: 'Courier New', monospace; position: relative; }
        .linha-item { border-bottom: 1px dashed #ccc; padding: 12px 0; }
        .botao-flutuante { position: fixed; bottom: 20px; left: 20px; right: 20px; border-radius: 50px; padding: 18px; font-weight: bold; z-index: 1000; box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4); }
        .gsc-control-cse { padding: 0 !important; border: none !important; }
        .item-resultado-google { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; border-radius: 10px; margin-bottom: 5px; transition: 0.2s; }
        .item-resultado-google:hover { background-color: #e7f1ff; color: #0d6efd; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="text-center mb-4">
        <a href="../index.php"><img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 50px;"></a>
    </div>

    <!-- PASSO 1: SELEÇÃO DE MERCADO -->
    <div class="card cartao-principal p-4 mb-4" id="sessao-mercado">
        <h6 class="fw-bold text-primary mb-3 text-uppercase small">Onde você está comprando?</h6>
        <div class="input-group mb-3">
            <select id="campo_id_mercado" class="form-select input-mobile border-primary">
                <option value="">Selecione o Supermercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $mercado): ?>
                    <option value="<?php echo $mercado['id']; ?>"><?php echo $mercado['nome']; ?> (<?php echo $mercado['regiao']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold" onclick="funcaoIniciarCarrinho()">
            INICIAR MINHA LISTA
        </button>
    </div>

    <!-- PASSO 2: FORMULÁRIO DE PRODUTOS -->
    <div id="sessao-carrinho" style="display: none;">
        <div class="card cartao-principal p-4 mb-4 border-top border-primary border-5">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <div class="input-group">
                    <input type="text" id="campo_nome_produto" class="form-control input-mobile" placeholder="Ex: Arroz Camil 5kg">
                    <button class="btn btn-warning px-3 shadow-sm" onclick="funcaoAbrirInteligenciaGoogle()">
                        <i class="bi bi-google"></i> IA
                    </button>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">PREÇO UNITÁRIO</label>
                    <input type="text" id="campo_preco_produto" class="form-control input-mobile fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoMascaraMoeda(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted">QUANTIDADE</label>
                    <input type="number" id="campo_quantidade_produto" class="form-control input-mobile" value="1">
                </div>
            </div>

            <button onclick="funcaoAdicionarItem()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <!-- VISUALIZAÇÃO DO CUPOM FISCAL -->
        <div class="area-cupom-fiscal shadow-sm mb-5">
            <div class="text-center mb-4">
                <h5 class="fw-bold mb-0" id="cupom_mercado_nome">CONFERÊNCIA</h5>
                <small>SÃO JOSÉ DOS CAMPOS - SP</small>
                <div class="mt-2 border-top border-1 border-dark"></div>
            </div>

            <div id="container-itens-carrinho">
                <div class="text-center py-4 text-muted small">Carrinho vazio</div>
            </div>

            <div class="mt-4 border-top border-2 border-dark pt-3">
                <div class="d-flex justify-content-between h3 fw-bold">
                    <span>TOTAL R$</span>
                    <span id="valor_total_exibicao">0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTÃO FINALIZAR (Sempre salvar) -->
<button id="btn-finalizar-bi" class="btn btn-success botao-flutuante shadow-lg border-white border-2" style="display: none;" onclick="funcaoSalvarCompraNoBanco()">
    <i class="bi bi-cloud-check-fill me-2"></i> FECHAR E SALVAR COMPRA
</button>

<!-- MODAL DE CAPTURA IA -->
<div class="modal fade" id="modalGoogleIA" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="bi bi-google text-primary"></i> Selecione a Sugestão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="corpo_resultados_google" style="max-height: 400px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<script>
let lista_carrinho_atual = [];
let identificador_mercado_selecionado = "";

function funcaoIniciarCarrinho() {
    identificador_mercado_selecionado = document.getElementById('campo_id_mercado').value;
    const nome_mercado = document.getElementById('campo_id_mercado').options[document.getElementById('campo_id_mercado').selectedIndex].text;
    
    if (!identificador_mercado_selecionado) {
        Swal.fire('Aviso', 'Selecione o supermercado primeiro.', 'info');
        return;
    }

    document.getElementById('sessao-mercado').style.display = 'none';
    document.getElementById('sessao-carrinho').style.display = 'block';
    document.getElementById('btn-finalizar-bi').style.display = 'block';
    document.getElementById('cupom_mercado_nome').innerText = nome_mercado.toUpperCase();
}

function funcaoMascaraMoeda(campo) {
    let valor = campo.value.replace(/\D/g, "");
    valor = (valor / 100).toFixed(2) + "";
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor = valor.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo.value = valor;
}

function funcaoAbrirInteligenciaGoogle() {
    const termo = document.getElementById('campo_nome_produto').value;
    if (termo.length < 3) {
        Swal.fire('Dica', 'Digite parte do nome para a IA ajudar.', 'info');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalGoogleIA'));
    const container = document.getElementById('corpo_resultados_google');
    container.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Consultando IA do Google...</p></div>';
    modal.show();

    fetch(`../api/google_busca.php?termo=${encodeURIComponent(termo)}`)
        .then(res => res.json())
        .then(dados => {
            container.innerHTML = "";
            if (dados.items && dados.items.length > 0) {
                dados.items.forEach(item => {
                    const div_item = document.createElement('div');
                    div_item.className = "item-resultado-google border shadow-sm";
                    div_item.innerHTML = `<strong>${item.title}</strong><br><small class='text-muted'>${item.snippet}</small>`;
                    div_item.onclick = () => {
                        document.getElementById('campo_nome_produto').value = item.title;
                        modal.hide();
                    };
                    container.appendChild(div_item);
                });
            } else {
                container.innerHTML = '<p class="text-center p-4">Nenhuma sugestão encontrada.</p>';
            }
        });
}

function funcaoAdicionarItem() {
    const nome = document.getElementById('campo_nome_produto').value;
    const preco_texto = document.getElementById('campo_preco_produto').value;
    const quantidade = parseInt(document.getElementById('campo_quantidade_produto').value);

    if (!nome || preco_texto === "" || preco_texto === "0,00") {
        Swal.fire('Aviso', 'Preencha o nome e o preço.', 'warning');
        return;
    }

    const preco_final = parseFloat(preco_texto.replace(".", "").replace(",", "."));
    lista_carrinho_atual.push({ nome, preco: preco_final, quantidade });
    
    funcaoRenderizarCarrinho();
    
    document.getElementById('campo_nome_produto').value = "";
    document.getElementById('campo_preco_produto').value = "";
    document.getElementById('campo_quantidade_produto').value = "1";
    document.getElementById('campo_nome_produto').focus();
}

function funcaoRenderizarCarrinho() {
    const container = document.getElementById('container-itens-carrinho');
    container.innerHTML = "";
    let soma_total = 0;

    lista_carrinho_atual.forEach((item, indice) => {
        const subtotal = item.preco * item.quantidade;
        soma_total += subtotal;

        container.innerHTML += `
            <div class="linha-item d-flex justify-content-between align-items-center">
                <div style="flex: 1;">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small class="text-muted">${item.quantidade} x R$ ${item.preco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <button class="btn btn-sm btn-light border text-danger" onclick="funcaoRemoverItem(${indice})"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    });

    if (lista_carrinho_atual.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted small">Carrinho vazio</div>';
    }

    document.getElementById('valor_total_exibicao').innerText = soma_total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverItem(indice) {
    lista_carrinho_atual.splice(indice, 1);
    funcaoRenderizarCarrinho();
}

/**
 * FUNÇÃO DE SALVAMENTO DIRETO (BI)
 * Alterada para não dar opção de cancelar ao cliente, garantindo a alimentação da base.
 */
function funcaoSalvarCompraNoBanco() {
    if (lista_carrinho_atual.length === 0) return;

    // Exibe apenas um pop-up informativo de "Salvando", sem botão de cancelar.
    Swal.fire({
        title: 'Salvando no BI...',
        text: 'Aguarde enquanto processamos sua lista.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('salvar_carrinho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            mercado_id: identificador_mercado_selecionado, 
            itens: lista_carrinho_atual 
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            Swal.fire({
                title: 'Concluído!',
                text: 'Sua lista alimentou nossa Inteligência de Mercado.',
                icon: 'success',
                confirmButtonText: 'Fechar e Voltar',
                confirmButtonColor: '#198754'
            }).then(() => {
                // Reseta a tela zerada (volta para a seleção de mercado e limpa carrinho)
                lista_carrinho_atual = [];
                funcaoRenderizarCarrinho();
                document.getElementById('sessao-carrinho').style.display = 'none';
                document.getElementById('btn-finalizar-bi').style.display = 'none';
                document.getElementById('sessao-mercado').style.display = 'block';
                document.getElementById('campo_id_mercado').value = "";
            });
        } else {
            Swal.fire('Erro', 'Houve um problema ao salvar os dados.', 'error');
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>