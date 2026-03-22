<?php
require_once 'sessao.php';
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];

    $stmt = $pdo->prepare("UPDATE produtos SET nome = ? WHERE id = ?");
    if ($stmt->execute([$nome, $id])) {
        header("Location: produtos.php?sucesso=1");
    } else {
        echo "Erro ao atualizar.";
    }
}