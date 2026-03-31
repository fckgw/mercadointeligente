<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/simulador.php
 * Finalidade: Interface mestre para simulação de compras, leitura de NFC-e e gravação de BI.
 * Desenvolvido por: BD Softech
 */

// Inicia a sessão para controle de identidade
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importação da conexão com o banco de dados
require_once '../core/db.php';

/**
 * CONFIGURAÇÃO DE IDENTIDADE DO USUÁRIO
 */
$usuario_esta_logado_atualmente = isset($_SESSION['usuario_id']);
$identificador_usuario_logado      = $_SESSION['usuario_id'] ?? null;
$nome_do_usuario_para_exibicao    = $usuario_esta_logado_atualmente ? $_SESSION['usuario_nome'] : "Visitante";
$data_do_ultimo_acesso_usuario    = $usuario_esta_logado_atualmente ? ($_SESSION['ultimo_acesso_formatado'] ?? date('d/m/Y H:i')) : "";

/**
 * CONSULTA DE ESTABELECIMENTOS DISPONÍVEIS
 */
try {
    $instrucao_sql_mercados = "SELECT id, nome FROM mercados ORDER BY nome ASC";
    $comando_recuperar_mercados = $pdo->query($instrucao_sql_mercados);
    $lista_de_mercados_disponiveis = $comando_recuperar_mercados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $excecao_banco) {
    $lista_de_mercados_disponiveis = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Simulador de Compras - Mercado Inteligente</title>
    
    <!-- Estilos e Bibliotecas -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 150px; font-family: 'Segoe UI', sans-serif; }
        
        /* Barra Superior */
        .barra-superior-identidade { background: #ffffff; border-bottom: 1px solid #dee2e6; padding: 12px 0; position: sticky; top: 0; z-index: 1050; }
        .texto-identificacao-usuario { font-size: 0.85rem; line-height: 1.2; }
        .nome-usuario-destaque { color: #0d6efd; font-weight: 700; text-transform: uppercase; }
        
        /* Cartões e Formulários */
        .cartao-principal-estilizado { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .campo-entrada-estilizado { padding: 15px; border-radius: 12px; font-size: 1.1rem; border: 1px solid #ced4da; }
        
        /* Recibo / Cupom Fiscal */
        .area-recibo-conferencia { background-color: #ffffff; padding: 25px; border: 1px solid #e0e0e0; font-family: 'Courier New', Courier, monospace; position: relative; border-radius: 4px; }
        .linha-divisor-item { border-bottom: 1px dashed #bbb; padding: 12px 0; }
        
        /* Autocomplete */
        #container-sugestoes-produtos { position: absolute; width: 100%; z-index: 2100; background: #fff; display: none; max-height: 250px; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .item-sugestao-clicavel { padding: 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; text-align: left; width: 100%; display: block; background: none; border: none; }
        .item-sugestao-clicavel:hover { background-color: #e7f1ff; }

        /* Rodapé Fixo */
        .painel-finalizar-fixo-centro { position: fixed; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, #f4f7f6 80%, transparent); padding: 20px; z-index: 2000; display: flex; justify-content: center; }
        .botao-finalizar-grande { width: 100%; max-width: 450px; border-radius: 50px; padding: 18px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(25, 135, 84, 0.4); border: 2px solid #fff; }
    </style>
</head>
<body>

<nav class="barra-superior-identidade shadow-sm mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="../index.php"><img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 40px;"></a>
        
        <div class="d-flex align-items-center text-end">
            <div class="texto-identificacao-usuario me-3">
                <span>Olá, <b class="nome-usuario-destaque"><?php echo $nome_do_usuario_para_exibicao; ?></b></span><br>
                <?php if ($usuario_esta_logado_atualmente): ?>
                    <small class="text-muted">Acesso: <?php echo $data_do_ultimo_acesso_usuario; ?></small>
                <?php endif; ?>
            </div>
            
            <?php if ($usuario_esta_logado_atualmente): ?>
                <a href="historico.php" class="btn btn-outline-primary btn-sm rounded-circle me-1" title="Meu Histórico"><i class="bi bi-clock-history"></i></a>
                <a href="../admin/logout.php" class="btn btn-outline-danger btn-sm rounded-circle" title="Sair do Sistema"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <a href="../admin/login.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <!-- ATALHOS RÁPIDOS -->
    <div class="row g-2 mb-4 px-2">
        <div class="col-6">
            <a href="coleta.php" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-primary fw-bold text-decoration-none text-center">
                <i class="bi bi-pencil-square d-block h4"></i><small>COLETA MANUAL</small>
            </a>
        </div>
        <div class="col-6">
            <button onclick="funcaoEscolherMetodoEntradaNotaFiscal()" class="btn btn-white border w-100 py-3 shadow-sm rounded-4 text-dark fw-bold text-center">
                <i class="bi bi-qr-code-scan d-block h4"></i><small>LER NOTA FISCAL</small>
            </button>
        </div>
    </div>

    <!-- PASSO 1: SELEÇÃO DE LOCAL -->
    <div class="card cartao-principal-estilizado p-4 mb-4" id="sessao-selecionar-mercado-inicial">
        <h6 class="fw-bold text-primary mb-3 text-center text-uppercase small">Onde você está agora?</h6>
        <div class="input-group mb-4">
            <select id="campo_identificador_mercado_selecionado" class="form-select campo-entrada-estilizado shadow-sm">
                <option value="">Selecione o Supermercado...</option>
                <?php foreach($lista_de_mercados_disponiveis as $mercado_item): ?>
                    <option value="<?php echo $mercado_item['id']; ?>"><?php echo $mercado_item['nome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow" onclick="funcaoIniciarInterfaceDoCarrinho()">
            ABRIR MEU CARRINHO
        </button>
    </div>

    <!-- PASSO 2: INTERFACE DE LANÇAMENTO -->
    <div id="sessao-interface-carrinho-ativa" style="display: none;">
        
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <button class="btn btn-sm btn-outline-secondary rounded-pill bg-white shadow-sm" onclick="window.location.reload()"><i class="bi bi-arrow-left"></i> Trocar Local</button>
            <span class="badge bg-primary text-white border shadow-sm px-3 py-2" id="etiqueta-nome-mercado-visual">MERCADO</span>
        </div>

        <div class="card cartao-principal-estilizado p-4 mb-4 border-top border-primary border-5">
            <div class="position-relative mb-3">
                <label class="form-label small fw-bold text-muted">PRODUTO</label>
                <input type="text" id="input_nome_produto_atual" class="form-control campo-entrada-estilizado" placeholder="O que deseja comprar?" autocomplete="off" onkeyup="funcaoPesquisarProdutosNaBaseDinamica(this.value)">
                <input type="hidden" id="input_id_produto_vencido">
                <div id="container-sugestoes-produtos"></div>
            </div>

            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="unidade_medida_escolhida" id="medida_unidade" value="UN" checked onchange="funcaoAjustarMascaraDeQuantidade()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="medida_unidade">UNID.</label>
                <input type="radio" class="btn-check" name="unidade_medida_escolhida" id="medida_quilo" value="KG" onchange="funcaoAjustarMascaraDeQuantidade()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="medida_quilo">QUILO</label>
                <input type="radio" class="btn-check" name="unidade_medida_escolhida" id="medida_litro" value="L" onchange="funcaoAjustarMascaraDeQuantidade()">
                <label class="btn btn-outline-primary py-2 fw-bold" for="medida_litro">LITRO</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold text-success">PREÇO UNITÁRIO</label>
                    <input type="text" id="input_valor_unitario_lancamento" class="form-control campo-entrada-estilizado fw-bold text-success" inputmode="numeric" placeholder="0,00" onkeyup="funcaoAplicarMascaraMoedaBrasileira(this)">
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold text-muted" id="label-quantidade-instrucao">QUANTIDADE</label>
                    <input type="text" id="input_quantidade_lancamento" class="form-control campo-entrada-estilizado text-center" inputmode="numeric" value="1" onkeyup="funcaoAplicarMascaraPesoOuGrama(this)">
                </div>
            </div>

            <div class="alert alert-light border text-center p-2 mb-3 shadow-sm">
                <small class="text-muted">Subtotal item: </small>
                <span class="fw-bold text-dark h5" id="label_previa_subtotal_item">R$ 0,00</span>
            </div>

            <button onclick="funcaoAdicionarItemNoCarrinhoLocal()" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                <i class="bi bi-cart-plus-fill me-1"></i> ADICIONAR ITEM
            </button>
        </div>

        <div class="area-recibo-conferencia shadow-sm mb-5">
            <div id="container-itens-cupom-fiscal">
                <!-- Injetado via JS -->
            </div>
            <div class="mt-4 border-top border-2 border-dark pt-3 d-flex justify-content-between h3 fw-bold">
                <span>TOTAL R$</span>
                <span id="valor-total-cupom-exibicao">0,00</span>
            </div>
        </div>
    </div>
</div>

<div class="painel-finalizar-fixo-centro" id="bloco-botao-finalizar-bi" style="display: none;">
    <button class="btn btn-success botao-finalizar-grande shadow-lg" onclick="funcaoFinalizarGravacaoOficialNoBI()">
        <i class="bi bi-cloud-arrow-up-fill me-2"></i> FECHAR E SALVAR COMPRA
    </button>
</div>

<!-- MODAL CÂMERA -->
<div class="modal fade" id="modalCameraLeitor" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered text-center">
        <div class="modal-content"><div class="modal-body">
            <div id="caixa-leitura-video-qr" style="width: 100%;"></div>
            <button class="btn btn-danger mt-3 w-100 fw-bold" data-bs-dismiss="modal" onclick="funcaoPararCameraLeitora()">CANCELAR LEITURA</button>
        </div></div>
    </div>
</div>

<script>
/**
 * LÓGICA DO SIMULADOR - BD SOFTECH
 */
let lista_de_itens_no_carrinho = [];
let identificador_mercado_referencia = "";
let instancia_do_leitor_qrcode = null;
const usuario_está_logado = <?php echo $usuario_esta_logado_atualmente ? 'true' : 'false'; ?>;

function funcaoIniciarInterfaceDoCarrinho() {
    const seletor_mercado = document.getElementById('campo_identificador_mercado_selecionado');
    identificador_mercado_referencia = seletor_mercado.value;
    
    if (!identificador_mercado_referencia) {
        Swal.fire('Atenção', 'Selecione o supermercado primeiro.', 'info');
        return;
    }

    document.getElementById('sessao-selecionar-mercado-inicial').style.display = 'none';
    document.getElementById('sessao-interface-carrinho-ativa').style.display = 'block';
    document.getElementById('bloco-botao-finalizar-bi').style.display = 'flex';
    document.getElementById('etiqueta-nome-mercado-visual').innerText = seletor_mercado.options[seletor_mercado.selectedIndex].text.toUpperCase();

    // Sincroniza rascunhos salvos no banco de dados
    if (usuario_está_logado) {
        fetch(`../api/gerenciar_temporarios.php?acao=listar_itens_temporarios&mercado_id=${identificador_mercado_referencia}`)
        .then(resposta => resposta.json()).then(dados => {
            if (dados.sucesso) {
                lista_de_itens_no_carrinho = dados.lista_itens;
                funcaoRenderizarVisualNoCupom();
            }
        });
    }
}

function funcaoAdicionarItemNoCarrinhoLocal() {
    const nome_produto = document.getElementById('input_nome_produto_atual').value;
    const id_produto = document.getElementById('input_id_produto_vencido').value;
    const preco_string = document.getElementById('input_valor_unitario_lancamento').value;
    const quantidade_string = document.getElementById('input_quantidade_lancamento').value;
    const unidade_medida = document.querySelector('input[name="unidade_medida_escolhida"]:checked').value;

    if (!nome_produto || !preco_string || preco_string === "0,00") {
        Swal.fire('Aviso', 'Preencha o nome e o preço corretamente.', 'warning');
        return;
    }

    const valor_unitario = parseFloat(preco_string.replace(/\./g, '').replace(',', '.'));
    const valor_quantidade = parseFloat(quantidade_string.replace(',', '.'));
    const valor_subtotal = parseFloat((valor_unitario * valor_quantidade).toFixed(2));

    const objeto_item = { 
        nome: nome_produto, 
        produto_id: id_produto, 
        preco: valor_unitario, 
        quantidade: valor_quantidade, 
        unidade: unidade_medida, 
        subtotal: valor_subtotal 
    };

    if (usuario_está_logado) {
        fetch('../api/gerenciar_temporarios.php?acao=adicionar_item_temporario', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({...objeto_item, mercado_id: identificador_mercado_referencia})
        }).then(() => {
            // Recarrega lista para obter IDs do banco para edição
            funcaoIniciarInterfaceDoCarrinho();
        });
    } else {
        lista_de_itens_no_carrinho.push(objeto_item);
        funcaoRenderizarVisualNoCupom();
    }

    // Reset de campos
    document.getElementById('input_nome_produto_atual').value = "";
    document.getElementById('input_id_produto_vencido').value = "";
    document.getElementById('input_valor_unitario_lancamento').value = "";
    document.getElementById('label_previa_subtotal_item').innerText = "R$ 0,00";
    document.getElementById('input_nome_produto_atual').focus();
}

function funcaoRenderizarVisualNoCupom() {
    const container_cupom = document.getElementById('container-itens-cupom-fiscal');
    container_cupom.innerHTML = "";
    let valor_total_acumulado = 0;

    lista_de_itens_no_carrinho.forEach((item, indice) => {
        const subtotal_formatado = Number(item.subtotal);
        valor_total_acumulado += subtotal_formatado;
        const decimais_quantidade = (item.unidade === 'UN') ? 0 : 3;

        container_cupom.innerHTML += `
            <div class="linha-divisor-item d-flex justify-content-between align-items-center">
                <div style="flex:1">
                    <div class="fw-bold small text-uppercase">${item.nome}</div>
                    <small class="text-muted">${item.quantidade.toLocaleString('pt-BR', {minimumFractionDigits: decimais_quantidade})} ${item.unidade} x R$ ${Number(item.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="text-end" style="min-width: 110px;">
                    <div class="fw-bold">R$ ${subtotal_formatado.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                    <div class="mt-1">
                        <button class="btn btn-sm btn-light border" onclick="funcaoEditarItemDoCarrinho(${indice})"><i class="bi bi-pencil text-primary"></i></button>
                        <button class="btn btn-sm btn-light border" onclick="funcaoRemoverItemDoCarrinho(${indice}, ${item.id || 0})"><i class="bi bi-trash text-danger"></i></button>
                    </div>
                </div>
            </div>`;
    });
    document.getElementById('valor-total-cupom-exibicao').innerText = valor_total_acumulado.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}

function funcaoRemoverItemDoCarrinho(indice, id_banco) {
    if (id_banco > 0 && navigator.onLine) {
        fetch(`../api/gerenciar_temporarios.php?acao=remover_item_rascunho&id=${id_banco}`);
    }
    lista_de_itens_no_carrinho.splice(indice, 1);
    funcaoRenderizarVisualNoCupom();
}

function funcaoEditarItemDoCarrinho(indice) {
    const item_referencia = lista_de_itens_no_carrinho[indice];
    document.getElementById('input_nome_produto_atual').value = item_referencia.nome;
    document.getElementById('input_id_produto_vencido').value = item_referencia.produto_id || "";
    document.getElementById('input_valor_unitario_lancamento').value = Number(item_referencia.preco).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    if (item_referencia.unidade === 'KG') document.getElementById('medida_quilo').checked = true;
    else if (item_referencia.unidade === 'L') document.getElementById('medida_litro').checked = true;
    else document.getElementById('medida_unidade').checked = true;

    funcaoAjustarMascaraDeQuantidade();
    document.getElementById('input_quantidade_lancamento').value = item_referencia.quantidade.toLocaleString('pt-BR', {minimumFractionDigits: (item_referencia.unidade === 'UN' ? 0 : 3)});
    
    funcaoRemoverItemDoCarrinho(indice, item_referencia.id || 0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function funcaoEscolherMetodoEntradaNotaFiscal() {
    if (!identificador_mercado_referencia) {
        Swal.fire('Aviso', 'Selecione o supermercado primeiro.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Importar Nota Fiscal',
        text: 'Escolha como deseja ler os dados:',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Colar Link (URL)',
        denyButtonText: 'Usar Câmera (QR)',
        cancelButtonText: 'Cancelar'
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            Swal.fire({ title: 'Cole a URL da Nota', input: 'text', showCancelButton: true }).then(r => {
                if(r.value) funcaoEnviarUrlNotaParaServidor(r.value);
            });
        } else if (resultado.isDenied) {
            new bootstrap.Modal(document.getElementById('modalCameraLeitor')).show();
            instancia_do_leitor_qrcode = new Html5Qrcode("caixa-leitura-video-qr");
            instancia_do_leitor_qrcode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, (texto_decodificado) => {
                funcaoPararCameraLeitora();
                bootstrap.Modal.getInstance(document.getElementById('modalCameraLeitor')).hide();
                funcaoEnviarUrlNotaParaServidor(texto_decodificado);
            });
        }
    });
}

function funcaoEnviarUrlNotaParaServidor(url_recebida) {
    Swal.fire({ title: 'Processando Nota Fiscal...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('../api/processar_nfce.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ url: url_recebida, mercado_id: identificador_mercado_referencia })
    }).then(r => r.json()).then(dados => {
        if (dados.sucesso) {
            Swal.fire('Pronto!', 'Dados importados com sucesso.', 'success').then(() => funcaoIniciarInterfaceDoCarrinho());
        } else {
            Swal.fire('Erro', dados.mensagem_erro, 'error');
        }
    });
}

function funcaoPararCameraLeitora() {
    if (instancia_do_leitor_qrcode) {
        instancia_do_leitor_qrcode.stop().catch(erro => console.log(erro));
    }
}

function funcaoFinalizarGravacaoOficialNoBI() {
    if (lista_de_itens_no_carrinho.length === 0) return;

    Swal.fire({ 
        title: 'Gravando no BI...', 
        text: 'Validando e salvando preços...',
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
    });

    fetch('../api/gerenciar_temporarios.php?acao=finalizar_e_gravar_oficial', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ mercado_id: identificador_mercado_referencia })
    })
    .then(resposta => resposta.json())
    .then(dados => {
        if (dados.sucesso) {
            Swal.fire('Sucesso!', 'Dados migrados para o histórico oficial!', 'success').then(() => location.reload());
        } else {
            Swal.fire('Erro de Integridade', dados.mensagem_erro, 'error');
        }
    })
    .catch(() => Swal.fire('Erro Crítico', 'Falha de comunicação com o servidor.', 'error'));
}

/**
 * MÁSCARAS E AUXILIARES
 */
function funcaoPesquisarProdutosNaBaseDinamica(termo_digitado) {
    const caixa_sugestoes = document.getElementById('container-sugestoes-produtos');
    if (termo_digitado.length < 2) {
        caixa_sugestoes.style.display = 'none';
        return;
    }

    fetch(`../api/buscar_produtos.php?termo=${encodeURIComponent(termo_digitado)}`)
    .then(r => r.json()).then(lista_produtos => {
        caixa_sugestoes.innerHTML = "";
        if (lista_produtos.length > 0) {
            caixa_sugestoes.style.display = 'block';
            lista_produtos.forEach(produto => {
                const botao_sugestao = document.createElement('button');
                botao_sugestao.className = "item-sugestao-clicavel";
                botao_sugestao.innerHTML = `<strong>${produto.nome}</strong> - <small>${produto.marca}</small>`;
                botao_sugestao.onclick = () => { 
                    document.getElementById('input_nome_produto_atual').value = produto.nome;
                    document.getElementById('input_id_produto_vencido').value = produto.id;
                    caixa_sugestoes.style.display = 'none';
                };
                caixa_sugestoes.appendChild(botao_sugestao);
            });
        }
    });
}

function funcaoAplicarMascaraMoedaBrasileira(campo_elemento) {
    let valor_limpo = campo_elemento.value.replace(/\D/g, "");
    valor_limpo = (valor_limpo / 100).toFixed(2).replace(".", ",");
    valor_limpo = valor_limpo.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor_limpo = valor_limpo.replace(/(\d)(\d{3}),/g, "$1.$2,");
    campo_elemento.value = valor_limpo;
    funcaoCalcularSubtotalPrevio();
}

function funcaoAplicarMascaraPesoOuGrama(campo_elemento) {
    const medida_atual = document.querySelector('input[name="unidade_medida_escolhida"]:checked').value;
    if (medida_atual === 'UN') {
        campo_elemento.value = campo_elemento.value.replace(/\D/g, "");
    } else {
        let valor_peso = campo_elemento.value.replace(/\D/g, "");
        campo_elemento.value = (valor_peso / 1000).toFixed(3).replace(".", ",");
    }
    funcaoCalcularSubtotalPrevio();
}

function funcaoCalcularSubtotalPrevio() {
    const texto_preco = document.getElementById('input_valor_unitario_lancamento').value;
    const texto_quantidade = document.getElementById('input_quantidade_lancamento').value;
    
    if (texto_preco && texto_quantidade) {
        const num_preco = parseFloat(texto_preco.replace(/\./g, '').replace(',', '.'));
        const num_quantidade = parseFloat(texto_quantidade.replace(',', '.'));
        const total_item = (num_preco * num_quantidade) || 0;
        document.getElementById('label_previa_subtotal_item').innerText = "R$ " + total_item.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function funcaoAjustarMascaraDeQuantidade() {
    const input_lancamento = document.getElementById('input_quantidade_lancamento');
    const label_instrucao = document.getElementById('label-quantidade-instrucao');
    const tipo_medida = document.querySelector('input[name="unidade_medida_escolhida"]:checked').value;
    
    label_instrucao.innerText = (tipo_medida === 'UN') ? "QUANTIDADE" : "PESO / LITRO (Ex: 0,500)";
    input_lancamento.value = (tipo_medida === 'UN') ? "1" : "0,000";
    funcaoCalcularSubtotalPrevio();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>