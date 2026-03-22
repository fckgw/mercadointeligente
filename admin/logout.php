<?php
/**
 * SISTEMA MERCADO INTELIGENTE - MVP
 * Arquivo: admin/logout.php
 * Finalidade: Encerrar a sessão do usuário e redirecionar para a tela de login.
 */

// Inicia a sessão para ter acesso aos dados atuais
session_start();

// Remove todas as variáveis de sessão cadastradas
session_unset();

// Destrói a sessão completamente no servidor
session_destroy();

// Redireciona o usuário para a página de login administrativa
header("Location: login.php");
exit;
?>