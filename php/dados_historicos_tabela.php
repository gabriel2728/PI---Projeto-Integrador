<?php
/**
 * Dados históricos de pluviosidade e potência usados na análise preditiva.
 *
 * id_usuario NULL = base original do sistema (dados reais INMET/ONS). Não aparece para ninguém
 *                   diretamente: serve só de modelo para as cópias.
 * id_usuario preenchido = cópia pessoal do usuário. Ele pode editar, excluir e incluir registros
 *                         à vontade para treinar o modelo, sem afetar os outros usuários nem a base original.
 */
function garantirTabelaDadosHistoricos(mysqli $conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS DadosHistoricos (
        id_dado INT AUTO_INCREMENT PRIMARY KEY,
        data_registro DATE NOT NULL,
        pluviosidade_mm DECIMAL(10,2) NOT NULL,
        potencia_mw DECIMAL(10,2) NOT NULL,
        fonte VARCHAR(500) NULL,
        id_usuario INT NULL,
        INDEX idx_dados_usuario (id_usuario)
    )");

    $colunas = [
        ['DadosHistoricos', 'fonte', "ALTER TABLE DadosHistoricos ADD COLUMN fonte VARCHAR(500) NULL"],
        ['DadosHistoricos', 'id_usuario', "ALTER TABLE DadosHistoricos ADD COLUMN id_usuario INT NULL, ADD INDEX idx_dados_usuario (id_usuario)"],
        // Marca se o usuário já recebeu a cópia da base original (para não recopiar quando ele apagar tudo)
        ['Usuario', 'dados_historicos_iniciados', "ALTER TABLE Usuario ADD COLUMN dados_historicos_iniciados TINYINT(1) NOT NULL DEFAULT 0"],
    ];
    foreach ($colunas as [$tabela, $coluna, $alter]) {
        $res = $conn->query("SHOW COLUMNS FROM {$tabela} LIKE '{$coluna}'");
        if ($res && $res->num_rows === 0) {
            $conn->query($alter);
        }
    }
}

/**
 * Na primeira vez que o usuário usa o módulo, copia a base original para a conta dele.
 */
function garantirDadosDoUsuario(mysqli $conn, $idUsuario) {
    $stmt = $conn->prepare('SELECT dados_historicos_iniciados FROM Usuario WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($linha && (int) $linha['dados_historicos_iniciados'] === 0) {
        copiarBaseOriginal($conn, $idUsuario);
    }
}

/**
 * Apaga a base pessoal do usuário e coloca de novo uma cópia da base original.
 */
function restaurarBaseOriginal(mysqli $conn, $idUsuario) {
    $stmt = $conn->prepare('DELETE FROM DadosHistoricos WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $stmt->close();
    copiarBaseOriginal($conn, $idUsuario);
}

function copiarBaseOriginal(mysqli $conn, $idUsuario) {
    $stmt = $conn->prepare('INSERT INTO DadosHistoricos (data_registro, pluviosidade_mm, potencia_mw, fonte, id_usuario)
        SELECT data_registro, pluviosidade_mm, potencia_mw, fonte, ? FROM DadosHistoricos WHERE id_usuario IS NULL');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE Usuario SET dados_historicos_iniciados = 1 WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $stmt->close();
}
