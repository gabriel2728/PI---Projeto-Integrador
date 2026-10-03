<?php
require_once __DIR__ . '/sessao.php';
include('error_handler.php');
require_once('seguranca.php');
include('conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['id_usuario'];
$nomeUsuario = $_SESSION['nomeUsuario'];

// Garante a tabela e entrega ao usuário a cópia pessoal da base original na primeira vez
require_once __DIR__ . '/dados_historicos_tabela.php';
garantirTabelaDadosHistoricos($conn);
garantirDadosDoUsuario($conn, $usuarioId);

function registroDoUsuario(mysqli $conn, $idDado, $usuarioId) {
    $stmt = $conn->prepare('SELECT 1 FROM DadosHistoricos WHERE id_dado = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $idDado, $usuarioId);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $existe;
}

$mensagem = '';
$erro = '';
$modoEdicao = false;
$registro = [
    'id_dado' => null,
    'data_registro' => '',
    'pluviosidade_mm' => '',
    'potencia_mw' => '',
    'fonte' => ''
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
        $fonte = trim(sanitizeInput($_POST['fonte'] ?? ''));
        $fonte = $fonte === '' ? null : $fonte;

        if ($acao === 'restaurar') {
            restaurarBaseOriginal($conn, $usuarioId);
            $mensagem = 'Base original restaurada com sucesso.';
        } elseif ($acao === 'excluir' && $id_dado) {
            // Só exclui registros da base pessoal do usuário; a base original e a dos outros não são afetadas
            $stmt = $conn->prepare('DELETE FROM DadosHistoricos WHERE id_dado = ? AND id_usuario = ?');
            $stmt->bind_param('ii', $id_dado, $usuarioId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensagem = 'Registro excluído com sucesso.';
            } elseif ($stmt->errno === 0) {
                logTentativaSuspeita('tentativa_excluir_dado_historico_alheio', ['id_usuario' => $usuarioId, 'id_dado' => $id_dado]);
                $erro = 'Registro não encontrado.';
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
                    $stmt = $conn->prepare('UPDATE DadosHistoricos SET data_registro = ?, pluviosidade_mm = ?, potencia_mw = ?, fonte = ? WHERE id_dado = ? AND id_usuario = ?');
                    $stmt->bind_param('sddsii', $dataRegistro, $pluviosidade, $potencia, $fonte, $id_dado, $usuarioId);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $mensagem = 'Registro atualizado com sucesso.';
                    } elseif ($stmt->errno === 0 && !registroDoUsuario($conn, $id_dado, $usuarioId)) {
                        logTentativaSuspeita('tentativa_editar_dado_historico_alheio', ['id_usuario' => $usuarioId, 'id_dado' => $id_dado]);
                        $erro = 'Registro não encontrado.';
                    } elseif ($stmt->errno === 0) {
                        $mensagem = 'Nenhuma alteração realizada.';
                    } else {
                        $erro = 'Falha ao atualizar o registro.';
                    }
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare('INSERT INTO DadosHistoricos (data_registro, pluviosidade_mm, potencia_mw, fonte, id_usuario) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sddsi', $dataRegistro, $pluviosidade, $potencia, $fonte, $usuarioId);
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
    $stmt = $conn->prepare('SELECT id_dado, data_registro, pluviosidade_mm, potencia_mw, fonte FROM DadosHistoricos WHERE id_dado = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $idEdicao, $usuarioId);
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

// Mostra só a base pessoal do usuário
$stmt = $conn->prepare('SELECT id_dado, data_registro, pluviosidade_mm, potencia_mw, fonte FROM DadosHistoricos WHERE id_usuario = ? ORDER BY data_registro DESC');
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrfToken = gerarTokenCSRF();
$paginaAtiva = 'analise_preditiva';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados Históricos - SiSGEH</title>
    <link rel="stylesheet" href="../css/historico.css?v=20260511-layout">
    <link rel="stylesheet" href="../css/components/botoes.css">
    <link rel="stylesheet" href="../css/components/sidebar.css?v=4">
    <link rel="stylesheet" href="../css/analise_preditiva.css?v=gotas2026">
</head>
<body>
<?php include('includes/sidebar.php'); ?>

<main class="container com-sidebar">
	<div class="layout">
    		<section class="intro">
        		<div class="mensagem-pequena">
            			<h2>Gerenciar Dados Históricos</h2>
            			<p>Esta é a sua base pessoal, iniciada com dados reais do INMET e do ONS. Você pode editar, excluir e incluir registros para treinar o modelo como quiser, sem afetar a base dos outros usuários.</p>
        		</div>
        
            		<a class="botao-cinza" href="analise_preditiva.php">← Voltar à Análise Preditiva</a>   
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
            			<input type="number" step="0.01" id="pluviosidade_mm" placeholder="180" name="pluviosidade_mm" required value="<?= htmlspecialchars($registro['pluviosidade_mm']) ?>">

            			<label for="potencia_mw">Potência (MW)</label>
            			<input type="number" step="0.01" id="potencia_mw" placeholder="60" name="potencia_mw" required value="<?= htmlspecialchars($registro['potencia_mw']) ?>">

            			<label for="fonte">Fonte (opcional)</label>
            			<input type="text" id="fonte" placeholder="Ex: INMET, estação X, 2025 (link)" name="fonte" value="<?= htmlspecialchars($registro['fonte'] ?? '') ?>">

            			<button type="submit" class="botao-generico"><?= $modoEdicao ? 'Atualizar registro' : 'Adicionar registro' ?></button>
        		</form>
    		</section>

    		<section class="dados-historicos">
        		<h3>Registros Existentes</h3>
        		<form method="POST" action="dados_historicos.php" style="margin-bottom:12px;">
            			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            			<input type="hidden" name="acao" value="restaurar">
            			<button type="submit" class="botao-cinza" onclick="return confirm('Isso apaga todos os seus registros e coloca de novo a base original (INMET/ONS). Deseja continuar?');">↺ Restaurar base original</button>
        		</form>
        		<table>
            			<thead>
                			<tr>
                    				<th>Data</th>
                    				<th>Pluviosidade (mm)</th>
                    				<th>Potência (MW)</th>
                    				<th>Fonte</th>
                    				<th>Ações</th>
                			</tr>
            			</thead>
            			<tbody>
                			<?php if (empty($registros)): ?>
                    			<tr>
                        			<td colspan="5">Nenhum registro encontrado.</td>
                    			</tr>
                			<?php endif; ?>
                			<?php foreach ($registros as $item): ?>
                    			<tr>
                        			<td><?= htmlspecialchars($item['data_registro']) ?></td>
                        			<td><?= htmlspecialchars(number_format($item['pluviosidade_mm'], 2, ',', '.')) ?></td>
                        			<td><?= htmlspecialchars(number_format($item['potencia_mw'], 2, ',', '.')) ?></td>
                        			<td><?= $item['fonte'] ? htmlspecialchars($item['fonte']) : '<span class="nota">não informada</span>' ?></td>
                        			<td>
                                        <div class="acoes-tabela">
                                            <a class="botao-acao botao-acao-editar" href="dados_historicos.php?editar=<?= $item['id_dado'] ?>">Editar</a>
                                            <form method="POST" action="dados_historicos.php">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id_dado" value="<?= $item['id_dado'] ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <button type="submit" class="botao-acao botao-acao-excluir" onclick="return confirm('Excluir este registro?');">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
					</tr>
                    			
                			<?php endforeach; ?>
            			</tbody>
        		</table>
    		</section>
	</div>
</main>
<script src="../js/pesquisa.js"></script>
</body>
</html>
