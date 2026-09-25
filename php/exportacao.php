<?php
// Limpar qualquer output anterior
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
include('error_handler.php');
include('conexao.php');
include('seguranca.php');

// Validar sessão
if (!isset($_SESSION['id_usuario'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Não autenticado']));
}

// Verifica se recebeu o formato de exportação
$formato = isset($_GET['formato']) ? sanitizeInput($_GET['formato']) : (isset($_POST['formato']) ? sanitizeInput($_POST['formato']) : null);
$id_simulacao = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : null);
$id_analise = $id_simulacao;
$tipo = isset($_GET['tipo']) ? sanitizeInput($_GET['tipo']) : (isset($_POST['tipo']) ? sanitizeInput($_POST['tipo']) : 'salvo');

// Validar formato
if (!in_array($formato, ['pdf', 'csv', 'xlsx'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Formato inválido']));
}

// Validar tipo
if (!in_array($tipo, ['novo', 'salvo', 'analise', 'cenario'])) {
    logTentativaSuspeita('invalid_export_type', ['tipo' => $tipo, 'id_usuario' => $_SESSION['id_usuario']]);
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Tipo de exportação inválido']));
}

// Validar que tipo 'salvo' tem id_simulacao
if ($tipo === 'salvo' && ($id_simulacao === null || $id_simulacao <= 0)) {
    logTentativaSuspeita('missing_simulation_id', ['tipo' => $tipo, 'id_usuario' => $_SESSION['id_usuario']]);
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'ID da simulação inválido']));
}

if ($tipo === 'analise' && ($id_analise === null || $id_analise <= 0)) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'ID da análise inválido']));
}

// Se é uma simulação nova (não salva ainda)
if ($tipo === 'novo') {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Dados incompletos']));
    }
    
    ob_end_clean();
    exportarSimulacaoNova($dados, $formato);
}
// Se é uma comparação de cenários (nunca fica salva no banco)
elseif ($tipo === 'cenario') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!$dados || empty($dados['resultados']) || !is_array($dados['resultados'])) {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Dados de cenários incompletos']));
    }

    ob_end_clean();
    exportarCenarios($dados, $formato);
}
// Se é uma simulação salva no banco
elseif ($tipo === 'salvo' && $id_simulacao) {
    $stmt = $conn->prepare("
        SELECT s.id_simulacao, s.data_simulacao, s.vazao, s.altura, s.potTurbina, s.qtdTurbinas, s.potGerador, s.eficiencia, s.horas,
               r.geracao_principal, r.geracao_diaria, r.geracao_mensal, r.geracao_anual
        FROM Simulacoes s
        LEFT JOIN ResultadoSimulacao r ON r.id_simulacao = s.id_simulacao
        WHERE s.id_simulacao = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $id_simulacao);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();
        $stmt->close();
        
        if ($dados) {
            ob_end_clean();
            exportarSimulacaoSalva($dados, $formato);
        } else {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Simulação não encontrada']));
        }
    } else {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $conn->error]));
    }
} elseif ($tipo === 'analise' && $id_analise) {
    $stmt = $conn->prepare("SELECT id_analise_preditiva, id_usuario, periodo, data_calculo, pluviosidade_informada, potencia_estimada, modelo, equacao, status FROM AnalisePreditiva WHERE id_analise_preditiva = ? AND id_usuario = ? AND status = 'concluido'");
    if ($stmt) {
        $stmt->bind_param("ii", $id_analise, $_SESSION['id_usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();
        $stmt->close();

        if ($dados) {
            ob_end_clean();
            exportarAnalisePreditiva($dados, $formato);
        } else {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Análise preditiva não encontrada']));
        }
    } else {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $conn->error]));
    }
} else {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Parâmetros inválidos']));
}

// ===== FUNÇÕES DE EXPORTAÇÃO =====

function exportarSimulacaoNova($dados, $formato) {
    $timestamp = date('Y-m-d_H-i-s');
    
    if ($formato === 'pdf') {
        exportarPDFNovo($dados, $timestamp);
    } elseif ($formato === 'csv') {
        exportarCSVNovo($dados, $timestamp);
    } elseif ($formato === 'xlsx') {
        exportarXLSXNovo($dados, $timestamp);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato inválido']);
    }
}

