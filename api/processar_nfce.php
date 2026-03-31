<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/processar_nfce.php
 */
header('Content-Type: application/json');
require_once '../core/db.php';
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;
$dados_post = json_decode(file_get_contents('php://input'), true);

$url_nota = $dados_post['url'] ?? '';
$mercado_id = (int)($dados_post['mercado_id'] ?? 0);

if (!$url_nota || $mercado_id === 0 || !$usuario_id) {
    echo json_encode(['sucesso' => false, 'mensagem_erro' => 'Dados inválidos ou mercado não selecionado.']);
    exit;
}

try {
    // --- SIMULAÇÃO DE DADOS DA NOTA (SCRAPING REAL IRIA AQUI) ---
    $itens_da_nota = [
        ['nome' => 'Pão Frances', 'preco' => 12.99, 'qtd' => 0.400, 'un' => 'KG'],
        ['nome' => 'Leite Integral Carrefour 1L', 'preco' => 5.49, 'qtd' => 2, 'un' => 'UN']
    ];

    foreach ($itens_da_nota as $item) {
        $nome_item = $item['nome'];
        
        // Tenta encontrar o ID do produto pelo nome
        $stmt_busca = $pdo->prepare("SELECT id FROM produtos WHERE nome LIKE ? LIMIT 1");
        $stmt_busca->execute(["%$nome_item%"]);
        $produto_encontrado = $stmt_busca->fetch(PDO::FETCH_ASSOC);
        
        $produto_id = $produto_encontrado ? $produto_encontrado['id'] : null;

        // Se não achar o produto_id, o gerenciar_temporarios.php vai criar um novo ao salvar.
        $stmt_insere = $pdo->prepare("INSERT INTO compras_temporarias (usuario_id, mercado_id, nome_produto, produto_id, preco, quantidade, unidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insere->execute([
            $usuario_id, 
            $mercado_id, 
            $nome_item, 
            $produto_id, 
            $item['preco'], 
            $item['qtd'], 
            $item['un'], 
            ($item['preco'] * $item['qtd'])
        ]);
    }

    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem_erro' => $e->getMessage()]);
}