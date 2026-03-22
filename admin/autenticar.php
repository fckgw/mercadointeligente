<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/autenticar.php
 * Finalidade: Processar o login de usuários, validar segurança e registrar metadados de acesso.
 */

// Inicia a sessão para permitir o armazenamento de dados do usuário durante a navegação
session_start();

// Importa a conexão com o banco de dados via PDO
require_once '../core/db.php';

/**
 * Verifica se a requisição de acesso foi enviada via formulário (Método POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Captura as credenciais fornecidas pelo usuário, removendo espaços vazios acidentais
    $email_fornecido_pelo_usuario = trim($_POST['email'] ?? '');
    $senha_fornecida_pelo_usuario = $_POST['senha'] ?? '';

    // Validação básica: impede o processamento se os campos estiverem vazios
    if (empty($email_fornecido_pelo_usuario) || empty($senha_fornecida_pelo_usuario)) {
        header("Location: login.php?erro=campos_obrigatorios");
        exit;
    }

    try {
        /**
         * CONSULTA AO BANCO DE DADOS
         * Busca os dados do usuário, incluindo o campo de troca de senha e a data do último acesso.
         */
        $instrucao_sql_busca_usuario = "SELECT id, nome, email, senha, perfil, trocar_senha, data_do_ultimo_acesso FROM usuarios WHERE email = ? LIMIT 1";
        $comando_preparado_busca = $pdo->prepare($instrucao_sql_busca_usuario);
        $comando_preparado_busca->execute([$email_fornecido_pelo_usuario]);
        
        $registro_do_usuario_encontrado = $comando_preparado_busca->fetch(PDO::FETCH_ASSOC);

        /**
         * VALIDAÇÃO DE SEGURANÇA
         * Compara a senha digitada com o Hash BCRYPT armazenado no banco de dados.
         */
        if ($registro_do_usuario_encontrado && password_verify($senha_fornecida_pelo_usuario, $registro_do_usuario_encontrado['senha'])) {
            
            /**
             * REGISTRO DO ÚLTIMO ACESSO (INTELIGÊNCIA DE DADOS)
             * 1. Capturamos a data que estava no banco (acesso anterior) para mostrar na tela.
             * 2. Atualizamos o banco com a data/hora de AGORA para o próximo login.
             */
            $data_do_acesso_anterior_armazenada = $registro_do_usuario_encontrado['data_do_ultimo_acesso'];

            $instrucao_sql_atualizar_acesso = "UPDATE usuarios SET data_do_ultimo_acesso = NOW() WHERE id = ?";
            $comando_preparado_atualizacao = $pdo->prepare($instrucao_sql_atualizar_acesso);
            $comando_preparado_atualizacao->execute([$registro_do_usuario_encontrado['id']]);

            /**
             * CRIAÇÃO DAS VARIÁVEIS DE SESSÃO
             */
            $_SESSION['usuario_id']     = $registro_do_usuario_encontrado['id'];
            $_SESSION['usuario_nome']   = $registro_do_usuario_encontrado['nome'];
            $_SESSION['usuario_perfil'] = $registro_do_usuario_encontrado['perfil'];

            // Formata a data do acesso anterior para exibição amigável no Simulador/Dashboard
            if (!empty($data_do_acesso_anterior_armazenada)) {
                $data_objeto_formatacao = new DateTime($data_do_acesso_anterior_armazenada);
                $_SESSION['ultimo_acesso_formatado'] = $data_objeto_formatacao->format('d/m/Y \à\s H:i');
            } else {
                $_SESSION['ultimo_acesso_formatado'] = "Primeiro Acesso ao Sistema";
            }

            /**
             * FLUXO DE REDIRECIONAMENTO INTELIGENTE
             */

            // REGRA 1: Se o usuário precisa trocar a senha obrigatoriamente
            if ($registro_do_usuario_encontrado['trocar_senha'] == 1) {
                header("Location: trocar_senha_obrigatoria.php");
                exit;
            }

            // REGRA 2: Se for Administrador, encaminha para o Painel de Controle
            if ($registro_do_usuario_encontrado['perfil'] === 'admin') {
                header("Location: dashboard.php");
                exit;
            } 
            
            // REGRA 3: Se for Cliente, encaminha para o Simulador de Compras
            else {
                header("Location: ../cliente/simulador.php");
                exit;
            }

        } else {
            /**
             * FALHA NA AUTENTICAÇÃO
             * Redireciona para o login com código de erro.
             */
            header("Location: login.php?erro=1");
            exit;
        }

    } catch (PDOException $erro_tecnico_banco_dados) {
        /**
         * TRATAMENTO DE ERROS NA LOCAWEB
         * Caso ocorra uma falha de conexão ou sintaxe, o sistema interrompe e exibe a falha.
         */
        die("Erro Crítico ao processar a autenticação: " . $erro_tecnico_banco_dados->getMessage());
    }

} else {
    /**
     * PROTEÇÃO DE ACESSO DIRETO
     * Se o arquivo for acessado diretamente sem o formulário, volta para o login.
     */
    header("Location: login.php");
    exit;
}