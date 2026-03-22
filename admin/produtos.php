<?php 
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/produtos.php
 * Finalidade: Gestão avançada da base de produtos, organização de categorias e BI.
 */

// Configurações de exibição de erros para o ambiente Locaweb
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Controle de acesso e conexão com a base de dados
require_once 'sessao.php';
require_once '../core/db.php';

/**
 * CAPTURA DE FILTROS DA URL
 */
$termo_da_pesquisa = $_GET['q'] ?? '';
$categoria_selecionada_no_filtro = $_GET['cat'] ?? '';

/**
 * CONSULTAS PARA ALIMENTAR OS COMPONENTES DA TELA
 */

// 1. Recupera categorias únicas para o filtro superior e modais
$consulta_categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos WHERE categoria != '' ORDER BY categoria ASC");
$lista_de_categorias_existentes = $consulta_categorias->fetchAll(PDO::FETCH_COLUMN);

// 2. Recupera marcas únicas para o modal de edição
$consulta_marcas = $pdo->query("SELECT DISTINCT marca FROM produtos WHERE marca != '' ORDER BY marca ASC");
$lista_de_marcas_existentes = $consulta_marcas->fetchAll(PDO::FETCH_COLUMN);

// 3. Recupera mercados para permitir lançamento manual de preços
$consulta_mercados = $pdo->query("SELECT id, nome, regiao FROM mercados ORDER BY nome ASC");
$lista_de_mercados_disponiveis = $consulta_mercados->fetchAll(PDO::FETCH_ASSOC);

/**
 * CONSULTA PRINCIPAL: LISTAGEM DE PRODUTOS
 * Busca o produto e a cotação de preço mais recente vinculada a ele (independente do mercado)
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

$parametros_da_consulta = [];

if (!empty($termo_da_pesquisa)) {
    $instrucao_sql_principal .= " AND (produtos.nome LIKE ? OR produtos.marca LIKE ?)";
    $parametros_da_consulta[] = "%$termo_da_pesquisa%";
    $parametros_da_consulta[] = "%$termo_da_pesquisa%";
}

if (!empty($categoria_selecionada_no_filtro)) {
    $instrucao_sql_principal .= " AND produtos.categoria = ?";
    $parametros_da_consulta[] = $categoria_selecionada_no_filtro;
}

$instrucao_sql_principal .= " ORDER BY produtos.nome ASC";

$comando_preparado = $pdo->prepare($instrucao_sql_principal);
$comando_preparado->execute($parametros_da_consulta);
$lista_de_produtos_exibicao = $comando_preparado->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Produtos - Mercado Inteligente</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f4f7f6; padding-bottom: 100px; }
        .cabecalho-estatico { background: #fff; border-bottom: 1px solid #dee2e6; position: sticky; top: 0; z-index: 1000; }
        .cartao-produto { border-radius: 15px; border: 2px solid transparent; transition: 0.3s; background: #fff; cursor: pointer; position: relative; }
        .cartao-produto.selecionado { border-color: #0d6efd; background-color: #f0f7ff; }
        .botao-editar-individual { position: absolute; top: 10px; right: 10px; border-radius: 50%; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; z-index: 10; }
        
        /* Barra de Ações em Massa (Rodapé) */
        #barra-acoes-massa {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            width: 92%; max-width: 550px; background: #1a1a1a; color: white;
            padding: 15px 25px; border-radius: 50px; display: none;
            justify-content: space-between; align-items: center; z-index: 2000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        
        .check-selecao { width: 25px; height: 25px; cursor: pointer; }
    </style>
</head>
<body>

<!-- CABEÇALHO E FILTROS -->
<div class="cabecalho-estatico p-3 shadow-sm mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="dashboard.php" class="btn btn-sm btn-light border text-muted"><i class="bi bi-arrow-left"></i> Painel</a>
            <img src="../images/Logo_MercadoInteligente.png" style="max-height: 40px;">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="marcarTodosProdutos" onclick="funcaoSelecionarTudo(this)">
                <label class="small fw-bold">Tudo</label>
            </div>
        </div>

        <form action="" method="GET" class="row g-2">
            <div class="col-12 col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Produto ou marca..." value="<?php echo htmlspecialchars($termo_da_pesquisa); ?>">
            </div>
            <div class="col-9 col-md-5">
                <select name="cat" class="form-select">
                    <option value="">Todas as Categorias</option>
                    <?php foreach($lista_de_categorias_existentes as $categoria_item): ?>
                        <option value="<?php echo $categoria_item; ?>" <?php echo ($categoria_selecionada_no_filtro == $categoria_item) ? 'selected' : ''; ?>>
                            <?php echo $categoria_item; ?>
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
<div id="barra-acoes-massa">
    <span id="texto-itens-selecionados" class="small fw-bold">0 selecionados</span>
    <button class="btn btn-warning btn-sm fw-bold rounded-pill px-4" onclick="abrirModalEdicaoEmMassa()">
        <i class="bi bi-tags-fill"></i> Categorizar em Massa
    </button>