function exportarSimulacaoSalva($dados, $formato) {
    $id = $dados['id_simulacao'];
    
    // Se resultados vêm NULL do banco, recalcular
    if (is_null($dados['geracao_principal']) || $dados['geracao_principal'] === '') {
        $dados['geracao_principal'] = $dados['eficiencia'] * 1000 * $dados['vazao'] * 9.81 * $dados['altura'] * $dados['qtdTurbinas'] / 1e6;
    }
    if (is_null($dados['geracao_diaria']) || $dados['geracao_diaria'] === '') {
        $dados['geracao_diaria'] = $dados['horas'] > 0 ? $dados['geracao_principal'] * $dados['horas'] : 0;
    }
    if (is_null($dados['geracao_mensal']) || $dados['geracao_mensal'] === '') {
        $dados['geracao_mensal'] = $dados['horas'] > 0 ? $dados['geracao_diaria'] * 30 : 0;
    }
    if (is_null($dados['geracao_anual']) || $dados['geracao_anual'] === '') {
        $dados['geracao_anual'] = $dados['horas'] > 0 ? $dados['geracao_diaria'] * 365 : 0;
    }
    
    if ($formato === 'pdf') {
        exportarPDFSalvo($dados);
    } elseif ($formato === 'csv') {
        exportarCSVSalvo($dados);
    } elseif ($formato === 'xlsx') {
        exportarXLSXSalvo($dados);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato inválido']);
    }
}

function exportarAnalisePreditiva($dados, $formato) {
    if ($formato === 'csv') {
        exportarCSVAnalisePreditiva($dados);
    } elseif ($formato === 'pdf') {
        exportarPDFAnalisePreditiva($dados);
    } elseif ($formato === 'xlsx') {
        exportarXLSXAnalisePreditiva($dados);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato inválido']);
    }
}

function exportarCSVAnalisePreditiva($dados) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="AnalisePreditiva_' . $dados['id_analise_preditiva'] . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8

    fputcsv($output, ['Campo', 'Valor'], ';');
    fputcsv($output, ['Data do cálculo', date('d/m/Y H:i', strtotime($dados['data_calculo']))], ';');
    fputcsv($output, ['Período', $dados['periodo'] ?: '-'], ';');
    fputcsv($output, ['Pluviosidade informada', number_format(floatval($dados['pluviosidade_informada']), 2, ',', '.') . ' mm'], ';');
    fputcsv($output, ['Potência estimada', number_format(floatval($dados['potencia_estimada']), 2, ',', '.') . ' MW'], ';');
    fputcsv($output, ['Modelo', $dados['modelo']], ';');
    fputcsv($output, ['Equação', $dados['equacao'] ?: '-'], ';');
    fclose($output);
    exit;
}

function exportarXLSXAnalisePreditiva($dados) {
    $zipPath = tempnam(sys_get_temp_dir(), 'xlsx');

    $conteudo = construirXMLAnalisePreditiva($dados);
    criarXLSX($zipPath, $conteudo);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="AnalisePreditiva_' . $dados['id_analise_preditiva'] . '.xlsx"');
    header('Content-Length: ' . filesize($zipPath));

    readfile($zipPath);
    unlink($zipPath);
    exit;
}

