<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/registrar_mercado.php
 * Finalidade: Receber via AJAX o nome de um novo mercado e cadastrar no banco de dados.
 */

header('Content-Type: application/json');
require_once '../core/db.php';

// Recebe os dados via JSON (padrão Fetch API)
$dados_recebidos = json_decode(file_get_contents('php://input'), true);

$nome_do_mercado = trim($dados_recebidos['nome'] ?? '');
$regiao_padrao = "São José dos Campos";

if (empty($nome_do_mercado)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome do mercado não pode estar vazio.']);
    exit;
}

try {
    // Verifica se já existe um mercado com esse nome para evitar duplicidade
    $comando_verificacao = $pdo->prepare("SELECT id FROM mercados WHERE nome = ? LIMIT 1");
    $comando_verificacao->execute([$nome_do_mercado]);
    $mercado_ja_existe = $comando_verificacao->fetch();

    if ($mercado_ja_existe) {
        echo json_encode([
            'sucesso' => true, 
            'id' => $mercado_ja_existe['id'], 
            'nome' => $nome_do_mercado,
            'mensagem' => 'Mercado já existente selecionado.'
        ]);
    } else {
        // Insere o novo mercado
        $comando_insercao = $pdo->prepare("INSERT INTO mercados (nome, regiao) VALUES (?, ?)");
        $comando_insercao->execute([$nome_do_mercado, $regiao_padrao]);
        $novo_identificador = $pdo->lastInsertId();

        echo json_encode([
            'sucesso' => true, 
            'id' => $novo_identificador, 
            'nome' => $nome_do_mercado,
            'mensagem' => 'Novo mercado cadastrado com sucesso!'
        ]);
    }
} catch (PDOException $erro_banco_dados) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao acessar o banco de dados.']);
}