</div>

<!-- LISTAGEM DE PRODUTOS EM CARDS -->
<div class="container">
    <div class="row g-3">
        <?php foreach ($lista_de_produtos_exibicao as $produto_individual): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card cartao-produto p-3 h-100 shadow-sm" id="container_produto_<?php echo $produto_individual['id']; ?>" onclick="funcaoMarcarCard(<?php echo $produto_individual['id']; ?>)">
                    
                    <div class="d-flex justify-content-between align-items-start">
                        <input type="checkbox" name="produtos_selecionados[]" value="<?php echo $produto_individual['id']; ?>" class="form-check-input check-selecao" id="checkbox_id_<?php echo $produto_individual['id']; ?>" onclick="event.stopPropagation(); atualizarEstadoDaInterface();">
                        
                        <!-- BOTÃO EDITAR INDIVIDUAL -->
                        <button class="btn btn-light btn-sm border shadow-sm botao-editar-individual" onclick="event.stopPropagation(); abrirModalEdicaoIndividual(<?php echo htmlspecialchars(json_encode($produto_individual)); ?>)">
                            <i class="bi bi-pencil-fill text-primary"></i>
                        </button>
                    </div>

                    <div class="mt-2">
                        <span class="badge bg-primary-subtle text-primary text-uppercase mb-1" style="font-size: 0.65rem;"><?php echo $produto_individual['marca']; ?></span>
                        <h6 class="fw-bold text-dark mb-1 lh-sm"><?php echo $produto_individual['nome']; ?></h6>
                        <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.75rem;"><?php echo $produto_individual['categoria']; ?></span>
                    </div>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-success fw-bold mb-0">R$ <?php echo number_format($produto_individual['valor_unitario'], 2, ',', '.'); ?></h4>
                            <small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-shop"></i> <?php echo $produto_individual['mercado_nome'] ?: 'Sem cotação'; ?></small>
                        </div>
                        <a href="historico_produto.php?id=<?php echo $produto_individual['id']; ?>" class="btn btn-sm btn-outline-dark" title="Ver Variação BI">
                            <i class="bi bi-graph-up"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL DE EDIÇÃO INDIVIDUAL (HÍBRIDO) -->
