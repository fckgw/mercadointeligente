<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cadastro - Mercado Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card-cadastro { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-label { font-weight: bold; font-size: 0.8rem; color: #666; }
        .input-custom { padding: 12px; border-radius: 10px; border: 1px solid #ddd; }
        .termos-texto { font-size: 0.85rem; line-height: 1.6; color: #444; height: 300px; overflow-y: scroll; padding: 15px; background: #f9f9f9; border: 1px solid #eee; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9 col-12">
            
            <div class="text-center mb-4">
                <img src="../images/Logo_MercadoInteligente.png" style="max-height: 50px;">
                <h4 class="fw-bold mt-3">Crie sua conta no Mercado Inteligente</h4>
                <p class="text-muted small">Tenha acesso ao simulador offline e seu histórico de economia.</p>
            </div>

            <div class="card card-cadastro p-4">
                <form action="processar_cadastro.php" method="POST" id="formularioCadastro">
                    
                    <!-- NOME COMPLETO -->
                    <div class="mb-3">
                        <label class="form-label">NOME COMPLETO</label>
                        <input type="text" name="nome_completo" class="form-control input-custom" placeholder="Digite seu nome" required>
                    </div>

                    <!-- TELEFONE COM DDD -->
                    <div class="row">
                        <div class="col-3 col-md-2 mb-3">
                            <label class="form-label">DDD</label>
                            <input type="number" name="ddd_telefone" class="form-control input-custom text-center" placeholder="12" required oninput="javascript: if (this.value.length > 2) this.value = this.value.slice(0, 2);">
                        </div>
                        <div class="col-9 col-md-10 mb-3">
                            <label class="form-label">CELULAR (WHATSAPP)</label>
                            <input type="text" name="numero_telefone" id="input_telefone" class="form-control input-custom" placeholder="00000-0000" required onkeyup="mascaraTelefoneManual(this)">
                        </div>
                    </div>

                    <!-- RG E EMAIL -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RG</label>
                            <input type="text" name="rg_usuario" class="form-control input-custom" placeholder="00.000.000-0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-MAIL</label>
                            <input type="email" name="email_usuario" class="form-control input-custom" placeholder="exemplo@email.com" required>
                        </div>
                    </div>

                    <!-- ESTADO E CIDADE -->
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label">ESTADO</label>
                            <select name="estado_usuario" class="form-select input-custom">
                                <option value="SP" selected>SP</option>
                                <option value="RJ">RJ</option>
                                <option value="MG">MG</option>
                            </select>
                        </div>
                        <div class="col-8 mb-3">
                            <label class="form-label">CIDADE</label>
                            <input type="text" name="cidade_usuario" class="form-control input-custom" value="São José dos Campos" required>
                        </div>
                    </div>

                    <!-- CHECKBOX LGPD -->
                    <div class="form-check mb-4 mt-2">
                        <input class="form-check-input" type="checkbox" id="checkLgpd" required style="width: 20px; height: 20px;">
                        <label class="form-check-label ms-2 small" for="checkLgpd">
                            Eu li e aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#modalLgpd">Termos de Uso e Política de Privacidade (LGPD)</a>.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow fw-bold py-3">CADASTRAR E GERAR SENHA</button>
                    
                    <div class="text-center mt-3">
                        <a href="../admin/login.php" class="text-muted small text-decoration-none">Já tenho conta? Entrar agora</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LGPD -->
<div class="modal fade" id="modalLgpd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="fw-bold">Política de Privacidade e Termos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="termos-texto">
                    <h6>Projeto: Mercado Inteligente</h6>
                    <p><strong>Empresa Desenvolvedora: BDSoftech</strong></p>
                    <p><strong>1. APRESENTAÇÃO</strong><br>
                    O presente documento tem como finalidade estabelecer as diretrizes legais... assegurando transparência no tratamento de dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).</p>
                    <p><strong>2. DEFINIÇÕES</strong><br>
                    Usuário: pessoa física que utiliza o sistema Mercado Inteligente.<br>
                    Dados Pessoais: informações que identifiquem uma pessoa.</p>
                    <p><strong>3. COLETA DE DADOS</strong><br>
                    O sistema coleta: Nome, RG, E-mail, Telefone, Estado e Cidade.</p>
                    <p><strong>4. FINALIDADE</strong><br>
                    Os dados serão usados para cadastro, autenticação, análise inteligente de mercado e BI regional.</p>
                    <p><strong>5. COMPARTILHAMENTO</strong><br>
                    A BDSoftech NÃO comercializa dados pessoais.</p>
                    <p><strong>6. ACEITE</strong><br>
                    Ao clicar em “ACEITO” no momento do cadastro, o usuário concorda integralmente com este termo.</p>
                    <hr>
                    <p class="small text-muted">Para dúvidas: suporte@bdsoft.com.br | (31) 97195-7751</p><br>
                    <p class="small text-muted">Siga-nos nas Redes Sociais: https://www.instagram.com/bdsoftech/</p>
                    
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-dark px-5" data-bs-dismiss="modal">LI E ENTENDI</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mascaraTelefoneManual(elemento) {
    let valor = elemento.value.replace(/\D/g, "");
    valor = valor.replace(/^(\d{5})(\d)/g, "$1-$2");
    elemento.value = valor.substring(0, 10);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>