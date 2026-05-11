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

// Buscar configurações atuais do usuário
$configuracoes = [];
$result = mysqli_query($conn, "SELECT * FROM UsuarioConfiguracoes WHERE id_usuario = $id_usuario");
if (mysqli_num_rows($result) > 0) {
    $configuracoes = mysqli_fetch_assoc($result);
} else {
    // Se não existir configuração, criar uma padrão
    $sql = "INSERT INTO UsuarioConfiguracoes (id_usuario, tema, notificacoes_email, notificacoes_sistema, notificacoes_simulacao, notificacoes_relatorios)
            VALUES ($id_usuario, 'claro', 1, 1, 1, 1)";
    mysqli_query($conn, $sql);
    $configuracoes = [
        'tema' => 'claro',
        'notificacoes_email' => 1,
        'notificacoes_sistema' => 1,
        'notificacoes_simulacao' => 1,
        'notificacoes_relatorios' => 1
    ];
}

// Processar formulário de configurações do sistema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tema = $_POST['tema'] ?? 'claro';
    $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
    $notificacoes_sistema = isset($_POST['notificacoes_sistema']) ? 1 : 0;
    $notificacoes_simulacao = isset($_POST['notificacoes_simulacao']) ? 1 : 0;
    $notificacoes_relatorios = isset($_POST['notificacoes_relatorios']) ? 1 : 0;

    // Atualizar configurações no banco
    $sql = "UPDATE UsuarioConfiguracoes SET
            tema = '$tema',
            notificacoes_email = $notificacoes_email,
            notificacoes_sistema = $notificacoes_sistema,
            notificacoes_simulacao = $notificacoes_simulacao,
            notificacoes_relatorios = $notificacoes_relatorios
            WHERE id_usuario = $id_usuario";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['mensagem_sucesso'] = 'Configurações do sistema salvas com sucesso!';
        // Atualizar array de configurações
        $configuracoes['tema'] = $tema;
        $configuracoes['notificacoes_email'] = $notificacoes_email;
        $configuracoes['notificacoes_sistema'] = $notificacoes_sistema;
        $configuracoes['notificacoes_simulacao'] = $notificacoes_simulacao;
        $configuracoes['notificacoes_relatorios'] = $notificacoes_relatorios;
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao salvar configurações: ' . mysqli_error($conn);
    }

    // Redirecionar para evitar reenvio do formulário
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - SiSGEH</title>
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
                    <a href="inicio.php" class="link_home">
                        <img src="../images/home.png" alt="Voltar a Home" class="home">
                    </a>
                </li>
        </nav>
    </header>

    <main>
        <div class="layout">
            <section class="section">
                    
                <div class="mensagem-pequena">
                    <h2>Configurações do Sistema</h2>
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

                <form method="POST">
                    <div class="form-group">
                        <label for="tema">🎨 Tema da Interface:</label>
                        <select name="tema" id="tema">
                            <option value="claro" <?php echo ($configuracoes['tema'] == 'claro') ? 'selected' : ''; ?>>Claro</option>
                            <option value="escuro" <?php echo ($configuracoes['tema'] == 'escuro') ? 'selected' : ''; ?>>Escuro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Notificações:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="notificacoes_email" id="notificacoes_email"
                                    <?php echo ($configuracoes['notificacoes_email']) ? 'checked' : ''; ?>>
                                <label for="notificacoes_email">📧 Notificações por e-mail</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notificacoes_sistema" id="notificacoes_sistema"
                                    <?php echo ($configuracoes['notificacoes_sistema']) ? 'checked' : ''; ?>>
                                <label for="notificacoes_sistema">🔔 Notificações do sistema</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notificacoes_simulacao" id="notificacoes_simulacao"
                                    <?php echo ($configuracoes['notificacoes_simulacao']) ? 'checked' : ''; ?>>
                                <label for="notificacoes_simulacao">📊 Notificações de simulações</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="notificacoes_relatorios" id="notificacoes_relatorios"
                                    <?php echo ($configuracoes['notificacoes_relatorios']) ? 'checked' : ''; ?>>
                                <label for="notificacoes_relatorios">📋 Notificações de relatórios</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="botao-generico">💾 Salvar Configurações</button>
                </form>

                <a href="configuracoes.php" class="botao-cinza">← Voltar</a>
            </section>
        </div>
    </main>

    <footer>
        <p> &copy Todos os direitos reservados. <a href="politica.html"> Políticas de privacidade. </a> </p>
    </footer>
</body>
</html>
