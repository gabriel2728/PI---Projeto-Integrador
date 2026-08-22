<?php
require('fpdf/fpdf.php');
include('conexao.php');

function textoPDF($texto) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $texto);
}

// Verifica se recebeu o ID da simulação pela URL
if (isset($_GET['id'])) {
    $id_simulacao = intval($_GET['id']);

    // Busca os dados da simulação no banco
    $sql = "SELECT * FROM simulacoes WHERE id = $id_simulacao";
    $resultado = mysqli_query($conexao, $sql);
    $dados = mysqli_fetch_assoc($resultado);

    if ($dados) {
        // Cria o PDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, textoPDF('Relatório de Simulação Hidrelétrica'), 0, 1, 'C');
        $pdf->Ln(10);

        // Define fonte para o conteúdo
        $pdf->SetFont('Arial', '', 12);

        // Mostra os dados
        foreach ($dados as $campo => $valor) {
            $label = ucfirst($campo);
            if ($campo === 'vazao') {
                $label = 'Vazão Volumétrica';
            }

            $pdf->Cell(60, 10, textoPDF($label . ':'), 0, 0);
            $pdf->Cell(0, 10, textoPDF($valor), 0, 1);
        }

        // Força o download do PDF
        $pdf->Output('D', 'Simulacao_' . $id_simulacao . '.pdf');
        exit;
    } else {
        echo "Simulacao nao encontrada.";
    }
} else {
    echo "ID da simulacao nao informado.";
}
?>
