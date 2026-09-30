<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" href="../css/login-test.css?v=fundo2026">
    <link rel="stylesheet" href="../css/components/marca.css">
</head>
<body>

       
    <main>
        <div class="container-cadastro">
            <a href="../cadastro.php" class="cadastro">Cadastre-se!</a>
        </div>
        
        <div class="container-login">

            <section class="section">

                <div class="marca-container">
                    <div class="marca">
                        <h1>SiSGEH</h1>
                        <hr>
                        <p> Sistema de geração de energia hidrelétrica </p>
                    </div>
                </div>


                <figure>
                        <img src="../images/logo.png" class="logo">
                    </figure>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="email" name="email" placeholder="Digite um nome de usuário ou E-mail" maxlength="50" required>

                    <br>

                    <input type="password" name="senha" placeholder="Digite sua senha" minlength="8" maxlength="20" required>
                    <br>

                    <input type="submit" name="entrar" value="Entrar" style="align-self: center;">
                </form>

                <a href="../recuperar_senha.php" class="esqueci_senha">Esqueci minha senha</a>

            </section>

            
        </div>
</body>
</html>