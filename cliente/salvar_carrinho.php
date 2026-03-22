<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: cliente/salvar_carrinho.php
 * Finalidade: Receber os itens do simulador e alimentar a base de dados (Produtos e Preços).
 */

// Define que a resposta será sempre JSON (evita o travamento do pop-up)
header('Content-Type: application/json');

// Desativa a exibição de erros textuais para não quebrar o JSON, mas registra no log
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../core/db.php';
require_once '../core/scraper.php'; // Contém identificarMarcaPeloNomeDoProduto e identificarCategoriaPeloNomeDoProduto

// Captura o corpo da requisição JSON
$conteudo_recebido = file_get_contents('php://input');
$dados_decodificados = json_decode($conteudo_recebido, true);

// Validação inicial
if (!$dados_decodificados || empty($dados_decodificados['itens'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados do carrinho não recebidos.']);
    exit;
}

$identificador_mercado = $dados_decodificados['mercado_id'];
$lista_de_itens = $dados_decodificados['itens'];

try {
    // Inicia uma transação para garantir que ou salva tudo, ou não salva nada
    $pdo->beginTransaction();

    foreach ($lista_de_itens as $item_carrinho) {
        $nome_produto = trim($item_carrinho['nome']);
        $valor_venda = (float)$item_carrinho['preco'];

        /**
         * PASSO 1: VERIFICAÇÃO DO PRODUTO
         * Se o produto não existir na base global, cadastramos usando a IA do Scraper
         */
        $comando_verificar = $pdo->prepare("SELECT id FROM produtos WHERE nome = ? LIMIT 1");
        $comando_verificar->execute([$nome_produto]);
        $produto_encontrado = $comando_verificar->fetch();

        if ($produto_encontrado) {
            $id_do_produto_final = $produto_encontrado['id'];
        } else {
            // Inteligência Artificial do Scraper em ação
            $marca_identificada = identificarMarcaPeloNomeDoProduto($nome_produto);
            $categoria_identificada = identificarCategoriaPeloNomeDoProduto($nome_produto);

            $comando_cadastrar_produto = $pdo->prepare("INSERT INTO produtos (nome, marca, categoria) VALUES (?, ?, ?)");
            $comando_cadastrar_produto->execute([$nome_produto, $marca_identificada, $categoria_identificada]);
            $id_do_produto_final = $pdo->lastInsertId();
        }

        /**
         * PASSO 2: REGISTRO DA COTAÇÃO (BI)
         * Insere na tabela de preços vinculando ao mercado e data atual.
         * Utilizamos os nomes exatos das novas colunas: valor_unitario e data_da_coleta.
         */
        $comando_salvar_preco = $pdo->prepare("
            INSERT INTO precos (produto_id, mercado_id, valor_unitario, data_da_coleta) 
            VALUES (?, ?, ?, NOW())
        ");
        $comando_salvar_preco->execute([
            $id_do_produto_final, 
            $identificador_mercado, 
            $valor_venda
        ]);
    }

    // Confirma todas as operações no banco de dados
    $pdo->commit();

    // Retorna sucesso para o JavaScript fechar o Pop-up
    echo json_encode(['sucesso' => true]);

} catch (Exception $erro_processamento) {
    // Caso ocorra qualquer erro, desfaz as alterações
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => 'Erro interno ao salvar: ' . $erro_processamento->getMessage()
    ]);
}