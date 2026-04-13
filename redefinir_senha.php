<?php
session_start();

$mensagem_sucesso = '';
$mensagem_erro = '';
$token_valido = false;
$id_usuario = null;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (empty($token)) {
        $mensagem_erro = 'Token inválido.';
    } else {
        include 'php/conexao.php';

        // Verificar se token existe, não foi usado e não expirou
        $sql = 'SELECT rs.id_usuario, rs.usado, rs.data_expiracao, u.nomeUsuario
                FROM RecuperacaoSenha rs
                JOIN Usuario u ON rs.id_usuario = u.id_usuario
                WHERE rs.token = ? AND rs.usado = FALSE AND rs.data_expiracao > NOW()';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $dados = $resultado->fetch_assoc();
            $token_valido = true;
            $id_usuario = $dados['id_usuario'];
            $nome_usuario = $dados['nomeUsuario'];
        } else {
            $mensagem_erro = 'Link inválido, expirado ou já utilizado.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $senha1 = $_POST['senha'] ?? '';
    $senha2 = $_POST['confirmar_senha'] ?? '';

    if (empty($token) || empty($senha1) || empty($senha2)) {
        $mensagem_erro = 'Preencha todos os campos.';
    } elseif ($senha1 !== $senha2) {
        $mensagem_erro = 'As senhas não coincidem.';
    } elseif (strlen($senha1) < 6) {
        $mensagem_erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        include 'php/conexao.php';

        // Verificar token novamente
        $sql = 'SELECT id_usuario FROM RecuperacaoSenha
                WHERE token = ? AND usado = FALSE AND data_expiracao > NOW()';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $dados = $resultado->fetch_assoc();
            $id_usuario_token = $dados['id_usuario'];

            // Atualizar senha
            $senha_hash = password_hash($senha1, PASSWORD_DEFAULT);
            $sqlUpdate = 'UPDATE Usuario SET senha = ? WHERE id_usuario = ?';
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param('si', $senha_hash, $id_usuario_token);

            if ($stmtUpdate->execute()) {
                // Marcar token como usado
                $sqlToken = 'UPDATE RecuperacaoSenha SET usado = TRUE WHERE token = ?';
                $stmtToken = $conn->prepare($sqlToken);
                $stmtToken->bind_param('s', $token);
                $stmtToken->execute();

                $mensagem_sucesso = '✅ Senha redefinida com sucesso!<br><br>';
                $mensagem_sucesso .= '<a href="php/login.php" style="color: #007bff; text-decoration: none;">🔐 Fazer login com a nova senha</a>';
            } else {
                $mensagem_erro = 'Erro ao atualizar senha. Tente novamente.';
            }
        } else {
            $mensagem_erro = 'Token inválido ou expirado.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinir senha - SiSGEH</title>
<link rel="stylesheet" type="text/css" href="css/estilo_recuperar_senha.css">
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

 <img src="logo.png" class="logo">

<div class="layoutRecuperar_Senha">
    <div class="recuperar_senha">
        <div class="mensagem">
            <h1>Redefinir senha</h1>
            <?php if ($token_valido): ?>
                <p>Olá, <?php echo htmlspecialchars($nome_usuario); ?>! Defina sua nova senha abaixo.</p>
            <?php else: ?>
                <p>Insira uma nova senha segura para sua conta.</p>
            <?php endif; ?>
        </div>

        <?php if ($mensagem_sucesso): ?>
            <div class="status-mensagem sucesso"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
            <div class="status-mensagem erro"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <?php if ($token_valido && !$mensagem_sucesso): ?>
            <form action="redefinir_senha.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label for="senha">NOVA SENHA</label>
                <input type="password" id="senha" name="senha" placeholder="Digite a nova senha" minlength="6" required>

                <label for="confirmar_senha">CONFIRMAR SENHA</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme a nova senha" minlength="6" required>

                <input type="submit" value="Redefinir Senha">
            </form>
        <?php elseif (!$mensagem_sucesso): ?>
            <div class="status-mensagem erro">Link inválido ou expirado. Solicite um novo link de recuperação.</div>
            <br>
            <a href="recuperar_senha.php" style="color: #007bff; text-decoration: none;">← Solicitar novo link</a>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>