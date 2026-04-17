<?php
session_start();

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

// Pega dados do usuário
$nomeUsuario = $_SESSION['nomeUsuario'];
$primeiroNome = explode(" ", $nomeUsuario)[0]; // Pega só o primeiro nome
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - SiSGEH</title>
    <link rel="stylesheet" href="../css/estilo_inicio.css"> 
</head>
<body>

<header> 
    <div class="caixa_de_texto">
        <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>

    <h2 class="sisgeh"> SiSGEH </h2>

    <div class="links">
          <a href="../sobr_e.html" class="sobre"> Sobre </a>
          <a href="configuracoes.php" class="link_config">
             <img src="../config.png" alt="Configurações" class="config">
         </a>
    </div>
</header>

<div class="layoutInicio">

    <div class="inicio">

        <div class="mensagem">
            <h2>Bem-vindo, <?php echo $primeiroNome; ?>!</h2>
            <p>Abaixo estão algumas opções que você pode selecionar:</p>
        </div>

        <a href="simulacao.php"> ⚡ Simulação</a>
        <a href="historico.php"> 🧾 Histórico</a>

        <!-- Botão sair estilizado -->
        <form method="post" action="logout.php">
            <button type="submit" class="botao-sair">📤 Sair</button>
        </form>

    </div>

</div>

<footer>
    <p>&copy Todos os direitos reservados. <a href="../politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>
