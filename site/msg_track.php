<?php
// Inicializar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Configuração do banco
$servername = "127.0.0.1";
$username = "u839226731_cztuap";
$password = "Meu6595869Trator";
$dbname = "u839226731_meutrator";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Gerar ID único para o registro
$id = uniqid("registro_");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qrcode = htmlspecialchars($_POST['qrcode'], ENT_QUOTES, 'UTF-8');
    $nome_recebedor = htmlspecialchars($_POST['nome_recebedor'], ENT_QUOTES, 'UTF-8');
    $cidade_recebedor = htmlspecialchars($_POST['cidade_recebedor'], ENT_QUOTES, 'UTF-8');
    $caixa_postal = htmlspecialchars($_POST['caixa_postal'], ENT_QUOTES, 'UTF-8');
    $documento = htmlspecialchars($_POST['documento'], ENT_QUOTES, 'UTF-8');
    $valor_de_aura = isset($_POST['valor_de_aura']) ? intval($_POST['valor_de_aura']) : 0;
    $ip_acesso = $_SERVER['REMOTE_ADDR'];
    $eq_user = $_SESSION["username"];
    $data_now = $_POST['data_now'] ?? null;

    // Validação
    if (!is_numeric($valor_de_aura) || $valor_de_aura <= 0) {
    echo "<p style='color:red;'>⚠️ Valor de aura deve ser um número positivo maior que zero.</p>";
    exit;
}

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_now)) {
        echo "<p style='color:red;'>⚠️ Data inválida.</p>";
        exit;
    }

    // Upload da imagem
    $targetFile = null;
    if (isset($_FILES["foto_recebedor"]) && $_FILES["foto_recebedor"]["error"] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = basename($_FILES["foto_recebedor"]["name"]);
        $targetFile = $targetDir . uniqid() . "_" . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "jpeg", "png", "gif");

        if (in_array($fileType, $allowedTypes)) {
            move_uploaded_file($_FILES["foto_recebedor"]["tmp_name"], $targetFile);
        }
    }

    // Upload do arquivo .wav
    $wavLink = null;
    if (isset($_FILES["arquivo_wav"]) && $_FILES["arquivo_wav"]["error"] == 0) {
        $wavDir = "downloads/";
        if (!is_dir($wavDir)) {
            mkdir($wavDir, 0755, true);
        }

        $wavName = basename($_FILES["arquivo_wav"]["name"]);
        $wavFile = $wavDir . uniqid() . "_" . $wavName;

        $wavType = strtolower(pathinfo($wavFile, PATHINFO_EXTENSION));
        if ($wavType === "wav") {
            if (move_uploaded_file($_FILES["arquivo_wav"]["tmp_name"], $wavFile)) {
                $wavLink = "https://carlitoslocacoes.com/site/" . $wavFile;
            }
        }
    }

    // Inserir no banco
    $stmt = $conn->prepare("INSERT INTO catalogo_musica (
        cv, ip_acesso, qrcode, eq_user, nome_recebedor, cidade_recebedor, destino_recebedor, caixa_postal, documento, foto_recebedor, valor_de_aura, data_now
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssds", $id, $ip_acesso, $qrcode, $eq_user, $nome_recebedor, $cidade_recebedor, $wavLink, $caixa_postal, $documento, $targetFile, $valor_de_aura, $data_now);

    try {
        $stmt->execute();
        echo "<p>✅ Registro criado com sucesso!</p>";
        if ($wavLink) {
            echo "<p><strong>Ouça o arquivo enviado:</strong></p>";
            echo "<audio controls src='$wavLink' style='width:100%;'></audio>";
        }
    } catch (Exception $e) {
        echo "<p>Erro ao criar o registro: " . $e->getMessage() . "</p>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Música ou Podcast</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style>
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <center><a href="http://carlitoslocacoes.com/frame" class="btn btn-primary">Início</a></center>
    <div class="page-header text-center">
        <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8'); ?>!</h1>
    </div>
    <div class="container">
        <form method="POST" action="" enctype="multipart/form-data">
            <p><b>Novo Cadastro de Música ou Podcast</b></p>

            <div class="form-group">
                <label for="qrcode">Número WhatsApp:</label>
                <input type="text" id="qrcode" name="qrcode" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="nome_recebedor">Título da Música:</label>
                <input type="text" id="nome_recebedor" name="nome_recebedor" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="documento">Documento do Autor:</label>
                <input type="text" id="documento" name="documento" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="cidade_recebedor">Cidade:</label>
                <input type="text" id="cidade_recebedor" name="cidade_recebedor" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="caixa_postal">Caixa Postal:</label>
                <input type="text" id="caixa_postal" name="caixa_postal" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="valor_de_aura">Valor de Aura:</label>
                <input type="number" id="valor_de_aura" name="valor_de_aura" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label for="foto_recebedor">Foto de Capa:</label>
                <input type="file" id="foto_recebedor" name="foto_recebedor" class="form-control" accept=".jpg,.jpeg,.png,.gif" required>
            </div>
            <div class="form-group">
                <label for="arquivo_wav">Arquivo de Música (.wav):</label>
                <input type="file" id="arquivo_wav" name="arquivo_wav" class="form-control" accept=".wav" required>
            </div>
            <div class="form-group">
                <label for="data_now">Data do Release:</label>
                <input type="date" id="data_now" name="data_now" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success">Cadastrar Música</button>
        </form>
    </div>
</body>
</html>
