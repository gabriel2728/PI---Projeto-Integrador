<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - SiSGEH</title>
    <link rel="stylesheet" href="estilo_configuracao.css">
    <script>
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
            const titulo = areaConfig.querySelector('h1');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar o nome
        function salvarNome() {
            const novoNome = document.getElementById('novoNome').value.trim();
            if (novoNome === '') {
                alert('Por favor, digite um nome válido.');
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
            tipoInput.value = 'nome';
            form.appendChild(tipoInput);

            const nomeInput = document.createElement('input');
            nomeInput.type = 'hidden';
            nomeInput.name = 'nome';
            nomeInput.value = novoNome;
            form.appendChild(nomeInput);

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
            const titulo = areaConfig.querySelector('h1');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar a senha
        function salvarSenha() {
            const senha1 = document.getElementById('novaSenha').value;
            const senha2 = document.getElementById('confirmarSenha').value;

            if (senha1 === '' || senha2 === '') {
                alert('Preencha os dois campos de senha.');
                return;
            }

            if (senha1 !== senha2) {
                alert('As senhas não coincidem.');
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
                		<button type="button" onclick="salvarEmail()">Confirmar</button>
			</div>
		</div>
            `;

            // Inserir após o título "Configurações de Perfil"
            const titulo = areaConfig.querySelector('h1');
            titulo.insertAdjacentElement('afterend', form);
        }

        // Função para salvar o e-mail
        function salvarEmail() {
            const email1 = document.getElementById('novoEmail').value.trim();
            const email2 = document.getElementById('confirmarEmail').value.trim();

            if (email1 === '' || email2 === '') {
                alert('Preencha os dois campos de e-mail.');
                return;
            }

            if (email1 !== email2) {
                alert('Os e-mails não coincidem.');
                return;
            }

            // Validação simples de formato de e-mail
            const regexEmail = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
            if (!regexEmail.test(email1)) {
                alert('Digite um e-mail válido.');
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
        <h2 class="sisgeh"> SiSGEH </h2>

        <div class="links">
             <a href="inicio.php" class="link_home">
                 <img src="icon_home.png" alt="Voltar a Home" class="home"> 
            </a>
        </div>
    </header>

    <div class="layoutConfiguracao">

	    <div class="configuracao">

            <div style="text-align: center; margin-bottom: 20px;">
                <a href="configuracoes.php" style="display: inline-block; padding: 10px 20px; margin: 0 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">👤 Perfil</a>
                <a href="configuracoes_sistema.php" style="display: inline-block; padding: 10px 20px; margin: 0 10px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">⚙️ Sistema</a>
            </div>

            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="mensagem sucesso">
                    <?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensagem_erro'])): ?>
                <div class="mensagem erro">
                    <?php echo $_SESSION['mensagem_erro']; unset($_SESSION['mensagem_erro']); ?>
                </div>
            <?php endif; ?>

	    <h1> Configurações de Perfil </h1>
	
		<!-- Botões que abrem os formulários -->
		<a href="#" onclick="abrirFormularioNome()"> 📛 Trocar Nome </a>
		<a href="#" onclick="abrirFormularioSenha()"> 🔑 Trocar Senha </a>
        <a href="#" onclick="abrirFormularioEmail()"> 📧 Trocar E-mail </a>
		<a href="#"> ❌ Excluir conta </a>
        <a href="#"> 📥 Sair da conta </a>
		
        </div>
   </div>

     <footer>
        <p> &copy Todos os direitos reservados. <a href="politica.html"> Políticas de privacidade. </a> </p>
    </footer>

<body>
</html>