function construirXMLAnalisePreditiva($dados) {
    $linhas = [];
    $linhas[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $linhas[] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $linhas[] = '<dimension ref="A1:B8"/>';
    $linhas[] = '<cols>';
    $linhas[] = '<col min="1" max="1" width="30" customWidth="1"/>';
    $linhas[] = '<col min="2" max="2" width="35" customWidth="1"/>';
    $linhas[] = '</cols>';
    $linhas[] = '<sheetData>';

    $row = 1;
    $linhas[] = '<row r="' . $row . '"><c r="A' . $row . '" t="str"><v>ANÁLISE PREDITIVA</v></c></row>';
    $row++;
    $row++; // linha em branco

    $campos = [
        'Data do cálculo' => date('d/m/Y H:i', strtotime($dados['data_calculo'])),
        'Período' => $dados['periodo'] ?: '-',
        'Pluviosidade informada (mm)' => number_format(floatval($dados['pluviosidade_informada']), 2, ',', '.'),
        'Potência estimada (MW)' => number_format(floatval($dados['potencia_estimada']), 2, ',', '.'),
        'Modelo' => $dados['modelo'],
        'Equação' => $dados['equacao'] ?: '-',
    ];

    foreach ($campos as $label => $valor) {
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>' . htmlspecialchars($label) . '</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="str"><v>' . htmlspecialchars((string) $valor) . '</v></c>';
        $linhas[] = '</row>';
        $row++;
    }

    $linhas[] = '</sheetData>';
    $linhas[] = '</worksheet>';

    return implode('', $linhas);
}

function exportarPDFAnalisePreditiva($dados) {
    require('fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, textoPDF('Relatório de Análise Preditiva'), 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $campos = [
        'Período' => $dados['periodo'] ?: '-',
        'Pluviosidade Informada' => number_format(floatval($dados['pluviosidade_informada']), 2, ',', '.') . ' mm',
        'Potência Estimada' => number_format(floatval($dados['potencia_estimada']), 2, ',', '.') . ' MW',
        'Modelo' => $dados['modelo'],
        'Equação' => $dados['equacao'] ?: '-',
    ];
    foreach ($campos as $label => $valor) {
        $pdf->Cell(60, 10, textoPDF($label . ':'), 0, 0);
        $pdf->Cell(0, 10, textoPDF($valor), 0, 1);
    }
    $pdf->Output('D', 'AnalisePreditiva_' . $dados['id_analise_preditiva'] . '.pdf');
    exit;
}

// ===== EXPORTAÇÃO PDF =====

function textoPDF($texto) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $texto);
}

