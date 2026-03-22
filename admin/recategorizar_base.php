<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/recategorizar_base.php
 * Finalidade: Varre a base de produtos e corrige categorias 'Geral' usando a inteligência do Scraper.
 */
require_once 'sessao.php';
require_once '../core/db.php';
require_once '../core/scraper.php';

$produtos_corrigidos = 0;

// Busca todos os produtos que estão marcados como 'Geral'
$comando_busca = $pdo->query("SELECT id, nome FROM produtos WHERE categoria = 'Geral'");
$lista_geral = $comando_busca->fetchAll();

foreach ($lista_geral as $item) {
    // Usa a função do scraper para identificar a categoria correta agora
    $nova_categoria = identificarCategoriaPeloNomeDoProduto($item['nome']);
    $nova_marca = identificarMarcaPeloNomeDoProduto($item['nome']);

    if ($nova_categoria !== 'Geral') {
        $comando_update = $pdo->prepare("UPDATE produtos SET categoria = ?, marca = ? WHERE id = ?");
        $comando_update->execute([$nova_categoria, $nova_marca, $item['id']]);
        $produtos_corrigidos++;
    }
}

header("Location: produtos.php?recategorizado=" . $produtos_corrigidos);
exit;