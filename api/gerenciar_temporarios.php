<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/gerenciar_temporarios.php
 * Finalidade: Persistência inteligente com correção automática de chaves estrangeiras.
 */
header('Content-Type: application/json');
require_once '../core/db.php';
session_start();

$identificador_usuario_sessao = $_SESSION['usuario_id'] ?? null;
if (!$identificador_usuario_sessao) { 
    echo json_encode(['sucesso' => false, 'mensagem_erro' => 'Sessão expirada.']); 
    exit; 
}

$acao_solicitada = $_GET['acao'] ?? '';
$dados_recebidos = json_decode(file_get_contents('php://input'), true);

/**
 * FUNÇÃO DE SEGURANÇA: GARANTE QUE O PRODUTO EXISTA NA TABELA 'produtos'
 */
function assegurarExistenciaDoProdutoEObterId($nome_produto, $pdo) {
    $nome_formatado = trim($nome_produto);
    if(empty($nome_formatado)) return null;

    // 1. Tenta localizar pelo nome exato
    $comando_verificacao = $pdo->prepare("SELECT id FROM produtos WHERE nome = ? LIMIT 1");
    $comando_verificacao->execute([$nome_formatado]);
    $resultado_produto = $comando_verificacao->fetch(PDO::FETCH_ASSOC);

    if ($resultado_produto) {
        return $resultado_produto['id'];
    }

    // 2. Se não existe, cria o produto agora para evitar erro de Chave Estrangeira (FK)
    $comando_cadastro_automatico = $pdo->prepare("INSERT INTO produtos (nome, marca, categoria) VALUES (?, 'Importado', 'Geral')");
    $comando_cadastro_automatico->execute([$nome_formatado]);
    return $pdo->lastInsertId();
}

try {
    switch ($acao_solicitada) {

        case 'adicionar_item_temporario':
            $produto_id_validado = assegurarExistenciaDoProdutoEObterId($dados_recebidos['nome'], $pdo);

            $instrucao_sql = "INSERT INTO compras_temporarias (usuario_id, mercado_id, nome_produto, produto_id, preco, quantidade, unidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $comando_insert = $pdo->prepare($instrucao_sql);
            $comando_insert->execute([
                $identificador_usuario_sessao, 
                $dados_recebidos['mercado_id'], 
                $dados_recebidos['nome'], 
                $produto_id_validado,
                $dados_recebidos['preco'], 
                $dados_recebidos['quantidade'], 
                $dados_recebidos['unidade'], 
                $dados_recebidos['subtotal']
            ]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'listar_itens_temporarios':
            $instrucao_busca = "SELECT id, nome_produto as nome, produto_id, preco, quantidade, unidade, subtotal FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?";
            $comando_lista = $pdo->prepare($instrucao_busca);
            $comando_lista->execute([$identificador_usuario_sessao, $_GET['mercado_id']]);
            echo json_encode(['sucesso' => true, 'lista_itens' => $comando_lista->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'finalizar_e_gravar_oficial':
            $mercado_id_atual = $dados_recebidos['mercado_id'];

            // --- INÍCIO DA VARREDURA DE INTEGRIDADE ---
            // Recupera todos os itens que estão no rascunho agora
            $comando_rascunhos = $pdo->prepare("SELECT id, nome_produto, produto_id FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?");
            $comando_rascunhos->execute([$identificador_usuario_sessao, $mercado_id_atual]);
            $itens_para_validar = $comando_rascunhos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($itens_para_validar as $item) {
                // Verifica se o produto_id gravado realmente existe na tabela de produtos
                $check_existencia = $pdo->prepare("SELECT id FROM produtos WHERE id = ?");
                $check_existencia->execute([$item['produto_id']]);
                
                if (!$check_existencia->fetch()) {
                    // Se o ID for inválido ou nulo, corrigimos na hora usando o nome do produto
                    $id_corrigido = assegurarExistenciaDoProdutoEObterId($item['nome_produto'], $pdo);
                    $pdo->prepare("UPDATE compras_temporarias SET produto_id = ? WHERE id = ?")
                        ->execute([$id_corrigido, $item['id']]);
                }
            }
            // --- FIM DA VARREDURA ---

            // Agora a migração é segura, pois todos os itens possuem um produto_id válido
            $sql_migracao_final = "INSERT INTO precos (produto_id, mercado_id, valor_unitario, data_da_coleta, usuario_id, unidade_medida) 
                                   SELECT produto_id, mercado_id, preco, NOW(), usuario_id, unidade 
                                   FROM compras_temporarias 
                                   WHERE usuario_id = ? AND mercado_id = ?";
            
            $stmt_final = $pdo->prepare($sql_migracao_final);
            $stmt_final->execute([$identificador_usuario_sessao, $mercado_id_atual]);

            // Limpa a tabela temporária
            $pdo->prepare("DELETE FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?")
                ->execute([$identificador_usuario_sessao, $mercado_id_atual]);
            
            echo json_encode(['sucesso' => true]);
            break;

        case 'remover_item_rascunho':
            $pdo->prepare("DELETE FROM compras_temporarias WHERE id = ? AND usuario_id = ?")->execute([$_GET['id'], $identificador_usuario_sessao]);
            echo json_encode(['sucesso' => true]);
            break;
    }
} catch (Exception $excecao_erro) {
    echo json_encode(['sucesso' => false, 'mensagem_erro' => 'Falha no Processamento: ' . $excecao_erro->getMessage()]);
}