<?php
session_start();
include('error_handler.php');
include('seguranca.php');

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
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/components/botoes.css">
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
                <a href="../sobr_e.html" class="sobre"> Sobre </a>

                <a href="configuracoes.php" class="link_config">
                    <img src="../images/gear.png" alt="Configurações" class="config">
                </a>
            </li>
        </ul>
    </nav>
</header>

    <main>
        <div class="layout">

            <section class="section">

                <div class="mensagem-pequena">
                    <h2>Bem-vindo, <?php echo $primeiroNome; ?>!</h2>
                    <p>Abaixo estão algumas opções que você pode selecionar:</p>
                </div>

                <a href="simulacao.php" class="botao-generico"> ⚡ Simulação</a>
                <a href="historico.php" class="botao-generico"> 🧾 Histórico</a>
                <a href="analise_preditiva.php" class="botao-generico"> 🔮 Análise Preditiva</a>

                <!-- Botão sair estilizado -->
                <form method="post" action="logout.php">
                    <button type="submit" class="botao-cinza">📤 Sair</button>
                </form>

            </section>
        </div>
</main>

<footer>
    <p>&copy Todos os direitos reservados. <a href="../politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>
