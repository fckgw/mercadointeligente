<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: api/google_busca.php
 * Finalidade: Consultar a Inteligência do Google para sugerir nomes de produtos.
 */

// Define que o retorno deste arquivo será sempre no formato JSON
header('Content-Type: application/json');

// Captura o termo digitado pelo cliente no simulador
$termo_pesquisado = $_GET['termo'] ?? '';

// Configurações Oficiais da sua conta Google
$chave_api_google = 'AIzaSyABW18wyZphxbk5QXPl0cG83jbGkB3oia0';
$identificador_motor_busca = '025203d8a65434468';

// Validação simples: se o termo estiver vazio, retorna uma lista vazia
if (empty($termo_pesquisado)) {
    echo json_encode(['items' => []]);
    exit;
}

/**
 * Montagem da URL de consulta na API JSON do Google.
 * Filtramos para trazer no máximo 7 resultados para garantir velocidade no mobile.
 */
$url_da_requisicao = "https://www.googleapis.com/customsearch/v1";
$url_da_requisicao .= "?key=" . $chave_api_google;
$url_da_requisicao .= "&cx=" . $identificador_motor_busca;
$url_da_requisicao .= "&num=7";
$url_da_requisicao .= "&q=" . urlencode($termo_pesquisado);

/**
 * INÍCIO DA CONSULTA VIA CURL
 * O cURL é mais seguro e performático na Locaweb para chamadas externas.
 */
$sessao_curl = curl_init();

curl_setopt($sessao_curl, CURLOPT_URL, $url_da_requisicao);
curl_setopt($sessao_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($sessao_curl, CURLOPT_SSL_VERIFYPEER, false); // Necessário para alguns servidores da Locaweb
curl_setopt($sessao_curl, CURLOPT_TIMEOUT, 15);           // Tempo limite de 15 segundos

// Executa a chamada e armazena a resposta bruta do Google
$resposta_bruta_google = curl_exec($sessao_curl);

// Verifica se houve erro na conexão técnica
if (curl_errno($sessao_curl)) {
    echo json_encode(['erro' => 'Falha na conexão com a IA: ' . curl_error($sessao_curl)]);
    curl_close($sessao_curl);
    exit;
}

curl_close($sessao_curl);

/**
 * RETORNO DOS DADOS
 * Enviamos o JSON exatamente como o Google entregou para o nosso simulador processar.
 */
echo $resposta_bruta_google;