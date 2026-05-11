<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include 'conexao.php';

// Verificar se usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$csrf_token = gerarTokenCSRF();

// Buscar dados do usuário
$sql = "SELECT nomeUsuario, emailUsuario, telefoneUsuario FROM Usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    $_SESSION['mensagem_erro'] = 'Usuário não encontrado.';
    header('Location: inicio.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Perfil - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" href="../css/estilo_configuracao_perfil.css"> 
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/components/botoes.css">
    <script>
        function adicionarTokenCSRF(form) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = document.querySelector('input[name="csrf_token"]')?.value || '';
            form.appendChild(csrfInput);
        }

        // Função para abrir o formulário de trocar nome
        function abrirFormularioNome() {
            // Remover formulários existentes
            document.querySelectorAll('.form-config').forEach(form => form.remove());

            const areaConfig = document.querySelector('.configuracao');
            const form = document.createElement('form');
            form.className = 'form-config';
            form.innerHTML = `
		<div id="estiloNome">
                <input type="text" id="novoNome" placeholder="Digite o novo nome" required>
                <button type="button" onclick="salvarNome()">Confirmar</button>
		</div>`;

            // Inserir após o título "Configurações de Perfil"
            const titulo = areaConfig.querySelector('h2');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar o nome
        function salvarNome() {
            const novoNome = document.getElementById('novoNome').value.trim();

            // Validações no lado cliente
            if (novoNome === '') {
                alert('Por favor, digite um nome válido.');
                return;
            }

            if (novoNome.length < 2 || novoNome.length > 50) {
                alert('Nome deve ter entre 2 e 50 caracteres.');
                return;
            }

            // Regex para validar nome (apenas letras, espaços, hífens e apóstrofos)
            const regexNome = /^[a-zA-ZÀ-ÿ\s\-\']+$/;
            if (!regexNome.test(novoNome)) {
                alert('Nome deve conter apenas letras, espaços, hífens e apóstrofos.');
                return;
            }

            // Sanitizar entrada (remover caracteres potencialmente perigosos)
            const nomeSanitizado = novoNome.replace(/[<>\"']/g, '');

            // Criar formulário e enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'salvar_configuracoes_perfil.php';
            form.style.display = 'none';

            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo';
            tipoInput.value = 'nome';
            form.appendChild(tipoInput);

            const nomeInput = document.createElement('input');
            nomeInput.type = 'hidden';
            nomeInput.name = 'nome';
            nomeInput.value = nomeSanitizado;
            form.appendChild(nomeInput);

            adicionarTokenCSRF(form);

            document.body.appendChild(form);
            form.submit();
        }

        // Função para abrir o formulário de trocar senha
        function abrirFormularioSenha() {
            // Remover formulários existentes
            document.querySelectorAll('.form-config').forEach(form => form.remove());

            const areaConfig = document.querySelector('.configuracao');
            const form = document.createElement('form');
            form.className = 'form-config';
            form.innerHTML = `
		<div id="conteudoSenha">
			<div id="layoutSenha">
                		<input type="password" id="novaSenha" placeholder="Nova senha" required>
                		<input type="password" id="confirmarSenha" placeholder="Confirmar senha" required>
            		</div>
			<div id="layoutBotaoSenha">
			<button type="button" onclick="salvarSenha()">Confirmar</button>
			</div>
		</div>`;

            // Inserir após o título "Configurações de Perfil"
            const titulo = areaConfig.querySelector('h2');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar a senha
        function salvarSenha() {
            const senha1 = document.getElementById('novaSenha').value;
            const senha2 = document.getElementById('confirmarSenha').value;

            // Validações no lado cliente
            if (senha1 === '' || senha2 === '') {
                alert('Preencha os dois campos de senha.');
                return;
            }

            if (senha1 !== senha2) {
                alert('As senhas não coincidem.');
                return;
            }

            if (senha1.length < 8) {
                alert('A senha deve ter pelo menos 8 caracteres.');
                return;
            }

            if (senha1.length > 255) {
                alert('A senha deve ter no máximo 255 caracteres.');
                return;
            }

            // Regex para validar senha forte (pelo menos uma maiúscula, minúscula e número)
            const regexSenha = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]+$/;
            if (!regexSenha.test(senha1)) {
                alert('A senha deve conter pelo menos uma letra maiúscula, uma minúscula e um número.');
                return;
            }

            // Verificar se a senha contém caracteres suspeitos
            if (/<|>|'|"|;/.test(senha1)) {
                alert('A senha contém caracteres inválidos.');
                return;
            }

            // Criar formulário e enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'salvar_configuracoes_perfil.php';
            form.style.display = 'none';

            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo';
            tipoInput.value = 'senha';
            form.appendChild(tipoInput);

            const senhaInput = document.createElement('input');
            senhaInput.type = 'hidden';
            senhaInput.name = 'senha';
            senhaInput.value = senha1;
            form.appendChild(senhaInput);

            const confirmarInput = document.createElement('input');
            confirmarInput.type = 'hidden';
            confirmarInput.name = 'confirmar_senha';
            confirmarInput.value = senha2;
            form.appendChild(confirmarInput);

            adicionarTokenCSRF(form);

            document.body.appendChild(form);
            form.submit();
        }

        // Função para abrir o formulário de trocar e-mail
        function abrirFormularioEmail() {
            // Remover formulários existentes
            document.querySelectorAll('.form-config').forEach(form => form.remove());

            const areaConfig = document.querySelector('.configuracao');
            const form = document.createElement('form');
            form.className = 'form-config';
            form.innerHTML = `
		<div id="conteudoEmail">
			<div id="layoutEmail">
               			<input type="email" id="novoEmail" placeholder="Digite o novo e-mail" required>
                		<input type="email" id="confirmarEmail" placeholder="Confirme o novo e-mail" required>
			</div>
			<div id="layoutBotaoEmail">
                		<button type="button" onclick="salvarEmail()" class="botao-cinza">Confirmar</button>
			</div>
		</div>
            `;

            // Inserir após o título "Configurações de Perfil"
            const titulo = areaConfig.querySelector('h2');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar o e-mail
        function salvarEmail() {
            const email1 = document.getElementById('novoEmail').value.trim();
            const email2 = document.getElementById('confirmarEmail').value.trim();

            // Validações no lado cliente
            if (email1 === '' || email2 === '') {
                alert('Preencha os dois campos de e-mail.');
                return;
            }

            if (email1 !== email2) {
                alert('Os e-mails não coincidem.');
                return;
            }

            if (email1.length > 100) {
                alert('E-mail muito longo (máximo 100 caracteres).');
                return;
            }

            // Regex para validar formato de e-mail
            const regexEmail = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
            if (!regexEmail.test(email1)) {
                alert('Digite um e-mail válido.');
                return;
            }

            // Verificar se contém caracteres suspeitos
            if (/<|>|'|"|;/.test(email1)) {
                alert('E-mail contém caracteres inválidos.');
                return;
            }

            // Criar formulário e enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'salvar_configuracoes_perfil.php';
            form.style.display = 'none';

            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo';
            tipoInput.value = 'email';
            form.appendChild(tipoInput);

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = email1;
            form.appendChild(emailInput);

            const confirmarInput = document.createElement('input');
            confirmarInput.type = 'hidden';
            confirmarInput.name = 'confirmar_email';
            confirmarInput.value = email2;
            form.appendChild(confirmarInput);

            adicionarTokenCSRF(form);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>
    <header>
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
        <h1 class="sisgeh"> SiSGEH </h1>

        <nav class="links">
            <ul>
                <li>
                    <a href="inicio.php" class="link_home">
                        <img src="../images/home.png" alt="Voltar a Home" class="home">
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="layout">
            <section class="configuracao">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">


                <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                    <div class="mensagem sucesso">
                        <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); unset($_SESSION['mensagem_sucesso']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensagem_erro'])): ?>
                    <div class="mensagem erro">
                        <?php echo htmlspecialchars($_SESSION['mensagem_erro']); unset($_SESSION['mensagem_erro']); ?>
                    </div>
                <?php endif; ?>

                <div class="mensagem-pequena">
                    <h2>Configurações de Perfil</h2>
                </div>

                <!-- Botões que abrem os formulários -->
                <a href="#" onclick="abrirFormularioNome(); return false;" class="botao-generico"> 📛 Trocar Nome </a>
                <a href="#" onclick="abrirFormularioSenha(); return false;" class="botao-generico"> 🔑 Trocar Senha </a>
                <a href="#" onclick="abrirFormularioEmail(); return false;" class="botao-generico"> 📧 Trocar E-mail </a>
                <a href="#" class="botao-generico"> ❌ Excluir conta </a>
                <!-- Botão sair estilizado -->
                <form method="post" action="logout.php">
                    <button type="submit" class="botao-cinza">📥 Sair da conta</button>
                </form>

                <!-- Dados do usuário -->
                <div class="dados-usuario">
                    <h3>Seus Dados Atuais</h3>
                    <div class="dado-item">
                        <span class="dado-label">Nome:</span>
                        <span class="dado-valor"><?php echo htmlspecialchars($usuario['nomeUsuario']); ?></span>
                    </div>
                    <div class="dado-item">
                        <span class="dado-label">E-mail:</span>
                        <span class="dado-valor"><?php echo htmlspecialchars($usuario['emailUsuario']); ?></span>
                    </div>
                    <div class="dado-item">
                        <span class="dado-label">Telefone:</span>
                        <span class="dado-valor"><?php echo htmlspecialchars($usuario['telefoneUsuario']); ?></span>
                    </div>
                </div>
                <a href="configuracoes.php" class="botao-cinza">← Voltar</a>
            </section>
        </div>
    </main>    

    <footer>
        <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
    </footer>
</body>
</html>
