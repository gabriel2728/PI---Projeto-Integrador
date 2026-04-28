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

// Buscar dados do usu�rio
$sql = "SELECT nomeUsuario, emailUsuario, telefoneUsuario FROM Usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    $_SESSION['mensagem_erro'] = 'Usu�rio n�o encontrado.';
    header('Location: inicio.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" href="../css/estilo_configuracao.css">
    <style>
        .menu-configuracoes {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            gap: 30px;
        }

        .opcao-config {
            display: block;
            width: 300px;
            padding: 25px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .opcao-config:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .dados-usuario {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid #ddd;
            max-width: 400px;
        }

        .dados-usuario h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .dado-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .dado-label {
            font-weight: bold;
            color: #555;
        }

        .dado-valor {
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
        <h2 class="sisgeh"> SiSGEH </h2>

        <div class="links">
             <a href="inicio.php" class="link_home">
                 <img src="../home.png" alt="Voltar a Home" class="home">
            </a>
        </div>
    </header>

    <div class="layoutConfiguracao">
        <div class="configuracao">
            <h1>Configurações</h1>

            <div class="menu-configuracoes">
                <a href="configuracoes_perfil.php" class="opcao-config">
                    👤 Configurações de Perfil
                </a>

                <a href="configuracoes_sistema.php" class="opcao-config">
                    ⚙️ Configurações do Sistema
                </a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; Todos os direitos reservados. <a href="../politica.html">Pol�ticas de privacidade.</a></p>
    </footer>
</body>
</html>
