<?php
$servidor = "localhost";
$usuario = "root";
$senha = "@Aniver1997";  
$banco = "SistemaHidreletrico"; 

$conn = new mysqli($servidor, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
