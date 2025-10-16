<?php
$host = "localhost";
$usuario = "u839226731_cztuap";
$senha = "Meu6595869Trator";
$banco = "u839226731_meutrator";

// Criação da conexão
$cx = new mysqli($host, $usuario, $senha, $banco);

// Verificação de erros
if ($cx->connect_error) {
    die("Erro de conexão: " . $cx->connect_error);
}

// Define charset para evitar problemas com acentuação
$cx->set_charset("utf8mb4");
?>
