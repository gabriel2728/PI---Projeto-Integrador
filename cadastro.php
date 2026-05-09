<!DOCTYPE html>
<?php
session_start();
include('php/seguranca.php');
$csrf_token = gerarTokenCSRF();
?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | SiSGEH</title>
    <link rel="stylesheet" type="text/css" href="css/components/header.css"> 
    <link rel="stylesheet" type="text/css" href="css/style.css"> 
   <link rel="stylesheet" type="text/css" href="css/components/botoes.css">  
</head>
<body>

    <header>
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
        <h1 class="sisgeh">SiSGEH</h1>

        <nav class="links">
            <ul>
                <li>
                    <a href="sobre.html" class="sobre">Sobre</a>
                </li>

                <li>
                    <a href="index.html" class="link_home">
                        <img src="images/home.png" alt="Voltar a Home" class="home">
                    </a>
                </li>
            </ul>
        </nav>
    </header>


    <main>
        <div class="layout">
            <section class="section">
                <h1>Criar Conta</h1>
                <form action="php/cadastro.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="text" name="nomeUsuario" placeholder="Nome de Usuário" maxlength="35" required>
                    <input type="tel" name="telefoneUsuario" placeholder="Telefone" maxlength="11" inputmode="numeric" required>
                    <input type="email" name="emailUsuario" placeholder="E-mail" maxlength="50" required>
                    <input type="password" name="senha" placeholder="Senha" minlength="8" maxlength="20" required>
                    <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" minlength="8" maxlength="20" required>
                    <input type="submit" class="botao-cinza" value="Enviar">
                </form>
            </section>
        </div>
    </main>
    <footer>
        <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
    </footer>
<script>
document.querySelector("form").addEventListener("submit", function(event) {
    const nome = document.querySelector("input[name='nomeUsuario']").value.trim();
    const telefone = document.querySelector("input[name='telefoneUsuario']").value.trim();
    const email = document.querySelector("input[name='emailUsuario']").value.trim();
    const senha = document.querySelector("input[name='senha']").value;
    const confirmar = document.querySelector("input[name='confirmar_senha']").value;

    // Verifica se todos os campos foram preenchidos
    if (!nome || !telefone || !email || !senha || !confirmar) {
        alert("Por favor, preencha todos os campos.");
        event.preventDefault(); // impede o envio do formulário
        return;
    }

    // Verifica formato básico de e-mail
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regexEmail.test(email)) {
        alert("Por favor, insira um e-mail válido.");
        event.preventDefault();
        return;
    }

    const regexNome = /^[A-Za-zÀ-ÿ\s.'-]{2,50}$/;
    if (!regexNome.test(nome)) {
        alert("O nome deve conter apenas letras, espaços, pontos, hífens e apóstrofos.");
        event.preventDefault();
        return;
    }

    // Verifica força da senha
    const regexSenha = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
    if (!regexSenha.test(senha)) {
        alert("A senha precisa ter pelo menos 8 caracteres, com letra maiúscula, minúscula e número.");
        event.preventDefault();
        return;
    }

    // Verifica se as senhas conferem
    if (senha !== confirmar) {
        alert("As senhas não coincidem.");
        event.preventDefault();
        return;
    }

    // Verifica se o telefone contém apenas números
    const regexTelefone = /^[0-9]{10,11}$/;
    if (!regexTelefone.test(telefone)) {
        alert("O telefone deve conter apenas números (com DDD).");
        event.preventDefault();
        return;
    }

    // Se tudo estiver ok, o formulário é enviado normalmente
});
</script>

</body>
</html>
