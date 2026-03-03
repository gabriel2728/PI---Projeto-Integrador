<?php
// Inclui o arquivo de conexão
include('conexao.php');

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Pega os dados do formulário
    $nome = $_POST['nomeUsuario'];
    $telefone = $_POST['telefoneUsuario'];
    $email = $_POST['emailUsuario'];
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar_senha'];

    // Verifica se as senhas conferem
    if ($senha !== $confirmar) {
        echo "<script>alert('As senhas não coincidem!'); window.history.back();</script>";
        exit;
    }

    // Criptografa a senha
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    // Monta o comando SQL
    $sql = "INSERT INTO Usuario (nomeUsuario, telefoneUsuario, emailUsuario, senha)
            VALUES ('$nome', '$telefone', '$email', '$senhaCriptografada')";

    // Executa o comando SQL
    if (mysqli_query($conn, $sql)) {
    session_start();
    $_SESSION['id_usuario'] = mysqli_insert_id($conn); 
    $_SESSION['nomeUsuario'] = $nome;

    echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='inicio.php';</script>";
} else {
    echo "Erro ao cadastrar: " . mysqli_error($conn);
}
}
?>
