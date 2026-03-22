<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/salvar_edicao_completa.php
 * Finalidade: Atualizar dados do produto e inserir nova cotação de histórico.
 */

require_once 'sessao.php';
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_produto = $_POST['produto_id'];
    $nome       = trim($_POST['nome']);
    $marca      = trim($_POST['marca']);
    $categoria  = trim($_POST['categoria']);
    
    $mercado_id = $_POST['mercado_id'] ?? '';
    $novo_preco = $_POST['novo_preco'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. ATUALIZA DADOS BÁSICOS DO PRODUTO
        $comando_update = $pdo->prepare("UPDATE produtos SET nome = ?, marca = ?, categoria = ? WHERE id = ?");
        $comando_update->execute([$nome, $marca, $categoria, $id_produto]);

        // 2. SE INFORMOU PREÇO E MERCADO, LANÇA NO HISTÓRICO
        if (!empty($mercado_id) && !empty($novo_preco)) {
            $comando_historico = $pdo->prepare("
                INSERT INTO precos (produto_id, mercado_id, valor_unitario, data_da_coleta) 
                VALUES (?, ?, ?, NOW())
            ");
            $comando_historico->execute([$id_produto, $mercado_id, $novo_preco]);
        }

        $pdo->commit();
        header("Location: produtos.php?sucesso_edit=1");

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao salvar: " . $e->getMessage());
    }
}