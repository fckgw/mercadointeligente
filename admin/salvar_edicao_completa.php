<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/salvar_edicao_completa.php
 * Finalidade: Processar a edição do produto aceitando novos valores manuais de marca/categoria.
 */

require_once 'sessao.php';
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $identificador_produto = $_POST['produto_id'];
    $nome_produto          = trim($_POST['nome']);
    
    // Lógica para Marca (Lista ou Manual)
    if ($_POST['marca_existente'] === 'NOVA') {
        $marca_final = trim($_POST['marca_manual']);
    } else {
        $marca_final = trim($_POST['marca_existente']);
    }

    // Lógica para Categoria (Lista ou Manual)
    if ($_POST['categoria_existente'] === 'NOVA') {
        $categoria_final = trim($_POST['categoria_manual']);
    } else {
        $categoria_final = trim($_POST['categoria_existente']);
    }
    
    // Dados da Cotação Manual
    $mercado_id = $_POST['mercado_id'] ?? '';
    $novo_preco = $_POST['novo_preco'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. Atualiza os dados do produto na base global
        $comando_update = $pdo->prepare("UPDATE produtos SET nome = ?, marca = ?, categoria = ? WHERE id = ?");
        $comando_update->execute([$nome_produto, $marca_final, $categoria_final, $identificador_produto]);

        // 2. Se houver preço e mercado informados, cria novo registro de histórico
        if (!empty($mercado_id) && !empty($novo_preco)) {
            $comando_preco = $pdo->prepare("
                INSERT INTO precos (produto_id, mercado_id, valor_unitario, data_da_coleta) 
                VALUES (?, ?, ?, NOW())
            ");
            $comando_preco->execute([$identificador_produto, $mercado_id, $novo_preco]);
        }

        $pdo->commit();
        header("Location: produtos.php?sucesso_edicao=1");

    } catch (Exception $erro) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Erro Crítico ao Salvar: " . $erro->getMessage());
    }
}