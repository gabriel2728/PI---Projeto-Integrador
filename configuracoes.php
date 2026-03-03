<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="estilo_configuracao.css"> 
    <script>
        // Função para abrir o formulário de trocar nome
        function abrirFormularioNome() {
            const areaConfig = document.querySelector('.configuracao');
            if (document.getElementById('formNome')) return; // Evita duplicar

            const form = document.createElement('form');
            form.id = 'formNome';
            form.innerHTML = `
		<div id="estiloNome">
                <input type="text" id="novoNome" placeholder="Digite o novo nome" required>
                <button type="button" onclick="salvarNome()">Confirmar</button>
		</div>`
		;

            const linkTrocarNome = areaConfig.querySelectorAll('a')[0];
            linkTrocarNome.insertAdjacentElement('afterend', form);
        }

        // Função para salvar o nome
        function salvarNome() {
            const novoNome = document.getElementById('novoNome').value.trim();
            if (novoNome === '') {
                alert('Por favor, digite um nome válido.');
                return;
            }

            // Aqui vai o fetch() ou requisição ao PHP depois
            alert('Nome alterado com sucesso para: ' + novoNome);
            document.getElementById('formNome').remove();
        }

        // Função para abrir o formulário de trocar senha
        function abrirFormularioSenha() {
            const areaConfig = document.querySelector('.configuracao');
            if (document.getElementById('formSenha')) return;

            const form = document.createElement('form');
            form.id = 'formSenha';
            form.innerHTML = `
		<div id="conteudoSenha">
			<div id="layoutSenha">
                		<input type="password" id="novaSenha" placeholder="Nova senha" required>
                		<input type="password" id="confirmarSenha" placeholder="Confirmar senha" required>
            		</div>
			<div id="layoutBotaoSenha">
			<button type="button" onclick="salvarSenha()">Confirmar</button>
			</div>
		</div>
		`;

            const linkTrocarSenha = areaConfig.querySelectorAll('a')[1];
            linkTrocarSenha.insertAdjacentElement('afterend', form);
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

            // Aqui você pode enviar ao PHP depois
            alert('Senha alterada com sucesso!');
            document.getElementById('formSenha').remove();
        }

        // Função para abrir o formulário de trocar e-mail
        function abrirFormularioEmail() {
            const areaConfig = document.querySelector('.configuracao');
            if (document.getElementById('formEmail')) return; // Evita duplicar

            const form = document.createElement('form');
            form.id = 'formEmail';
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

            const linkTrocarEmail = areaConfig.querySelectorAll('a')[2];
            linkTrocarEmail.insertAdjacentElement('afterend', form);
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

            // Aqui você pode enviar ao PHP depois
            alert('E-mail alterado com sucesso para: ' + email1);
            document.getElementById('formEmail').remove();
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

	    <h1> Configurações </h1>
	
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