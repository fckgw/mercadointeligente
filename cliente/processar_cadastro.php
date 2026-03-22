<?php
/**
 * SISTEMA MERCADO INTELIGENTE
 * Arquivo: cliente/processar_cadastro.php
 */
require_once '../core/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome_completo']);
    $email    = trim($_POST['email_usuario']);
    $ddd      = $_POST['ddd_telefone'];
    $telefone = $_POST['numero_telefone'];
    $rg       = $_POST['rg_usuario'];
    $cidade   = $_POST['cidade_usuario'];
    $estado   = $_POST['estado_usuario'];

    // GERA SENHA ALEATÓRIA (6 caracteres Alfanuméricos)
    $caracteres_permitidos = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sem O e 0 para evitar confusão
    $senha_gerada_aleatoria = substr(str_shuffle($caracteres_permitidos), 0, 6);
    $senha_criptografada_hash = password_hash($senha_gerada_aleatoria, PASSWORD_DEFAULT);

    try {
        // PERFIL SEMPRE 'cliente' E 'trocar_senha' ATIVADO
        $instrucao_insert = "INSERT INTO usuarios (nome, email, senha, perfil, ddd, telefone, rg, cidade, estado, trocar_senha) 
                             VALUES (?, ?, ?, 'cliente', ?, ?, ?, ?, ?, 1)";
        
        $comando_banco = $pdo->prepare($instrucao_insert);
        $comando_banco->execute([$nome, $email, $senha_criptografada_hash, $ddd, $telefone, $rg, $cidade, $estado]);

        // Prepara mensagem WhatsApp
        $mensagem_whatsapp = urlencode("Olá $nome! Bem-vindo ao Mercado Inteligente. Sua senha de acesso provisória é: $senha_gerada_aleatoria. Lembre-se de alterá-la no primeiro acesso.");
        $link_whatsapp_final = "https://wa.me/55$ddd" . str_replace('-', '', $telefone) . "?text=$mensagem_whatsapp";

    } catch (PDOException $erro_sql) {
        die("Erro: Este e-mail ou RG já está cadastrado em nossa base.");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<script>
    Swal.fire({
        title: 'Cadastro Realizado!',
        html: '<p>Sua senha provisória é: <b style="font-size: 28px; color: #0d6efd; letter-spacing: 2px;"><?php echo $senha_gerada_aleatoria; ?></b></p><p class="small text-muted">Você deverá trocá-la ao entrar no sistema.</p>',
        icon: 'success',
        confirmButtonText: '<i class="bi bi-whatsapp"></i> Receber no WhatsApp',
        showDenyButton: true,
        denyButtonText: '<i class="bi bi-clipboard"></i> Copiar Senha',
        confirmButtonColor: '#25D366',
        denyButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            window.open('<?php echo $link_whatsapp_final; ?>', '_blank');
            window.location.href = '../admin/login.php';
        } else if (result.isDenied) {
            navigator.clipboard.writeText('<?php echo $senha_gerada_aleatoria; ?>');
            Swal.fire('Copiado!', 'Senha na área de transferência. Use-a no login.', 'info').then(() => {
                window.location.href = '../admin/login.php';
            });
        } else {
            window.location.href = '../admin/login.php';
        }
    });
</script>
</body>
</html>