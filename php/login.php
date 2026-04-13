<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (isset($_POST['entrar'])) {
    include('conexao.php'); // arquivo com sua conexão MySQL

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Verifica se o e-mail existe no banco
    $sql = "SELECT * FROM Usuario WHERE emailUsuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Verifica senha criptografada
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nomeUsuario'] = $usuario['nomeUsuario'];

            header("Location: inicio.php");
            exit;
        } else {
            echo "<script>alert('Senha incorreta!');</script>";
        }
    } else {
        echo "<script>alert('E-mail não encontrado!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/estilo_login.css">
</head>
<body>

    <header> 
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
        <h2 class="sisgeh"> SiSGEH </h2>

        <div class="links">

            <a href="sobre.html" class="sobre"> Sobre </a>
            <a href="index.html" class="link_home">
                 <img src="icon_home.png" alt="Voltar a Home" class="home"> 
            </a>
        </div>
    </header>

        <div class="lateral">
            <h1> Seja Bem-Vindo! </h1>
	        <p> Novo por aqui? </p>
            <br>
	        <a href="cadastro.html" > Criar conta </a>
        </div>

        <img src="logo.png" class="logo">
        <div class="layoutEntrar">
        
            <h1>Área do Usuário</h1>
		    <p>Identifique-se</p>

            <form action="login.php" method="POST">
                <label for="email">E-MAIL</label>
                <input type="email" name="email" placeholder="E-mail" maxlength="50" required>

                <br>

                <label for="senha">SENHA</label>
                <input type="password" name="senha" placeholder="Senha" minlength="8" maxlength="20" required>
                <br>

                <input type="submit" name="entrar" value="Entrar" style="align-self: center;">
            </form>

               <a href="../recuperar_senha.php" class="esqueci_senha">Esqueci minha senha</a>
        </div>

</body>
</html>