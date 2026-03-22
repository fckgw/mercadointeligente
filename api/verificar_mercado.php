<?php
/**
 * Arquivo: api/verificar_mercado.php
 * Finalidade: Verificar similaridade de nomes de mercados para evitar duplicados.
 */
header('Content-Type: application/json');
require_once '../core/db.php';

$nome_novo = trim($_GET['nome'] ?? '');
$sugestoes = [];

if (strlen($nome_novo) > 2) {
    $mercados = $pdo->query("SELECT id, nome FROM mercados")->fetchAll();
    
    foreach ($mercados as $mercado) {
        // Calcula a diferença entre os nomes (0 = idêntico)
        $distancia = levenshtein(strtolower($nome_novo), strtolower($mercado['nome']));
        
        // Se a distância for pequena (ex: 3 caracteres de diferença), sugerimos o existente
        if ($distancia <= 3) {
            $sugestoes[] = $mercado;
        }
    }
}

echo json_encode($sugestoes);