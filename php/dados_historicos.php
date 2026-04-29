<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['id_usuario'];
$nomeUsuario = $_SESSION['nomeUsuario'];

// Garante existência da tabela
$conn->query("CREATE TABLE IF NOT EXISTS DadosHistoricos (
    id_dado INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    pluviosidade_mm DECIMAL(10,2) NOT NULL,
    potencia_mw DECIMAL(10,2) NOT NULL
)");

$mensagem = '';
$erro = '';
$modoEdicao = false;
$registro = [
    'id_dado' => null,
    'data_registro' => '',
    'pluviosidade_mm' => '',
    'potencia_mw' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Token inválido. Recarregue a página e tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? 'salvar';
        $id_dado = isset($_POST['id_dado']) ? intval($_POST['id_dado']) : null;
        $dataRegistro = sanitizeInput($_POST['data_registro'] ?? '');
        $pluviosidade = sanitizeInput($_POST['pluviosidade_mm'] ?? '');
        $potencia = sanitizeInput($_POST['potencia_mw'] ?? '');

        if ($acao === 'excluir' && $id_dado) {
            $stmt = $conn->prepare('DELETE FROM DadosHistoricos WHERE id_dado = ?');
            $stmt->bind_param('i', $id_dado);
            if ($stmt->execute()) {
                $mensagem = 'Registro excluído com sucesso.';
            } else {
                $erro = 'Falha ao excluir o registro.';
            }
            $stmt->close();
        } else {
            if (!validarData($dataRegistro)) {
                $erro = 'Data inválida. Use o formato YYYY-MM-DD.';
            } elseif (!validarNumero($pluviosidade)) {
                $erro = 'Pluviosidade deve ser um número válido.';
            } elseif (!validarNumero($potencia)) {
                $erro = 'Potência deve ser um número válido.';
            } else {
                $pluviosidade = floatval($pluviosidade);
                $potencia = floatval($potencia);

                if ($id_dado) {
                    $stmt = $conn->prepare('UPDATE DadosHistoricos SET data_registro = ?, pluviosidade_mm = ?, potencia_mw = ? WHERE id_dado = ?');
                    $stmt->bind_param('sddi', $dataRegistro, $pluviosidade, $potencia, $id_dado);
                    if ($stmt->execute()) {
                        $mensagem = 'Registro atualizado com sucesso.';
                    } else {
                        $erro = 'Falha ao atualizar o registro.';
                    }
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare('INSERT INTO DadosHistoricos (data_registro, pluviosidade_mm, potencia_mw) VALUES (?, ?, ?)');
                    $stmt->bind_param('sdd', $dataRegistro, $pluviosidade, $potencia);
                    if ($stmt->execute()) {
                        $mensagem = 'Registro adicionado com sucesso.';
                    } else {
                        $erro = 'Falha ao adicionar o registro.';
                    }
                    $stmt->close();
                }
            }
        }
    }
    header('Location: dados_historicos.php?mensagem=' . urlencode($mensagem) . '&erro=' . urlencode($erro));
    exit;
}

if (isset($_GET['editar'])) {
    $idEdicao = intval($_GET['editar']);
    $stmt = $conn->prepare('SELECT id_dado, data_registro, pluviosidade_mm, potencia_mw FROM DadosHistoricos WHERE id_dado = ?');
    $stmt->bind_param('i', $idEdicao);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $registro = $resultado->fetch_assoc();
        $modoEdicao = true;
    }
    $stmt->close();
}

if (isset($_GET['mensagem'])) {
    $mensagem = sanitizeInput($_GET['mensagem']);
}
if (isset($_GET['erro'])) {
    $erro = sanitizeInput($_GET['erro']);
}

$result = $conn->query('SELECT id_dado, data_registro, pluviosidade_mm, potencia_mw FROM DadosHistoricos ORDER BY data_registro DESC');
$registros = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$csrfToken = gerarTokenCSRF();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados Históricos - SiSGEH</title>
    <link rel="stylesheet" href="../css/estilo_analise_preditiva.css">
</head>
<body>
<header>
    <div class="cabecalho">
        <h1>SiSGEH</h1>
        <p>Módulo: Dados Históricos para Análise Preditiva</p>
    </div>
</header>
<main class="container">
    <section class="intro">
        <div>
            <h2>Gerenciar Dados Históricos</h2>
            <p>Cadastre e edite registros de chuva e potência usados pelo módulo preditivo.</p>
        </div>
        <div>
            <a class="botao-voltar" href="analise_preditiva.php">← Voltar à Análise Preditiva</a>
        </div>
    </section>

    <?php if ($mensagem): ?>
        <div class="resultado-card" style="background:#ecfdf5; border-color:#86efac; color:#166534; margin-bottom:18px;">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alerta erro" style="margin-bottom:18px;">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <section class="painel-entrada">
        <h3><?= $modoEdicao ? 'Editar registro' : 'Adicionar novo registro' ?></h3>
        <form method="POST" action="dados_historicos.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id_dado" value="<?= htmlspecialchars($registro['id_dado']) ?>">
            <input type="hidden" name="acao" value="salvar">

            <label for="data_registro">Data</label>
            <input type="date" id="data_registro" name="data_registro" required value="<?= htmlspecialchars($registro['data_registro']) ?>">

            <label for="pluviosidade_mm">Pluviosidade (mm)</label>
            <input type="number" step="0.01" id="pluviosidade_mm" name="pluviosidade_mm" required value="<?= htmlspecialchars($registro['pluviosidade_mm']) ?>">

            <label for="potencia_mw">Potência (MW)</label>
            <input type="number" step="0.01" id="potencia_mw" name="potencia_mw" required value="<?= htmlspecialchars($registro['potencia_mw']) ?>">

            <button type="submit"><?= $modoEdicao ? 'Atualizar registro' : 'Adicionar registro' ?></button>
        </form>
    </section>

    <section class="dados-historicos">
        <h3>Registros Existentes</h3>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Pluviosidade (mm)</th>
                    <th>Potência (MW)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="4">Nenhum registro encontrado.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($registros as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['data_registro']) ?></td>
                        <td><?= htmlspecialchars(number_format($item['pluviosidade_mm'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars(number_format($item['potencia_mw'], 2, ',', '.')) ?></td>
                        <td>
                            <a class="btn-acao" href="dados_historicos.php?editar=<?= $item['id_dado'] ?>">Editar</a>
                            <form method="POST" action="dados_historicos.php" style="display:inline-block; margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id_dado" value="<?= $item['id_dado'] ?>">
                                <input type="hidden" name="acao" value="excluir">
                                <button type="submit" class="btn-acao-excluir" onclick="return confirm('Excluir este registro?');">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
