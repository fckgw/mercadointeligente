<?php
/**
 * SISTEMA MERCADO INTELIGENTE
 * Arquivo: cliente/salvar_carrinho.php
 */
session_start();
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/scraper.php';

$conteudo_recebido = file_get_contents('php://input');
$dados_decodificados = json_decode($conteudo_recebido, true);

if (!$dados_decodificados || empty($dados_decodificados['itens'])) {
    echo json_encode(['sucesso' => false]); exit;
}

$identificador_usuario = $_SESSION['usuario_id'] ?? null;
$identificador_mercado = $dados_decodificados['mercado_id'];
$lista_de_itens = $dados_decodificados['itens'];

try {
    $pdo->beginTransaction();
    foreach ($lista_de_itens as $item) {
        $nome_produto = trim($item['nome']);
        $preco_produto = (float)$item['preco'];

        $comando_busca = $pdo->prepare("SELECT id FROM produtos WHERE nome = ? LIMIT 1");
        $comando_busca->execute([$nome_produto]);
        $produto_existente = $comando_busca->fetch();

        if ($produto_existente) {
            $id_produto_final = $produto_existente['id'];
        } else {
            $marca = identificarMarcaPeloNomeDoProduto($nome_produto);
            $categoria = identificarCategoriaPeloNomeDoProduto($nome_produto);
            $comando_insere_produto = $pdo->prepare("INSERT INTO produtos (nome, marca, categoria) VALUES (?, ?, ?)");
            $comando_insere_produto->execute([$nome_produto, $marca, $categoria]);
            $id_produto_final = $pdo->lastInsertId();
        }

        // SALVA COM O ID DO USUÁRIO LOGADO
        $comando_preco = $pdo->prepare("INSERT INTO precos (produto_id, mercado_id, usuario_id, valor_unitario, data_da_coleta) VALUES (?, ?, ?, ?, NOW())");
        $comando_preco->execute([$id_produto_final, $identificador_mercado, $identificador_usuario, $preco_produto]);
    }
    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $erro) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['sucesso' => false]);
}