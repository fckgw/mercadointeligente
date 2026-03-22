<?php 
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/produtos.php
 * Finalidade: Gestão completa de produtos, filtros avançados e edição em massa.
 */

// Configurações para exibição de erros durante o desenvolvimento na Locaweb
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Controle de sessão e conexão com o banco de dados
require_once 'sessao.php';
require_once '../core/db.php';

// Captura dos parâmetros de busca e filtro vindos da URL
$termo_de_pesquisa = $_GET['q'] ?? '';
$categoria_para_filtro = $_GET['cat'] ?? '';

/**
 * CONSULTA 1: BUSCA DE CATEGORIAS
 * Obtém todas as categorias únicas cadastradas para alimentar o filtro e o modal.
 */
$consulta_categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
$lista_de_categorias_cadastradas = $consulta_categorias->fetchAll(PDO::FETCH_COLUMN);

/**
 * CONSULTA 2: BUSCA DE MERCADOS
 * Necessário para o modal de lançamento de preço manual.
 */
$consulta_mercados = $pdo->query("SELECT id, nome, regiao FROM mercados ORDER BY nome ASC");
$lista_de_mercados_disponiveis = $consulta_mercados->fetchAll();

/**
 * CONSULTA 3: LISTAGEM DE PRODUTOS COM ÚLTIMA COTAÇÃO
 * Query otimizada para trazer o produto e apenas o preço mais recente vinculado a ele.
 */
$instrucao_sql_principal = "
    SELECT 
        produtos.id, 
        produtos.nome, 
        produtos.marca, 
        produtos.categoria,
        precos.valor_unitario, 
        precos.data_da_coleta, 
        mercados.nome AS mercado_nome
    FROM produtos
    LEFT JOIN precos ON precos.id = (
        SELECT id FROM precos 
        WHERE produto_id = produtos.id 
        ORDER BY data_da_coleta DESC LIMIT 1
    )
    LEFT JOIN mercados ON precos.mercado_id = mercados.id
    WHERE 1=1
";

$parametros_da_busca = [];

// Aplica o filtro de texto (Nome ou Marca)
if (!empty($termo_de_pesquisa)) {
    $instrucao_sql_principal .= " AND (produtos.nome LIKE ? OR produtos.marca LIKE ?)";
    $parametros_da_busca[] = "%$termo_de_pesquisa%";
    $parametros_da_busca[] = "%$termo_de_pesquisa%";
}

// Aplica o filtro por categoria selecionada no DropDown
if (!empty($categoria_para_filtro)) {
    $instrucao_sql_principal .= " AND produtos.categoria = ?";
    $parametros_da_busca[] = $categoria_para_filtro;
}

$instrucao_sql_principal .= " ORDER BY produtos.nome ASC";