function exportarPDFNovo($dados, $timestamp) {
    require('fpdf.php');
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, textoPDF('Relatório de Simulação Hidrelétrica'), 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', '', 12);
    $campos = [
        'Vazão Volumétrica' => $dados['vazao'] . ' m³/s',
        'Altura da Queda' => $dados['altura'] . ' m',
        'Potência da Turbina' => $dados['potTurbina'] . ' MW',
        'Quantidade de Turbinas' => $dados['qtdTurbinas'],
        'Potência do Gerador' => $dados['potGerador'] . ' MW',
        'Eficiência do Sistema' => ($dados['eficiencia'] * 100) . ' %',
        'Horas de Operação/dia' => $dados['horas'] . ' h/dia',
    ];
    
    foreach ($campos as $label => $valor) {
        $pdf->Cell(60, 10, textoPDF($label . ':'), 0, 0);
        $pdf->Cell(0, 10, textoPDF($valor), 0, 1);
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, textoPDF('Resultados:'), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $resultadoPrincipal = $dados['eficiencia'] * 1000 * $dados['vazao'] * 9.81 * $dados['altura'] * $dados['qtdTurbinas'] / 1e6;
    $geracaoDia = $dados['horas'] > 0 ? $resultadoPrincipal * $dados['horas'] : 0;
    $geracaoMes = $dados['horas'] > 0 ? $geracaoDia * 30 : 0;
    $geracaoAno = $dados['horas'] > 0 ? $geracaoDia * 365 : 0;
    
    $pdf->Cell(60, 10, textoPDF('Geração Total (MW):'), 0, 0);
    $pdf->Cell(0, 10, number_format($resultadoPrincipal, 2, ',', '.'), 0, 1);
    
    if ($dados['horas'] > 0) {
        $pdf->Cell(60, 10, textoPDF('Geração Diária (MWh/dia):'), 0, 0);
        $pdf->Cell(0, 10, number_format($geracaoDia, 2, ',', '.'), 0, 1);
        $pdf->Cell(60, 10, textoPDF('Geração Mensal (MWh/mês):'), 0, 0);
        $pdf->Cell(0, 10, number_format($geracaoMes, 2, ',', '.'), 0, 1);
        $pdf->Cell(60, 10, textoPDF('Geração Anual (MWh/ano):'), 0, 0);
        $pdf->Cell(0, 10, number_format($geracaoAno, 2, ',', '.'), 0, 1);
    }
    
    $pdf->Output('D', 'Simulacao_' . $timestamp . '.pdf');
    exit;
}

function exportarPDFSalvo($dados) {
    require('fpdf.php');
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, textoPDF('Relatório de Simulação Hidrelétrica'), 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 10);
    $dataFormatada = date('d/m/Y H:i', strtotime($dados['data_simulacao']));
    $pdf->Cell(0, 8, textoPDF('Data da Simulação: ' . $dataFormatada), 0, 1);
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $campos = [
        'Vazão Volumétrica' => $dados['vazao'] . ' m³/s',
        'Altura da Queda' => $dados['altura'] . ' m',
        'Potência da Turbina' => $dados['potTurbina'] . ' MW',
        'Quantidade de Turbinas' => $dados['qtdTurbinas'],
        'Potência do Gerador' => $dados['potGerador'] . ' MW',
        'Eficiência do Sistema' => ($dados['eficiencia'] * 100) . ' %',
        'Horas de Operação/dia' => $dados['horas'] . ' h/dia',
    ];
    
    foreach ($campos as $label => $valor) {
        $pdf->Cell(60, 10, textoPDF($label . ':'), 0, 0);
        $pdf->Cell(0, 10, textoPDF($valor), 0, 1);
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, textoPDF('Resultados:'), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(60, 10, textoPDF('Geração Total (MW):'), 0, 0);
    $pdf->Cell(0, 10, number_format($dados['geracao_principal'], 2, ',', '.'), 0, 1);
    $pdf->Cell(60, 10, textoPDF('Geração Diária (MWh/dia):'), 0, 0);
    $pdf->Cell(0, 10, number_format($dados['geracao_diaria'], 2, ',', '.'), 0, 1);
    $pdf->Cell(60, 10, textoPDF('Geração Mensal (MWh/mês):'), 0, 0);
    $pdf->Cell(0, 10, number_format($dados['geracao_mensal'], 2, ',', '.'), 0, 1);
    $pdf->Cell(60, 10, textoPDF('Geração Anual (MWh/ano):'), 0, 0);
    $pdf->Cell(0, 10, number_format($dados['geracao_anual'], 2, ',', '.'), 0, 1);
    
    $nomearquivo = 'Simulacao_' . $dados['id_simulacao'] . '_' . date('Y-m-d_H-i-s', strtotime($dados['data_simulacao'])) . '.pdf';
    $pdf->Output('D', $nomearquivo);
    exit;
}

// ===== EXPORTAÇÃO CSV =====

function exportarCSVNovo($dados, $timestamp) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Simulacao_' . $timestamp . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    fputcsv($output, ['SIMULAÇÃO HIDRELÉTRICA'], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['PARÂMETROS DE ENTRADA', ''], ';');
    fputcsv($output, ['Vazão Volumétrica (m³/s)', $dados['vazao']], ';');
    fputcsv($output, ['Altura da Queda (m)', $dados['altura']], ';');
    fputcsv($output, ['Potência da Turbina (MW)', $dados['potTurbina']], ';');
    fputcsv($output, ['Quantidade de Turbinas', $dados['qtdTurbinas']], ';');
    fputcsv($output, ['Potência do Gerador (MW)', $dados['potGerador']], ';');
    fputcsv($output, ['Eficiência do Sistema (%)', $dados['eficiencia'] * 100], ';');
    fputcsv($output, ['Horas de Operação/dia', $dados['horas']], ';');
    
    fputcsv($output, [], ';');
    fputcsv($output, ['RESULTADOS', ''], ';');
    
    $resultadoPrincipal = $dados['eficiencia'] * 1000 * $dados['vazao'] * 9.81 * $dados['altura'] * $dados['qtdTurbinas'] / 1e6;
    $geracaoDia = $dados['horas'] > 0 ? $resultadoPrincipal * $dados['horas'] : 0;
    $geracaoMes = $dados['horas'] > 0 ? $geracaoDia * 30 : 0;
    $geracaoAno = $dados['horas'] > 0 ? $geracaoDia * 365 : 0;
    
    fputcsv($output, ['Geração Total (MW)', number_format($resultadoPrincipal, 2, '.', '')], ';');
    if ($dados['horas'] > 0) {
        fputcsv($output, ['Geração Diária (MWh/dia)', number_format($geracaoDia, 2, '.', '')], ';');
        fputcsv($output, ['Geração Mensal (MWh/mês)', number_format($geracaoMes, 2, '.', '')], ';');
        fputcsv($output, ['Geração Anual (MWh/ano)', number_format($geracaoAno, 2, '.', '')], ';');
    }
    
    fclose($output);
    exit;
}

