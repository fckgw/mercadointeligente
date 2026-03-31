<?php
require_once '../core/db.php';
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) { echo json_encode(['sucesso' => false]); exit; }

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'add':
        $sql = "INSERT INTO compras_temporarias (usuario_id, mercado_id, nome_produto, preco, quantidade, unidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $data['mercado_id'], $data['nome'], $data['preco'], $data['quantidade'], $data['unidade'], $data['subtotal']]);
        echo json_encode(['sucesso' => true]);
        break;

    case 'list':
        $mercado_id = $_GET['mercado_id'];
        $sql = "SELECT nome_produto as nome, preco, quantidade, unidade, subtotal FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $mercado_id]);
        echo json_encode(['sucesso' => true, 'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'sync':
        foreach ($data as $item) {
            $sql = "INSERT INTO compras_temporarias (usuario_id, mercado_id, nome_produto, preco, quantidade, unidade, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$usuario_id, $item['mercado_id'], $item['nome'], $item['preco'], $item['quantidade'], $item['unidade'], $item['subtotal']]);
        }
        echo json_encode(['sucesso' => true]);
        break;

    case 'delete_single':
        $pdo->prepare("DELETE FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ? AND nome_produto = ? LIMIT 1")
            ->execute([$usuario_id, $_GET['mercado_id'], $_GET['nome']]);
        break;

    case 'finalize':
        $mercado_id = $data['mercado_id'];
        // 1. Move para a tabela principal (Ex: compras_historico)
        $pdo->prepare("INSERT INTO compras (usuario_id, mercado_id, nome_produto, preco, quantidade, unidade, subtotal, data_compra) 
                       SELECT usuario_id, mercado_id, nome_produto, preco, quantidade, unidade, subtotal, NOW() 
                       FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?")
            ->execute([$usuario_id, $mercado_id]);

        // 2. Limpa temporária
        $pdo->prepare("DELETE FROM compras_temporarias WHERE usuario_id = ? AND mercado_id = ?")
            ->execute([$usuario_id, $mercado_id]);
            
        echo json_encode(['sucesso' => true]);
        break;
}