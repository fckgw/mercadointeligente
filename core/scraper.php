<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: core/scraper.php
 * Finalidade: Extrair dados de produtos sem abreviações e com inteligência de categoria.
 */

function identificarMarcaPeloNomeDoProduto($nome_completo_do_produto) {
    $lista_de_marcas_conhecidas = [
        'Camil', 'Tio João', 'Fantastico', 'Prato Fino', 'Broto Legal', 'Solito', 'Namorado', 
        'Omo', 'Brilhante', 'Ariel', 'Ipê', 'Nestlé', 'Garoto', 'Lacta', 'Oreo', 'Qualitá', 
        'Carrefour', 'Sadia', 'Perdigão', 'Seara', 'Coca-Cola', 'Pepsi', 'Pilão', 'Melitta',
        '3 Corações', 'Toddy', 'Nescau', 'Qualy', 'Vigor', 'Swift', 'Friboi', 'Maturatta', 'Ouro Branco', 'Bis'
    ];

    foreach ($lista_de_marcas_conhecidas as $marca_para_verificacao) {
        if (stripos($nome_completo_do_produto, $marca_para_verificacao) !== false) {
            return $marca_para_verificacao;
        }
    }
    return "Outras Marcas";
}

function identificarCategoriaPeloNomeDoProduto($nome_completo_do_produto) {
    $regras_de_classificacao = [
        'Arroz'     => ['arroz'],
        'Feijão'    => ['feijão', 'feijao'],
        'Carnes'    => ['carne', 'picanha', 'alcatra', 'contra', 'maminha', 'fraldinha', 'acém', 'acem', 'patinho', 'músculo', 'cupim', 'swift', 'friboi', 'bovino', 'suíno', 'porco', 'frango', 'coxa', 'peito'],
        'Chocolate' => ['chocolate', 'bis', 'lacta', 'nestlé', 'nestle', 'garoto', 'oreo', 'bombom', 'barra', 'ouro branco', 'trakinas', 'hershey', 'caixa de bombom', 'ovo de páscoa'],
        'Bebidas'   => ['coca-cola', 'refrigerante', 'suco', 'cerveja', 'vinho', 'vodka', 'água', 'agua', 'energético', 'fanta', 'sprite'],
        'Limpeza'   => ['detergente', 'sabão', 'sabao', 'amaciante', 'desinfetante', 'veja', 'limpador', 'cloro', 'água sanitária']
    ];

    foreach ($regras_de_classificacao as $categoria_nome => $lista_de_palavras_chave) {
        foreach ($lista_de_palavras_chave as $palavra_chave) {
            if (stripos($nome_completo_do_produto, $palavra_chave) !== false) {
                return $categoria_nome;
            }
        }
    }
    return "Geral";
}

function extrairProdutosDaPaginaHtml($conteudo_html_bruto) {
    $lista_final_de_produtos = [];
    if (empty($conteudo_html_bruto)) {
        return $lista_final_de_produtos;
    }

    libxml_use_internal_errors(true);
    $documento_dom = new DOMDocument();
    
    // Injeta a meta tag para garantir leitura UTF-8 no PHP 8.x
    $html_preparado = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $conteudo_html_bruto;
    @$documento_dom->loadHTML($html_preparado);
    $buscador_xpath = new DOMXPath($documento_dom);

    // Seletores específicos (Padrão Carrefour)
    $blocos_de_produtos = $buscador_xpath->query("//a[@data-testid='search-product-card']");

    // Fallback caso não encontre pelo padrão Carrefour
    if ($blocos_de_produtos->length === 0) {
        $blocos_de_produtos = $buscador_xpath->query("//div[contains(@class,'product')] | //li[contains(@class,'item')]");
    }

    foreach ($blocos_de_produtos as $elemento_do_produto) {
        
        // Extração do Nome
        $no_do_nome = $buscador_xpath->query(".//h2", $elemento_do_produto);
        $nome_extraido = ($no_do_nome->length > 0) ? trim($no_do_nome->item(0)->nodeValue) : "";

        // Extração do Preço
        $no_do_preco = $buscador_xpath->query(".//span[contains(@class, 'text-price-default')] | .//*[contains(@class, 'price')] | .//*[contains(@class, 'preco')]", $elemento_do_produto);
        $preco_texto_bruto = ($no_do_preco->length > 0) ? trim($no_do_preco->item(0)->nodeValue) : "";

        if (!empty($nome_extraido) && !empty($preco_texto_bruto)) {
            
            // Limpeza de preço para formato decimal (banco de dados)
            $preco_limpo = str_replace(['R$', ' ', '.'], '', $preco_texto_bruto);
            $preco_limpo = str_replace(',', '.', $preco_limpo);
            $valor_decimal_final = floatval(preg_replace("/[^0-9.]/", "", $preco_limpo));

            if ($valor_decimal_final > 0) {
                $lista_final_de_produtos[] = [
                    'nome'      => $nome_extraido,
                    'preco'     => $valor_decimal_final,
                    'marca'     => identificarMarcaPeloNomeDoProduto($nome_extraido),
                    'categoria' => identificarCategoriaPeloNomeDoProduto($nome_extraido)
                ];
            }
        }
    }

    libxml_clear_errors();
    return $lista_final_de_produtos;
}