<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/autenticar.php
 * Finalidade: Processar a autenticação de usuários, validar senhas criptografadas e gerenciar permissões.
 */

// Inicia a sessão para permitir o armazenamento de dados do usuário logado
session_start();

// Importa a conexão com o banco de dados via PDO
require_once '../core/db.php';

/**
 * Verifica se a requisição foi enviada através do método POST (formulário de login).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Captura o e-mail e a senha digitados, removendo espaços em branco acidentais
    $email_digitado_pelo_usuario = trim($_POST['email'] ?? '');
    $senha_digitada_pelo_usuario = $_POST['senha'] ?? '';

    // Validação básica de preenchimento
    if (empty($email_digitado_pelo_usuario) || empty($senha_digitada_pelo_usuario)) {
        header("Location: login.php?erro=campos_vazios");
        exit;
    }

    try {
        /**
         * CONSULTA AO BANCO DE DADOS
         * Busca o registro do usuário através do e-mail informado.
         */
        $instrucao_sql_busca_usuario = "SELECT id, nome, email, senha, perfil, trocar_senha FROM usuarios WHERE email = ? LIMIT 1";
        $comando_preparado_consulta = $pdo->prepare($instrucao_sql_busca_usuario);
        $comando_preparado_consulta->execute([$email_digitado_pelo_usuario]);
        
        // Recupera os dados do usuário como um array associativo
        $informacoes_do_usuario_no_banco = $comando_preparado_consulta->fetch(PDO::FETCH_ASSOC);

        /**
         * LOGICA DE VALIDAÇÃO DA SENHA
         * O sistema utiliza a função password_verify para comparar a senha digitada
         * com o Hash (criptografia) armazenado com segurança no banco de dados.
         */
        if ($informacoes_do_usuario_no_banco && password_verify($senha_digitada_pelo_usuario, $informacoes_do_usuario_no_banco['senha'])) {
            
            // SENHA CORRETA: Criamos as variáveis de sessão para manter o usuário conectado
            $_SESSION['usuario_id']     = $informacoes_do_usuario_no_banco['id'];
            $_SESSION['usuario_nome']   = $informacoes_do_usuario_no_banco['nome'];
            $_SESSION['usuario_perfil'] = $informacoes_do_usuario_no_banco['perfil']; // 'admin' ou 'cliente'

            /**
             * VERIFICAÇÃO DE SEGURANÇA: TROCA DE SENHA OBRIGATÓRIA
             * Se a coluna 'trocar_senha' estiver marcada como 1, o usuário é o novo cadastrado
             * e deve ser enviado para a tela de alteração de senha antes de usar o sistema.
             */
            if ($informacoes_do_usuario_no_banco['trocar_senha'] == 1) {
                header("Location: trocar_senha_obrigatoria.php");
                exit;
            }

            /**
             * REDIRECIONAMENTO POR PERFIL
             * Usuário Admin -> Vai para o Painel Administrativo (Dashboard)
             * Usuário Cliente -> Vai para o Simulador de Compras (Mercado Inteligente)
             */
            if ($informacoes_do_usuario_no_banco['perfil'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: ../cliente/simulador.php");
            }
            exit;

        } else {
            // Caso o e-mail não exista ou a senha não confira com o Hash
            header("Location: login.php?erro=1");
            exit;
        }

    } catch (PDOException $excecao_banco_dados) {
        /**
         * Em caso de falha técnica de conexão com o banco de dados na Locaweb,
         * o sistema registra o erro e interrompe a execução com uma mensagem clara.
         */
        die("Erro Crítico de Autenticação no Banco de Dados: " . $excecao_banco_dados->getMessage());
    }

} else {
    /**
     * Caso alguém tente acessar este arquivo diretamente pela URL (sem ser pelo formulário),
     * o sistema redireciona automaticamente para a tela de login.
     */
    header("Location: login.php");
    exit;
}