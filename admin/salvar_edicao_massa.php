<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/salvar_edicao_massa.php
 * Finalidade: Processar a atualização de categoria para múltiplos produtos simultaneamente.
 */

// Controle de segurança e conexão
require_once 'sessao.php';
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura a string de IDs vinda do formulário (Ex: "12,15,18,20")
    $string_de_identificadores = $_POST['ids_dos_produtos'] ?? '';
    
    // Lógica para decidir qual categoria será utilizada
    $nome_da_categoria_final = "";
    
    if ($_POST['categoria_existente'] === 'ADICIONAR_NOVA') {
        // Se escolheu adicionar nova, pega o valor do campo de texto manual
        $nome_da_categoria_final = trim($_POST['categoria_nova_manual']);
    } else {
        // Caso contrário, pega o valor selecionado no DropDown
        $nome_da_categoria_final = trim($_POST['categoria_existente']);
    }

    // Validação dos dados necessários
    if (!empty($string_de_identificadores) && !empty($nome_da_categoria_final)) {
        
        // Converte a string separada por vírgulas em um array
        $lista_de_ids_array = explode(',', $string_de_identificadores);
        
        // Gera os placeholders (?, ?, ?) para a query SQL de forma dinâmica
        $marcadores_sql = implode(',', array_fill(0, count($lista_de_ids_array), '?'));

        try {
            /**
             * EXECUÇÃO DA ATUALIZAÇÃO
             * Atualiza a categoria de todos os IDs contidos na seleção.
             */
            $instrucao_update = "UPDATE produtos SET categoria = ? WHERE id IN ($marcadores_sql)";
            $comando_pdo = $pdo->prepare($instrucao_update);
            
            // Mescla o nome da categoria com a lista de IDs para preencher os placeholders
            $parametros_finais = array_merge([$nome_da_categoria_final], $lista_de_ids_array);
            
            $comando_pdo->execute($parametros_finais);
            
            // Captura o número de linhas que foram realmente alteradas no banco
            $quantidade_de_registros_alterados = $comando_pdo->rowCount();

            /**
             * REDIRECIONAMENTO
             * Retorna para a página de produtos enviando o total para o SweetAlert2.
             */
            header("Location: produtos.php?sucesso_massa=" . $quantidade_de_registros_alterados);
            exit;
            
        } catch (PDOException $erro_de_banco) {
            // Em caso de erro técnico, interrompe e exibe a mensagem
            die("Erro Crítico ao processar atualização em massa: " . $erro_de_banco->getMessage());
        }

    } else {
        // Caso falte algum dado, retorna para a lista sem processar
        header("Location: produtos.php?erro=dados_insuficientes");
        exit;
    }
} else {
    // Bloqueia acesso direto via URL a este arquivo
    header("Location: produtos.php");
    exit;
}