<?php
session_start();

// Verificar login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Conexão com banco
$servername = "localhost";
$username = "u839226731_cztuap";
$password = "Meu6595869Trator";
$dbname = "u839226731_meutrator";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$usuario = $_SESSION["username"];
$mensagem = "";

// Verificar se usuário já tem caixa postal
$stmtVerifica = $conn->prepare("SELECT caixa_postal, documento, data_criacao FROM identificacao WHERE username = ?");
$stmtVerifica->bind_param("s", $usuario);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();
$caixaExistente = $resultVerifica->fetch_assoc();
$stmtVerifica->close();

// Criar caixa postal
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['criar_caixa_postal'])) {
    if ($caixaExistente) {
        $mensagem = "⚠️ Você já possui uma caixa postal. Para criar outra, exclua a atual.";
    } else {
        $documento = trim($_POST["documento"]);
        $codigo = 'CP_' . md5($usuario . microtime(true) . random_int(1000, 9999));
        $stmt = $conn->prepare("INSERT INTO identificacao (username, documento, caixa_postal) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $usuario, $documento, $codigo);
            $stmt->execute();
            $stmt->close();
            $mensagem = "📬 Caixa postal criada com sucesso: <strong>$codigo</strong>";
        } else {
            $mensagem = "❌ Erro ao criar caixa postal.";
        }
        // Atualiza caixaExistente após criação
        $stmtVerifica = $conn->prepare("SELECT caixa_postal, documento, data_criacao FROM identificacao WHERE username = ?");
        $stmtVerifica->bind_param("s", $usuario);
        $stmtVerifica->execute();
        $resultVerifica = $stmtVerifica->get_result();
        $caixaExistente = $resultVerifica->fetch_assoc();
        $stmtVerifica->close();
    }
}

// Atualizar documento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar_documento'])) {
    $novoDocumento = trim($_POST["novo_documento"]);
    $stmtUpdate = $conn->prepare("UPDATE identificacao SET documento = ? WHERE username = ?");
    if ($stmtUpdate) {
        $stmtUpdate->bind_param("ss", $novoDocumento, $usuario);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        $mensagem = "✅ Documento atualizado com sucesso.";
        // Atualiza dados da caixa
        $stmtVerifica = $conn->prepare("SELECT caixa_postal, documento, data_criacao FROM identificacao WHERE username = ?");
        $stmtVerifica->bind_param("s", $usuario);
        $stmtVerifica->execute();
        $resultVerifica = $stmtVerifica->get_result();
        $caixaExistente = $resultVerifica->fetch_assoc();
        $stmtVerifica->close();
    } else {
        $mensagem = "❌ Erro ao atualizar documento.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>📬 Caixa Postal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 0 8px #aaa; }
        h2 { color: #007bff; text-align: center; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 6px; }
        button { width: 100%; padding: 10px; margin-top: 15px; background: #007bff; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .mensagem { margin-top: 20px; text-align: center; font-size: 16px; color: #333; }
        .caixa { background: #f9f9f9; margin-top: 30px; padding: 15px; border-left: 5px solid #007bff; border-radius: 8px; }
        .caixa strong { display: block; font-size: 18px; }
        .caixa small { display: block; margin-top: 5px; color: #555; }
        form.editar { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bem-vindo, <?= htmlspecialchars($usuario) ?></h2>

        <form method="POST" action="">
            <label for="documento">Documento CPF:</label>
            <input type="text" name="documento" id="documento" p CPFlaceholder="Digite seu documento" required>
            <input type="hidden" name="criar_caixa_postal" value="1">
            <button type="submit">📬 Criar Caixa Postal</button>
        </form>

        <?php if ($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
        <?php endif; ?>

        <?php if ($caixaExistente): ?>
            <div class="caixa">
                <strong>📦 Caixa Postal: <?= htmlspecialchars($caixaExistente['caixa_postal']) ?></strong>
                <small>🧾 Documento CPF: <?= htmlspecialchars($caixaExistente['documento']) ?></small>
                <small>🕒 Criado em: <?= $caixaExistente['data_criacao'] ?></small>

                <form method="POST" class="editar">
                    <input type="text" name="novo_documento" placeholder="Atualizar documento" required>
                    <input type="hidden" name="editar_documento" value="1">
                    <button type="submit">✏️ Editar Documento</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
