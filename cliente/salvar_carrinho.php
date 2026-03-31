<?php
/**
 * Arquivo: cliente/salvar_carrinho.php
 */
session_start();
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/scraper.php';

// Impede que erros sujem o JSON de saída
ini_set('display_errors', 0);

$conteudo_recebido = file_get_contents('php://input');
$dados_decodificados = json_decode($conteudo_recebido, true);

if (!$dados_decodificados || empty($dados_decodificados['itens'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Carrinho vazio']);
    exit;
}

$identificador_usuario = $_SESSION['usuario_id'] ?? null;
$id_mercado = $dados_decodificados['mercado_id'];
$itens = $dados_decodificados['itens'];

try {
    $pdo->beginTransaction();

    foreach ($itens as $item) {
        $nome = trim($item['nome']);
        $preco = (float)$item['preco'];
        $unidade = $item['unidade'] ?? 'UN';

        // Verifica se o produto existe
        $stmt = $pdo->prepare("SELECT id FROM produtos WHERE nome = ? LIMIT 1");
        $stmt->execute([$nome]);
        $prod = $stmt->fetch();

        if ($prod) {
            $produto_id = $prod['id'];
        } else {
            $marca = identificarMarcaPeloNomeDoProduto($nome);
            $categoria = identificarCategoriaPeloNomeDoProduto($nome);
            $ins = $pdo->prepare("INSERT INTO produtos (nome, marca, categoria) VALUES (?, ?, ?)");
            $ins->execute([$nome, $marca, $categoria]);
            $produto_id = $pdo->lastInsertId();
        }

        // Salva preço
        $sql_preco = "INSERT INTO precos (produto_id, mercado_id, usuario_id, valor_unitario, unidade_medida, data_da_coleta) VALUES (?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sql_preco)->execute([$produto_id, $id_mercado, $identificador_usuario, $preco, $unidade]);
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}