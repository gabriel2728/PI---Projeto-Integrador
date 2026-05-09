<?php
session_start();
include('php/error_handler.php');
include('php/seguranca.php');

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
    // Rate limiting para redefinição de senha (máximo 5 tentativas por 15 minutos)
    if (!rateLimitCheck('redefinir_senha', 5, 900)) {
        $mensagem_erro = 'Muitas tentativas. Tente novamente em 15 minutos.';
    } else {
        $token = sanitizeInput(trim($_POST['token'] ?? ''));
        $senha1 = sanitizeInput($_POST['senha'] ?? '');
        $senha2 = sanitizeInput($_POST['confirmar_senha'] ?? '');

        if (empty($token) || empty($senha1) || empty($senha2)) {
            $mensagem_erro = 'Preencha todos os campos.';
            logTentativaSuspeita('campo_vazio_redefinir_senha', ['ip' => $_SERVER['REMOTE_ADDR']]);
        } elseif ($senha1 !== $senha2) {
            $mensagem_erro = 'As senhas não coincidem.';
            logTentativaSuspeita('senhas_nao_coincidem_redefinir', ['ip' => $_SERVER['REMOTE_ADDR']]);
        } elseif (!validarSenha($senha1)) {
            $mensagem_erro = 'A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula e número.';
            logTentativaSuspeita('senha_invalida_redefinir', ['ip' => $_SERVER['REMOTE_ADDR']]);
        } else {
            include 'php/conexao.php';

            // Verificar token novamente
            $sql = 'SELECT id_usuario FROM RecuperacaoSenha
                    WHERE token = ? AND usado = FALSE AND data_expiracao > NOW()';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                logTentativaSuspeita('erro_sql_redefinir_senha', ['ip' => $_SERVER['REMOTE_ADDR']]);
                $mensagem_erro = 'Erro no sistema. Tente novamente.';
            } else {
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
                        logAuditoria('senha_redefinida_sucesso', ['id_usuario' => $id_usuario_token]);
                    } else {
                        $mensagem_erro = 'Erro ao atualizar senha. Tente novamente.';
                        logTentativaSuspeita('erro_update_senha', ['id_usuario' => $id_usuario_token]);
                    }
                } else {
                    $mensagem_erro = 'Token inválido ou expirado.';
                    logTentativaSuspeita('token_invalido_redefinir', ['ip' => $_SERVER['REMOTE_ADDR']]);
                }
            }
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
<link rel="stylesheet" href="css/components/header.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/recuperar_senha.css">
<link rel="stylesheet" href="css/components/botoes.css">
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
                <a href="sobre.html" class="sobre"> Sobre </a>

                <a href="index.html" class="link_home">
                    <img src="images/home.png" alt="Voltar a Home" class="home">
                </a>
            </li>
        </ul>
    </div>
</header>

<div class="layout">
    <section class="recuperar_senha">
        <div class="mensagem-pequena">
            <h2>Redefinir senha</h2>
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

                <input type="submit" value="Redefinir Senha" class="botao-cinza">
            </form>
        <?php elseif (!$mensagem_sucesso): ?>
            <div class="status-mensagem erro">Link inválido ou expirado. Solicite um novo link de recuperação.</div>
            <br>
            <a href="recuperar_senha.php" class="botao-generico">← Solicitar novo link</a>
        <?php endif; ?>
    </section>
</div>

<footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>