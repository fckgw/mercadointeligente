<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/buscar_produtos.php
 * Finalidade: Retornar produtos da base local via JSON para o preenchimento automático.
 */

header('Content-Type: application/json');
require_once '../core/db.php';

$termo_da_pesquisa = $_GET['termo'] ?? '';

// Só pesquisa se o usuário digitou pelo menos 2 caracteres
if (strlen($termo_da_pesquisa) >= 2) {
    /**
     * Busca o nome do produto ou a marca na base local.
     * Limitamos a 6 resultados para não poluir a tela do celular.
     */
    $instrucao_sql = "SELECT nome, marca, categoria FROM produtos 
                      WHERE nome LIKE ? OR marca LIKE ? 
                      ORDER BY nome ASC LIMIT 6";
    
    $comando_preparado = $pdo->prepare($instrucao_sql);
    $comando_preparado->execute(["%$termo_da_pesquisa%", "%$termo_da_pesquisa%"]);
    $lista_de_resultados = $comando_preparado->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($lista_de_resultados);
} else {
    echo json_encode([]);
}