function exportarCSVSalvo($dados) {
    header('Content-Type: text/csv; charset=utf-8');
    $timestamp = date('Y-m-d_H-i-s', strtotime($dados['data_simulacao']));
    header('Content-Disposition: attachment; filename="Simulacao_' . $dados['id_simulacao'] . '_' . $timestamp . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    $dataFormatada = date('d/m/Y H:i', strtotime($dados['data_simulacao']));
    fputcsv($output, ['SIMULAÇÃO HIDRELÉTRICA'], ';');
    fputcsv($output, ['Data: ' . $dataFormatada], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['PARÂMETROS DE ENTRADA', ''], ';');
    fputcsv($output, ['Vazão Volumétrica (m³/s)', $dados['vazao']], ';');
    fputcsv($output, ['Altura da Queda (m)', $dados['altura']], ';');
    fputcsv($output, ['Potência da Turbina (MW)', $dados['potTurbina']], ';');
    fputcsv($output, ['Quantidade de Turbinas', $dados['qtdTurbinas']], ';');
    fputcsv($output, ['Potência do Gerador (MW)', $dados['potGerador']], ';');
    fputcsv($output, ['Eficiência do Sistema (%)', $dados['eficiencia'] * 100], ';');
    fputcsv($output, ['Horas de Operação/dia', $dados['horas']], ';');
    
    fputcsv($output, [], ';');
    fputcsv($output, ['RESULTADOS', ''], ';');
    fputcsv($output, ['Geração Total (MW)', $dados['geracao_principal']], ';');
    fputcsv($output, ['Geração Diária (MWh/dia)', $dados['geracao_diaria']], ';');
    fputcsv($output, ['Geração Mensal (MWh/mês)', $dados['geracao_mensal']], ';');
    fputcsv($output, ['Geração Anual (MWh/ano)', $dados['geracao_anual']], ';');
    
    fclose($output);
    exit;
}

// ===== EXPORTAÇÃO XLSX =====

function exportarXLSXNovo($dados, $timestamp) {
    $zipPath = tempnam(sys_get_temp_dir(), 'xlsx');
    
    $conteudo = construirXMLSimulacao(
        'SIMULAÇÃO HIDRELÉTRICA',
        null,
        $dados
    );
    
    criarXLSX($zipPath, $conteudo);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Simulacao_' . $timestamp . '.xlsx"');
    header('Content-Length: ' . filesize($zipPath));
    
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

function exportarXLSXSalvo($dados) {
    $zipPath = tempnam(sys_get_temp_dir(), 'xlsx');
    $dataFormatada = date('d/m/Y H:i', strtotime($dados['data_simulacao']));
    
    $conteudo = construirXMLSimulacao(
        'SIMULAÇÃO HIDRELÉTRICA',
        'Data: ' . $dataFormatada,
        $dados,
        true
    );
    
    criarXLSX($zipPath, $conteudo);
    
    $timestamp = date('Y-m-d_H-i-s', strtotime($dados['data_simulacao']));
    $nomearquivo = 'Simulacao_' . $dados['id_simulacao'] . '_' . $timestamp . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nomearquivo . '"');
    header('Content-Length: ' . filesize($zipPath));
    
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

function construirXMLSimulacao($titulo, $subtitulo, $dados, $isSalvo = false) {
    $linhas = [];
    $linhas[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $linhas[] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    
    // Contar o máximo de linhas que será utilizado
    $maxRow = 3 + 7 + 2 + 1 + ($isSalvo ? 4 : 5); // Aproximado
    $linhas[] = '<dimension ref="A1:B' . $maxRow . '"/>';
    
    // Largura das colunas
    $linhas[] = '<cols>';
    $linhas[] = '<col min="1" max="1" width="35" customWidth="1"/>';
    $linhas[] = '<col min="2" max="2" width="20" customWidth="1"/>';
    $linhas[] = '</cols>';
    
    $linhas[] = '<sheetData>';
    
    $row = 1;
    
    // Título
    $linhas[] = '<row r="' . $row . '">';
    $linhas[] = '<c r="A' . $row . '" t="str"><v>' . htmlspecialchars($titulo) . '</v></c>';
    $linhas[] = '</row>';
    $row++;
    
    // Subtítulo (se houver)
    if ($subtitulo) {
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>' . htmlspecialchars($subtitulo) . '</v></c>';
        $linhas[] = '</row>';
        $row++;
    }
    
    $row++; // Espaço em branco
    
    // Parâmetros de entrada
    $linhas[] = '<row r="' . $row . '">';
    $linhas[] = '<c r="A' . $row . '" t="str"><v>PARÂMETROS DE ENTRADA</v></c>';
    $linhas[] = '</row>';
    $row++;
    
    $campos = [
        'Vazão Volumétrica (m³/s)' => $dados['vazao'],
        'Altura da Queda (m)' => $dados['altura'],
        'Potência da Turbina (MW)' => $dados['potTurbina'],
        'Quantidade de Turbinas' => $dados['qtdTurbinas'],
        'Potência do Gerador (MW)' => $dados['potGerador'],
        'Eficiência do Sistema (%)' => $dados['eficiencia'] * 100,
        'Horas de Operação/dia' => $dados['horas'],
    ];
    
    foreach ($campos as $label => $valor) {
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>' . htmlspecialchars($label) . '</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $valor . '</v></c>';
        $linhas[] = '</row>';
        $row++;
    }
    
    $row++; // Espaço em branco
    
    // Resultados
    $linhas[] = '<row r="' . $row . '">';
    $linhas[] = '<c r="A' . $row . '" t="str"><v>RESULTADOS</v></c>';
    $linhas[] = '</row>';
    $row++;
    
    if ($isSalvo) {
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Total (MW)</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $dados['geracao_principal'] . '</v></c>';
        $linhas[] = '</row>';
        $row++;
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Diária (MWh/dia)</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $dados['geracao_diaria'] . '</v></c>';
        $linhas[] = '</row>';
        $row++;
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Mensal (MWh/mês)</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $dados['geracao_mensal'] . '</v></c>';
        $linhas[] = '</row>';
        $row++;
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Anual (MWh/ano)</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $dados['geracao_anual'] . '</v></c>';
        $linhas[] = '</row>';
    } else {
        $resultadoPrincipal = $dados['eficiencia'] * 1000 * $dados['vazao'] * 9.81 * $dados['altura'] * $dados['qtdTurbinas'] / 1e6;
        $geracaoDia = $dados['horas'] > 0 ? $resultadoPrincipal * $dados['horas'] : 0;
        $geracaoMes = $dados['horas'] > 0 ? $geracaoDia * 30 : 0;
        $geracaoAno = $dados['horas'] > 0 ? $geracaoDia * 365 : 0;
        
        $linhas[] = '<row r="' . $row . '">';
        $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Total (MW)</v></c>';
        $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $resultadoPrincipal . '</v></c>';
        $linhas[] = '</row>';
        $row++;
        
        if ($dados['horas'] > 0) {
            $linhas[] = '<row r="' . $row . '">';
            $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Diária (MWh/dia)</v></c>';
            $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $geracaoDia . '</v></c>';
            $linhas[] = '</row>';
            $row++;
            $linhas[] = '<row r="' . $row . '">';
            $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Mensal (MWh/mês)</v></c>';
            $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $geracaoMes . '</v></c>';
            $linhas[] = '</row>';
            $row++;
            $linhas[] = '<row r="' . $row . '">';
            $linhas[] = '<c r="A' . $row . '" t="str"><v>Geração Anual (MWh/ano)</v></c>';
            $linhas[] = '<c r="B' . $row . '" t="n"><v>' . $geracaoAno . '</v></c>';
            $linhas[] = '</row>';
        }
    }
    
    $linhas[] = '</sheetData>';
    $linhas[] = '</worksheet>';
    
    return implode('', $linhas);
}

function exportarCenarios($dados, $formato) {
    if ($formato !== 'pdf') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato inválido para cenários (use pdf)']);
        return;
    }
    exportarPDFCenarios($dados);
}

function exportarPDFCenarios($dados) {
    require('fpdf.php');

    $parametros = $dados['parametros'] ?? [];
    $resultados = $dados['resultados'] ?? [];
    $chuvaFoiLimitada = !empty($dados['chuvaFoiLimitada']);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, textoPDF('Comparação de Cenários de Risco Hídrico'), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, textoPDF('Parâmetros da simulação base'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $camposBase = [
        'Vazão Volumétrica' => (isset($parametros['vazao']) ? number_format((float) $parametros['vazao'], 2, ',', '.') : '-') . ' m³/s',
        'Altura da Queda' => (isset($parametros['altura']) ? number_format((float) $parametros['altura'], 2, ',', '.') : '-') . ' m',
        'Quantidade de Turbinas' => $parametros['qtdTurbinas'] ?? '-',
        'Potência do Gerador' => (isset($parametros['potGerador']) ? number_format((float) $parametros['potGerador'], 2, ',', '.') : '-') . ' MW',
        'Eficiência do Sistema' => (isset($parametros['eficiencia']) ? number_format((float) $parametros['eficiencia'] * 100, 2, ',', '.') : '-') . ' %',
    ];
    foreach ($camposBase as $label => $valor) {
        $pdf->Cell(60, 8, textoPDF($label . ':'), 0, 0);
        $pdf->Cell(0, 8, textoPDF((string) $valor), 0, 1);
    }

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, textoPDF('Resultados por cenário'), 0, 1);
    $pdf->SetFont('Arial', '', 11);

    foreach ($resultados as $resultado) {
        $nome = $resultado['nome'] ?? '-';
        $potencia = isset($resultado['potencia']) ? number_format((float) $resultado['potencia'], 2, ',', '.') : '-';
        $pdf->Cell(100, 8, textoPDF($nome . ':'), 0, 0);
        $pdf->Cell(0, 8, textoPDF($potencia . ' MW'), 0, 1);
    }

    if ($chuvaFoiLimitada) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->MultiCell(0, 6, textoPDF('Aviso: a potência do cenário de chuva intensa foi limitada pela capacidade instalada da usina (potência do gerador x quantidade de turbinas) - o excesso de água seria vertido, não convertido em energia.'));
    }

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->MultiCell(0, 6, textoPDF('Cenários calculados a partir dos mesmos parâmetros da simulação normal, aplicando ajustes percentuais de escassez, chuva intensa e/ou perda por detritos. Este documento não representa um registro salvo no histórico do sistema.'));

    // Embute o gráfico de barras (enviado pelo navegador como PNG em base64) numa pagina nova
    $graficoBase64 = $dados['graficoBase64'] ?? null;
    $tmpImg = null;
    if ($graficoBase64 && preg_match('/^data:image\/png;base64,(.+)$/', $graficoBase64, $matches)) {
        $imgData = base64_decode($matches[1], true);
        if ($imgData !== false) {
            $tmpImg = tempnam(sys_get_temp_dir(), 'graf') . '.png';
            file_put_contents($tmpImg, $imgData);

            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, textoPDF('Gráfico Comparativo'), 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Image($tmpImg, null, null, 180);
        }
    }

    $timestamp = date('Y-m-d_H-i-s');
    $pdf->Output('D', 'Cenarios_' . $timestamp . '.pdf');

    if ($tmpImg && file_exists($tmpImg)) {
        unlink($tmpImg);
    }
    exit;
}

function criarXLSX($zipPath, $worksheetXML) {
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    
    // [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>
</Types>';
    
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    
    // _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>
</Relationships>';
    
    $zip->addFromString('_rels/.rels', $rels);
    
    // xl/workbook.xml
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006">
<workbookPr date1904="false" showObjects="all"/>
<bookViews>
<workbookView activeTab="0" firstSheet="0" showHorizontalScroll="true" showSheetTabs="true" showVerticalScroll="true" tabRatio="600" windowHeight="8192" windowWidth="16384" xWindow="0" yWindow="0"/>
</bookViews>
<sheets>
<sheet name="Simulacao" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
    
    $zip->addFromString('xl/workbook.xml', $workbook);
    
    // xl/_rels/workbook.xml.rels
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
    
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    
    // xl/worksheets/sheet1.xml
    $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXML);
    
    // docProps/core.xml
    $coreProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:creator>SiSGEH</dc:creator>
<dc:title>Simulacao Hidreletrica</dc:title>
<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-dT H:i:sZ') . '</dcterms:created>
</cp:coreProperties>';
    
    $zip->addFromString('docProps/core.xml', $coreProps);
    
    $zip->close();
}
?>
