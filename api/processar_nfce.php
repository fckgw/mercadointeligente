<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/processar_nfce.php
 * Finalidade: Extração robusta de itens de Notas Fiscais (NFC-e SP).
 */

header('Content-Type: application/json');
require_once '../core/db.php';

$url_da_nota_fiscal = $_POST['url_nfce'] ?? '';

if (empty($url_da_nota_fiscal)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'URL da nota não fornecida.']);
    exit;
}

/**
 * REQUISIÇÃO CURL OTIMIZADA
 */
$sessao_curl = curl_init();
curl_setopt($sessao_curl, CURLOPT_URL, $url_da_nota_fiscal);
curl_setopt($sessao_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($sessao_curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($sessao_curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($sessao_curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
curl_setopt($sessao_curl, CURLOPT_TIMEOUT, 30);

$html_bruto_da_nota = curl_exec($sessao_curl);
curl_close($sessao_curl);

if (!$html_bruto_da_nota) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Falha na conexão com o servidor da Fazenda.']);
    exit;
}

/**
 * PROCESSAMENTO DO HTML (DOM E XPATH)
 */
libxml_use_internal_errors(true);
$documento_dom = new DOMDocument();
// Injeção de Meta Tag para forçar UTF-8 e evitar quebra de valores monetários
@$documento_dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html_bruto_da_nota);
$buscador_xpath = new DOMXPath($documento_dom);

// Localiza as linhas de produtos (Padrão SEFAZ SP)
$linhas_da_nota = $buscador_xpath->query("//table[@id='tabResult']/tr");

$lista_final_de_itens = [];

foreach ($linhas_da_nota as $linha_singular) {
    
    // NOME: Classe txtTit
    $nodo_nome = $buscador_xpath->query(".//span[@class='txtTit']", $linha_singular);
    $nome_do_produto = ($nodo_nome->length > 0) ? trim($nodo_nome->item(0)->nodeValue) : "";

    // VALOR UNITÁRIO: Classe RValUnit
    $nodo_valor = $buscador_xpath->query(".//span[@class='RValUnit']", $linha_singular);
    $valor_texto_original = ($nodo_valor->length > 0) ? trim($nodo_valor->item(0)->nodeValue) : "0";

    // QUANTIDADE: Classe RQty
    $nodo_quantidade = $buscador_xpath->query(".//span[@class='RQty']", $linha_singular);
    $quantidade_texto_original = ($nodo_quantidade->length > 0) ? trim($nodo_quantidade->item(0)->nodeValue) : "1";

    if (!empty($nome_do_produto)) {
        
        /**
         * LIMPEZA PROFUNDA DE CARACTERES
         * Remove "R$", espaços invisíveis, pontos de milhar e acerta a vírgula decimal.
         */
        $valor_limpo = str_replace(['R$', ' ', "\xc2\xa0", "\xa0"], '', $valor_texto_original);
        $valor_limpo = str_replace('.', '', $valor_limpo); // Remove ponto de milhar
        $valor_limpo = str_replace(',', '.', $valor_limpo); // Troca vírgula por ponto
        $valor_unitario_decimal = floatval(preg_replace("/[^0-9.]/", "", $valor_limpo));

        $quantidade_limpa = str_replace(',', '.', trim($quantidade_texto_original));
        $quantidade_decimal = floatval(preg_replace("/[^0-9.]/", "", $quantidade_limpa));

        $lista_final_de_itens[] = [
            'nome'       => $nome_do_produto,
            'valor'      => $valor_unitario_decimal,
            'quantidade' => ($quantidade_decimal > 0) ? $quantidade_decimal : 1,
            'unidade'    => 'UN' // A SEFAZ tem classes variadas, padronizamos para UN no retorno inicial
        ];
    }
}

echo json_encode(['sucesso' => true, 'itens' => $lista_final_de_itens]);