<?php
/**
 * Arquivo: api/buscar_produtos.php
 */
header('Content-Type: application/json');
require_once '../core/db.php';

$termo_pesquisado = $_GET['term'] ?? '';

if (strlen($termo_pesquisado) >= 2) {
    // Busca aproximada para evitar erros de digitação (LIKE)
    $comando_sql = "SELECT nome, marca, categoria FROM produtos 
                    WHERE nome LIKE ? OR marca LIKE ? 
                    GROUP BY nome LIMIT 6";
    $stmt = $pdo->prepare($comando_sql);
    $stmt->execute(["%$termo_pesquisado%", "%$termo_pesquisado%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados);
} else {
    echo json_encode([]);
}