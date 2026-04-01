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

$url_nota_fiscal = $dados_post['url'] ?? '';
$identificador_mercado = (int)($dados_post['mercado_id'] ?? 0);

if (!$url_nota_fiscal || $identificador_mercado === 0 || !$usuario_id) {
    echo json_encode(['sucesso' => false, 'mensagem_erro' => 'Dados incompletos.']);
    exit;
}

try {
    $contexto = stream_context_create([
        "http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/110.0.0.0 Safari/537.36\r\n"]
    ]);
    
    $html_conteudo = @file_get_contents($url_nota_fiscal, false, $contexto);
    
    if (!$html_conteudo) {
        throw new Exception("Não foi possível acessar o portal da Nota Fiscal.");
    }

    // EXTRAÇÃO DOS NOMES
    preg_match_all('/<span class="txtTit">(.*?)<\/span>/i', $html_conteudo, $nomes_produtos);
    
    // EXTRAÇÃO DOS VALORES (Tenta Vl. Unit. ou classes de valor)
    preg_match_all('/Vl. Unit.:.*?<span class="valor">(.*?)<\/span>/is', $html_conteudo, $valores_produtos);
    
    // EXTRAÇÃO DAS QTDs
    preg_match_all('/Qtde.:.*?<span class="valor">(.*?)<\/span>/is', $html_conteudo, $quantidades_produtos);

    if (empty($nomes_produtos[1])) {
        throw new Exception("Nenhum item identificado na URL informada.");
    }

    foreach ($nomes_produtos[1] as $indice => $nome_bruto) {
        $nome_final = trim(strip_tags($nome_bruto));
        
        // Limpeza do valor: substitui vírgula por ponto
        $valor_raw = isset($valores_produtos[1][$indice]) ? trim($valores_produtos[1][$indice]) : '0';
        $valor_unitario = (float)str_replace(['.', ','], ['', '.'], $valor_raw);
        
        $qtd_raw = isset($quantidades_produtos[1][$indice]) ? trim($quantidades_produtos[1][$indice]) : '1';
        $quantidade = (float)str_replace(['.', ','], ['', '.'], $qtd_raw);
        
        $subtotal = $valor_unitario * $quantidade;

        // Tenta encontrar o produto_id na base
        $stmt_busca = $pdo->prepare("SELECT id FROM produtos WHERE nome LIKE ? LIMIT 1");
        $stmt_busca->execute(["%$nome_final%"]);
        $produto_encontrado = $stmt_busca->fetch(PDO::FETCH_ASSOC);
        $produto_id = $produto_encontrado ? $produto_encontrado['id'] : null;

        $stmt_insert = $pdo->prepare("INSERT INTO compras_temporarias (usuario_id, mercado_id, nome_produto, produto_id, preco, quantidade, unidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$usuario_id, $identificador_mercado, $nome_final, $produto_id, $valor_unitario, $quantidade, 'UN', $subtotal]);
    }

    echo json_encode(['sucesso' => true]);

} catch (Exception $excecao) {
    echo json_encode(['sucesso' => false, 'mensagem_erro' => $excecao->getMessage()]);
}