<div class="modal fade" id="modalEdicaoIndividual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="salvar_edicao_completa.php" method="POST" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Ajustar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="produto_id" id="campo_id_individual">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">DESCRIÇÃO DO PRODUTO</label>
                    <input type="text" name="nome" id="campo_nome_individual" class="form-control" required>
                </div>

                <div class="row">
                    <!-- MARCA (LISTA OU NOVO) -->
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-muted">MARCA</label>
                        <select name="marca_existente" id="select_marca_individual" class="form-select" onchange="alternarEntradaManual(this.value, 'container_marca_nova')">
                            <option value="">Selecione...</option>
                            <?php foreach($lista_de_marcas_existentes as $marca_singular) echo "<option value='$marca_singular'>$marca_singular</option>"; ?>
                            <option value="CADASTRAR_NOVA" class="text-primary fw-bold">+ Adicionar Nova</option>
                        </select>
                        <div id="container_marca_nova" style="display:none;" class="mt-2">
                            <input type="text" name="marca_manual" class="form-control form-control-sm border-primary" placeholder="Nome da Marca">
                        </div>
                    </div>

                    <!-- CATEGORIA (LISTA OU NOVO) -->
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-muted">CATEGORIA</label>
                        <select name="categoria_existente" id="select_categoria_individual" class="form-select" onchange="alternarEntradaManual(this.value, 'container_categoria_nova')">
                            <option value="">Selecione...</option>
                            <?php foreach($lista_de_categorias_existentes as $categoria_singular) echo "<option value='$categoria_singular'>$categoria_singular</option>"; ?>
                            <option value="CADASTRAR_NOVA" class="text-primary fw-bold">+ Adicionar Nova</option>
                        </select>
                        <div id="container_categoria_nova" style="display:none;" class="mt-2">
                            <input type="text" name="categoria_manual" class="form-control form-control-sm border-primary" placeholder="Nome da Categoria">
                        </div>
                    </div>
                </div>

                <!-- LANÇAMENTO BI -->
                <div class="p-3 bg-light rounded-4 border">
                    <label class="form-label small fw-bold text-primary"><i class="bi bi-lightning-fill"></i> ATUALIZAR PREÇO AGORA (BI)</label>
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <select name="mercado_id" class="form-select">
                                <option value="">Onde você viu este preço?</option>
                                <?php foreach($lista_de_mercados_disponiveis as $mercado_op): ?>
                                    <option value="<?php echo $mercado_op['id']; ?>"><?php echo $mercado_op['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text bg-white">R$</span>
                                <input type="number" name="novo_preco" step="0.01" class="form-control" placeholder="0,00">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 shadow mt-4 py-3 fw-bold">SALVAR ALTERAÇÕES</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDIÇÃO EM MASSA -->
<div class="modal fade" id="modalEdicaoEmMassa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="salvar_edicao_massa.php" method="POST" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Categorização em Massa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="ids_dos_produtos" id="input_ids_selecionados_massa">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Escolha a Categoria para os Selecionados</label>
                    <select name="categoria_existente" class="form-select form-select-lg" onchange="alternarEntradaManual(this.value, 'container_categoria_massa_nova')" required>
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach($lista_de_categorias_existentes as $cat_modal): ?>
                            <option value="<?php echo $cat_modal; ?>"><?php echo $cat_modal; ?></option>
                        <?php endforeach; ?>
                        <option value="CADASTRAR_NOVA" class="text-primary fw-bold">+ Criar Nova Categoria</option>
                    </select>
                </div>

                <div id="container_categoria_massa_nova" style="display:none;" class="p-3 bg-light rounded-3 mb-3 border">
                    <label class="form-label small fw-bold">Nome da Nova Categoria</label>
                    <input type="text" name="categoria_nova_manual" class="form-control" placeholder="Ex: Higiene Pessoal">
                </div>

                <button type="submit" class="btn btn-warning btn-lg w-100 shadow fw-bold py-3 mt-2">
                    APLICAR EM TODOS OS ITENS
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/**
 * FUNÇÕES DE INTERAÇÃO COM A INTERFACE
 */

function alternarEntradaManual(valor_selecionado, id_do_container) {
    const container = document.getElementById(id_do_container);
    const input_interno = container.querySelector('input');
    
    if (valor_selecionado === 'CADASTRAR_NOVA') {
        container.style.display = 'block';
        input_interno.required = true;
        input_interno.focus();
    } else {
        container.style.display = 'none';
        input_interno.required = false;
    }
}

function abrirModalEdicaoIndividual(objeto_produto) {
    document.getElementById('campo_id_individual').value = objeto_produto.id;
    document.getElementById('campo_nome_individual').value = objeto_produto.nome;
    
    // Configura Marca
    document.getElementById('select_marca_individual').value = objeto_produto.marca;
    document.getElementById('container_marca_nova').style.display = 'none';

    // Configura Categoria
    document.getElementById('select_categoria_individual').value = objeto_produto.categoria;
    document.getElementById('container_categoria_nova').style.display = 'none';
    
    new bootstrap.Modal(document.getElementById('modalEdicaoIndividual')).show();
}

function funcaoMarcarCard(identificador_produto) {
    const checkbox = document.getElementById('checkbox_id_' + identificador_produto);
    checkbox.checked = !checkbox.checked;
    atualizarEstadoDaInterface();
}

function atualizarEstadoDaInterface() {
    const elementos_checados = document.querySelectorAll('input[name="produtos_selecionados[]"]:checked');
    const barra_acoes = document.getElementById('barra-acoes-massa');
    const label_contador = document.getElementById('texto-itens-selecionados');
    
    // Reseta destaque de todos os cards
    document.querySelectorAll('.cartao-produto').forEach(card => card.classList.remove('selecionado'));
    
    // Aplica destaque nos selecionados
    elementos_checados.forEach(item => {
        document.getElementById('container_produto_' + item.value).classList.add('selecionado');
    });

    if (elementos_checados.length > 0) {
        barra_acoes.style.display = 'flex';
        label_contador.innerText = elementos_checados.length + ' produtos selecionados';
    } else {
        barra_acoes.style.display = 'none';
    }
}

function funcaoSelecionarTudo(fonte_do_evento) {
    const todos_os_checks = document.getElementsByName('produtos_selecionados[]');
    for (let i = 0; i < todos_os_checks.length; i++) {
        todos_os_checks[i].checked = fonte_do_evento.checked;
    }
    atualizarEstadoDaInterface();
}

function abrirModalMassa() {
    const selecionados = document.querySelectorAll('input[name="produtos_selecionados[]"]:checked');
    let lista_de_ids = [];
    selecionados.forEach(item => lista_de_ids.push(item.value));
    
    document.getElementById('input_ids_selecionados_massa').value = lista_de_ids.join(',');
    new bootstrap.Modal(document.getElementById('modalEdicaoEmMassa')).show();
}

/**
 * FEEDBACK DE SUCESSO (SWEETALERT2)
 */
document.addEventListener('DOMContentLoaded', function() {
    const parametros_url = new URLSearchParams(window.location.search);
    if (parametros_url.get('sucesso_massa')) {
        Swal.fire({
            title: 'Sucesso!',
            text: parametros_url.get('sucesso_massa') + ' itens foram categorizados.',
            icon: 'success',
            confirmButtonColor: '#0d6efd'
        }).then(() => window.history.replaceState({}, document.title, "produtos.php"));
    }
    if (parametros_url.get('sucesso_edicao')) {
        Swal.fire({ title: 'Atualizado!', text: 'Produto e histórico salvos.', icon: 'success', confirmButtonColor: '#0d6efd' })
        .then(() => window.history.replaceState({}, document.title, "produtos.php"));
    }
});
</script>

</body>
</html>