<?php
require_once __DIR__ . '/sessao.php';
include('error_handler.php');
require_once('seguranca.php');
include 'conexao.php';

// Verificar se usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = (int) $_SESSION['id_usuario'];
$csrf_token = gerarTokenCSRF();

// Bancos criados antes do modo daltonismo nao tem a coluna: cria na hora
$colunaDaltonismo = $conn->query("SHOW COLUMNS FROM UsuarioConfiguracoes LIKE 'modo_daltonismo'");
if ($colunaDaltonismo && $colunaDaltonismo->num_rows === 0) {
    $conn->query("ALTER TABLE UsuarioConfiguracoes ADD COLUMN modo_daltonismo BOOLEAN DEFAULT false");
}

// Buscar configurações atuais do usuário
$configuracoes = [];
$stmt = $conn->prepare("SELECT * FROM UsuarioConfiguracoes WHERE id_usuario = ?");
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $configuracoes = $result->fetch_assoc();
} else {
    // Se não existir configuração, criar uma padrão
    $stmt = $conn->prepare("INSERT INTO UsuarioConfiguracoes (id_usuario, tema, notificacoes_email, notificacoes_sistema, notificacoes_simulacao, notificacoes_relatorios)
            VALUES (?, 'claro', 1, 1, 1, 1)");
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $configuracoes = [
        'tema' => 'claro',
        'notificacoes_email' => 1,
        'notificacoes_sistema' => 1,
        'notificacoes_simulacao' => 1,
        'notificacoes_relatorios' => 1,
        'modo_daltonismo' => 0
    ];
}

// Processar formulário de configurações do sistema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logTentativaSuspeita('csrf_fail_config_sistema', ['id_usuario' => $id_usuario]);
        $_SESSION['mensagem_erro'] = 'Token de segurança inválido. Tente novamente.';
        header('Location: configuracoes_sistema.php');
        exit();
    }

    // Só aceita os temas que existem na coluna ENUM
    $tema = in_array($_POST['tema'] ?? '', ['claro', 'escuro'], true) ? $_POST['tema'] : 'claro';
    $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
    $notificacoes_sistema = isset($_POST['notificacoes_sistema']) ? 1 : 0;
    $notificacoes_simulacao = isset($_POST['notificacoes_simulacao']) ? 1 : 0;
    $notificacoes_relatorios = isset($_POST['notificacoes_relatorios']) ? 1 : 0;
    $modo_daltonismo = isset($_POST['modo_daltonismo']) ? 1 : 0;

    // Atualizar configurações no banco
    $stmt = $conn->prepare("UPDATE UsuarioConfiguracoes SET
            tema = ?,
            notificacoes_email = ?,
            notificacoes_sistema = ?,
            notificacoes_simulacao = ?,
            notificacoes_relatorios = ?,
            modo_daltonismo = ?
            WHERE id_usuario = ?");
    $stmt->bind_param('siiiiii', $tema, $notificacoes_email, $notificacoes_sistema, $notificacoes_simulacao, $notificacoes_relatorios, $modo_daltonismo, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Configurações do sistema salvas com sucesso!';
        $_SESSION['tema'] = $tema; // menu lateral aplica o tema a partir da sessao
        $_SESSION['daltonismo'] = (bool) $modo_daltonismo;
        // Atualizar array de configurações
        $configuracoes['tema'] = $tema;
        $configuracoes['notificacoes_email'] = $notificacoes_email;
        $configuracoes['notificacoes_sistema'] = $notificacoes_sistema;
        $configuracoes['notificacoes_simulacao'] = $notificacoes_simulacao;
        $configuracoes['notificacoes_relatorios'] = $notificacoes_relatorios;
    } else {
        logCustom('ERROR', 'Falha ao salvar configurações do sistema', ['id_usuario' => $id_usuario, 'erro' => $stmt->error]);
        $_SESSION['mensagem_erro'] = 'Erro ao salvar configurações. Tente novamente.';
    }

    // Redirecionar para evitar reenvio do formulário
    header('Location: configuracoes_sistema.php');
    exit();
}
$paginaAtiva = 'configuracoes';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/sidebar.css?v=9">
    <link rel="stylesheet" href="../css/style.css?v=config2026">
    <link rel="stylesheet" href="../css/components/botoes.css">
    <link rel="stylesheet" href="../css/components/card.css">
    <link rel="stylesheet" href="../css/components/escuro.css">
    <link rel="stylesheet" href="../css/components/daltonismo.css">
    <link rel="stylesheet" href="../css/components/contraste.css?v=2">
</head>
<body>
    <?php include('includes/sidebar.php'); ?>

    <main class="com-sidebar">
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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
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
                                <input type="checkbox" name="notificacoes_sistema" id="notificacoes_sistema"
                                    <?php echo ($configuracoes['notificacoes_sistema']) ? 'checked' : ''; ?>>
                                <label for="notificacoes_sistema">🔔 Notificações do sistema</label>
                            </div>
                        
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Acessibilidade:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="modo_daltonismo" id="modo_daltonismo"
                                    <?php echo !empty($configuracoes['modo_daltonismo']) ? 'checked' : ''; ?>>
                                <label for="modo_daltonismo">👁️ Modo daltonismo (vermelho-verde)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="botao-generico">💾 Salvar Configurações</button>
                </form>

                <a href="configuracoes.php" class="botao-cinza">← Voltar</a>
            </section>
        </div>
    </main>

    <footer class="com-sidebar">
        <p>&copy; Todos os direitos reservados. <a href="../sobre.html">Sobre o sistema</a> · <a href="../politica.html">Políticas de privacidade</a></p>
    </footer>
</body>
</html>