$comando_preparado = $pdo->prepare($instrucao_sql_principal);
$comando_preparado->execute($parametros_da_busca);
$lista_final_de_produtos = $comando_preparado->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SweetAlert2 para Pop-ups profissionais -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f4f7f6; padding-bottom: 100px; }
        .cabecalho-fixo { background: #fff; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; z-index: 1020; }
        .cartao-produto { border: 2px solid transparent; border-radius: 15px; transition: 0.3s; cursor: pointer; background: #fff; }
        .cartao-produto.selecionado { border-color: #0d6efd; background-color: #f0f7ff; }
        .botao-editar-flutuante { position: absolute; top: 10px; right: 10px; z-index: 5; }
        
        /* Barra de Ação em Massa Estilo Mobile */
        #barra-acao-massa {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            width: 90%; max-width: 600px; background: #212529; color: white;
            padding: 15px 25px; border-radius: 50px; display: none;
            justify-content: space-between; align-items: center; z-index: 2000;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body>

<div class="cabecalho-fixo p-3 shadow-sm mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="dashboard.php" class="btn btn-sm btn-light border text-muted"><i class="bi bi-chevron-left"></i> Painel</a>
            <img src="../images/Logo_MercadoInteligente.png" alt="Logo" style="max-height: 40px;">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="selecionarTodosProdutos" onclick="funcaoAlternarSelecaoGeral(this)">
                <label class="form-check-label small fw-bold">Tudo</label>
            </div>
        </div>

        <form action="" method="GET" class="row g-2">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Nome ou Marca..." value="<?php echo htmlspecialchars($termo_de_pesquisa); ?>">
                </div>
            </div>
            <div class="col-9 col-md-5">
                <select name="cat" class="form-select">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($lista_de_categorias_cadastradas as $categoria_da_lista): ?>
                        <option value="<?php echo $categoria_da_lista; ?>" <?php echo ($categoria_para_filtro == $categoria_da_lista) ? 'selected' : ''; ?>>
                            <?php echo $categoria_da_lista; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3 col-md-2">
                <button class="btn btn-primary w-100 fw-bold" type="submit">OK</button>
            </div>
        </form>
    </div>
</div>

<!-- BARRA DE AÇÕES EM MASSA -->
<div id="barra-acao-massa">
    <span id="texto-contagem-selecao" class="small">0 itens selecionados</span>
    <button class="btn btn-warning btn-sm fw-bold rounded-pill px-3" onclick="abrirModalEdicaoEmMassa()">
        <i class="bi bi-tags-fill me-1"></i> Categorizar em Massa
    </button>
</div>

<div class="container">
    <div class="row g-3">
        <?php foreach ($lista_final_de_produtos as $produto): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card cartao-produto p-3 h-100 shadow-sm position-relative" id="container_produto_<?php echo $produto['id']; ?>">
                    
                    <div class="d-flex justify-content-between align-items-start">
                        <!-- Checkbox de Seleção -->
                        <input type="checkbox" name="produtos_selecionados[]" value="<?php echo $produto['id']; ?>" class="form-check-input check-produto-item" style="width:25px; height:25px;" onclick="funcaoAtualizarContagemSelecao()">
                        
                        <!-- Botão Editar Individual -->
                        <button onclick="abrirModalEdicaoIndividual(<?php echo htmlspecialchars(json_encode($produto)); ?>)" class="btn btn-light btn-sm border shadow-sm botao-editar-flutuante">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>

                    <div class="mt-2">
                        <span class="badge bg-primary-subtle text-primary text-uppercase mb-1" style="font-size: 0.65rem;"><?php echo $produto['marca']; ?></span>
                        <h6 class="fw-bold text-dark mb-1 lh-sm"><?php echo $produto['nome']; ?></h6>
                        <span class="badge bg-light text-muted border fw-normal"><?php echo $produto['categoria']; ?></span>
                    </div>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-success fw-bold mb-0">R$ <?php echo number_format($produto['valor_unitario'], 2, ',', '.'); ?></h4>
                            <small class="text-muted" style="font-size: 0.7rem;"><?php echo $produto['mercado_nome'] ?: 'Sem coleta'; ?></small>
                        </div>
                        <a href="historico_produto.php?id=<?php echo $produto['id']; ?>" class="btn btn-sm btn-outline-dark" title="Ver Histórico BI">
                            <i class="bi bi-graph-up"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL DE EDIÇÃO EM MASSA -->
<div class="modal fade" id="modalEdicaoEmMassa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="salvar_edicao_massa.php" method="POST" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Categorização em Massa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="ids_dos_produtos" id="input_ids_massa">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Selecione uma Categoria Existente</label>
                    <select name="categoria_existente" id="dropdown_categoria_massa" class="form-select" onchange="verificarOpcaoNovaCategoria(this.value)">
                        <option value="">Escolher da lista...</option>
                        <?php foreach ($lista_de_categorias_cadastradas as $cat_modal): ?>
                            <option value="<?php echo $cat_modal; ?>"><?php echo $cat_modal; ?></option>
                        <?php endforeach; ?>
                        <option value="ADICIONAR_NOVA" class="text-primary fw-bold">+ Adicionar Nova Categoria</option>
                    </select>
                </div>

                <div id="container_entrada_manual_categoria" style="display: none;" class="p-3 bg-light rounded-3 mb-3 border">
                    <label class="form-label small fw-bold">Nome da Nova Categoria</label>
                    <input type="text" name="categoria_nova_manual" id="input_categoria_manual" class="form-control" placeholder="Ex: Bebidas Alcoólicas">
                </div>

                <button type="submit" class="btn btn-warning btn-lg w-100 shadow fw-bold py-3 mt-2">
                    <i class="bi bi-check-all"></i> Aplicar a todos selecionados
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Alterna a visibilidade do campo de texto caso a opção "Adicionar Nova" seja selecionada.
 */
function verificarOpcaoNovaCategoria(valor_selecionado) {
    const containerManual = document.getElementById('container_entrada_manual_categoria');
    const inputManual = document.getElementById('input_categoria_manual');
    
    if (valor_selecionado === 'ADICIONAR_NOVA') {
        containerManual.style.display = 'block';
        inputManual.required = true;
        inputManual.focus();
    } else {
        containerManual.style.display = 'none';
        inputManual.required = false;
    }
}

/**
 * Controla a exibição da barra de ações em massa e o destaque visual dos cards.
 */
function funcaoAtualizarContagemSelecao() {
    const itens_checados = document.querySelectorAll('input[name="produtos_selecionados[]"]:checked');
    const barra_bulk = document.getElementById('barra-acao-massa');
    const label_contagem = document.getElementById('texto-contagem-selecao');
    
    // Remove destaque de todos e adiciona apenas nos selecionados
    document.querySelectorAll('.cartao-produto').forEach(card => card.classList.remove('selecionado'));
    
    itens_checados.forEach(item => {
        document.getElementById('container_produto_' + item.value).classList.add('selecionado');
    });

    if (itens_checados.length > 0) {
        barra_bulk.style.display = 'flex';
        label_contagem.innerText = itens_checados.length + ' produtos selecionados';
    } else {
        barra_bulk.style.display = 'none';
    }
}

/**
 * Seleciona ou desseleciona todos os itens da página.
 */
function funcaoAlternarSelecaoGeral(fonte_do_evento) {
    const todos_os_checkboxes = document.getElementsByName('produtos_selecionados[]');
    for (let i = 0; i < todos_os_checkboxes.length; i++) {
        todos_os_checkboxes[i].checked = fonte_do_evento.checked;
    }
    funcaoAtualizarContagemSelecao();
}

/**
 * Prepara e abre o modal de edição em massa.
 */
function abrirModalEdicaoEmMassa() {
    const itens_selecionados = document.querySelectorAll('input[name="produtos_selecionados[]"]:checked');
    let lista_de_ids = [];
    itens_selecionados.forEach(item => lista_de_ids.push(item.value));
    
    document.getElementById('input_ids_massa').value = lista_de_ids.join(',');
    new bootstrap.Modal(document.getElementById('modalEdicaoEmMassa')).show();
}

/**
 * Dispara o SweetAlert caso o processamento em massa tenha sido concluído.
 */
document.addEventListener('DOMContentLoaded', function() {
    const parametros_da_url = new URLSearchParams(window.location.search);
    const total_alterado = parametros_da_url.get('sucesso_massa');
    
    if (total_alterado) {
        Swal.fire({
            title: 'Categorização Concluída!',
            text: total_alterado + ' produtos foram organizados com sucesso.',
            icon: 'success',
            confirmButtonText: 'Ótimo',
            confirmButtonColor: '#0d6efd'
        }).then(() => {
            // Limpa a URL para evitar repetição do alerta no refresh
            window.history.replaceState({}, document.title, "produtos.php");
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>