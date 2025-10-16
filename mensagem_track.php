<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once 'conexao.php';

if (empty($_SESSION['username'])) {
    die("<p class='text-danger text-center mt-5'>Sessão não iniciada. Faça login antes de acessar esta página.</p>");
}

$usuarioLogado = $_SESSION['username'];
$id = $_GET['id'] ?? $_POST['id'] ?? '';
$erro = '';
$comprovante = '';

if (empty($id)) {
    die("ID da etiqueta não informado.");
}

// Consulta da etiqueta
$stmt = $cx->prepare("SELECT valor_de_aura, caixa_postal, nome_recebedor, destino_recebedor FROM catalogo_musica WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$etiqueta = $result->fetch_assoc();
$destinoDownload = trim($etiqueta['destino_recebedor']);
$stmt->close();

if (!$etiqueta) {
    die("Etiqueta não encontrada.");
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconhecido';
$agente = $_SERVER['HTTP_USER_AGENT'] ?? 'Agente desconhecido';
$cv = session_id(); // ou outro identificador único
$assinaturaRecebida = ''; // se quiser registrar algo como token ou hash

$stmtLog = $cx->prepare("INSERT INTO pagamento_musica (cv, id_etiqueta, ip_acesso, agente_usuario, assinatura, username) VALUES (?, ?, ?, ?, ?, ?)");
$stmtLog->bind_param("sissss", $cv, $id, $ip, $agente, $assinaturaRecebida, $usuarioLogado);
$stmtLog->execute();
$stmtLog->close();

$valorAura = ceil(floatval($etiqueta['valor_de_aura']));
$caixaDestino = trim($etiqueta['caixa_postal']);
$nomeRecebedor = htmlspecialchars($etiqueta['nome_recebedor']);

// Processa envio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
    $senha = $_POST['senha'];

    // Verifica senha do usuário logado
    $stmt = $cx->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $usuarioLogado);
    $stmt->execute();
    $stmt->bind_result($senha_hash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($senha, $senha_hash)) {
        $erro = "Senha incorreta. Operação cancelada.";
    } else {
        // Verifica saldo do remetente
        $stmt = $cx->prepare("SELECT saldo_total FROM identificacao WHERE username = ?");
        $stmt->bind_param("s", $usuarioLogado);
        $stmt->execute();
        $stmt->bind_result($saldoAtual);
        $stmt->fetch();
        $stmt->close();

        if ($saldoAtual < $valorAura) {
            $erro = "Saldo insuficiente. São necessários $valorAura aura.";
        } else {
            // Busca destinatário pela caixa postal
            $stmt = $cx->prepare("SELECT username, caixa_postal FROM identificacao WHERE caixa_postal = ? LIMIT 1");
            $stmt->bind_param("s", $caixaDestino);
            $stmt->execute();
            $result = $stmt->get_result();
            $destinatario = $result->fetch_assoc();
            $stmt->close();

            if (!$destinatario) {
                $erro = "Destinatário não encontrado com essa caixa postal.";
            } else {
                $usuarioDestino = $destinatario['username'];
                $caixaAtual = $destinatario['caixa_postal'] ?? '';

                // Desconta do remetente
                $stmt = $cx->prepare("UPDATE identificacao SET saldo_total = saldo_total - ? WHERE username = ?");
                $stmt->bind_param("is", $valorAura, $usuarioLogado);
                $stmt->execute();
                $stmt->close();

                // Credita ao destinatário
                $stmt = $cx->prepare("UPDATE identificacao SET saldo_total = saldo_total + ? WHERE username = ?");
                $stmt->bind_param("is", $valorAura, $usuarioDestino);
                $stmt->execute();
                $stmt->close();

                // Atualiza caixa postal do destinatário
                
// Gera hash da transação
$data = date('Y-m-d H:i:s');
$dadosParaHash = $usuarioLogado . $usuarioDestino . $valorAura . $id . $data . session_id();
$assinaturaRecebida = hash('sha256', $dadosParaHash);

// Salva log com hash
$stmtLog = $cx->prepare("INSERT INTO pagamento_musica (cv, id_etiqueta, ip_acesso, agente_usuario, assinatura, username, hash_transacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtLog->bind_param("sisssss", $cv, $id, $ip, $agente, $assinaturaRecebida, $usuarioLogado, $assinaturaRecebida);
$stmtLog->execute();
$stmtLog->close();

                // Comprovante
                $data = date('d/m/Y H:i');
                $comprovante = "
                <div class='card mt-4 mx-auto' style='max-width: 700px;'>
                    <div class='card-header bg-success text-white text-center'>
                        Comprovante de Transferência de Aura
                    </div>
                    <div class='card-body'>
                        <table class='table table-bordered'>
                            <tr><th>Remetente</th><td>$usuarioLogado</td></tr>
                            <tr><th>Destinatário</th><td>$usuarioDestino</td></tr>
                            <tr><th>Valor transferido</th><td>$valorAura aura</td></tr>
                            <tr><th>Etiqueta</th><td>$nomeRecebedor (ID $id)</td></tr>
                            <tr><th>Data</th><td>$data</td></tr>
                            <tr><th>Hash da transação</th><td><code>$assinaturaRecebida</code></td></tr>
                        </table>
                        <hr>
                        <p><strong>Status:</strong> Transferência registrada com sucesso.</p>
                        <button onclick='window.print()' class='btn btn-outline-secondary mt-3'>Imprimir Comprovante</button>
                    </div>
                </div>";
                $comprovante .= "
<script>
  window.onload = function() {
    const link = document.createElement('a');
    link.href = '" . htmlspecialchars($destinoDownload, ENT_QUOTES, 'UTF-8') . "';
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };
</script>";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Transferência de Aura</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="text-center">Enviar Aura para Caixa Postal</h2>

  <p><strong>Etiqueta:</strong> <?= $nomeRecebedor ?> (ID <?= $id ?>)</p>
  <p><strong>Valor de Aura:</strong> <?= $valorAura ?> aura</p>
  <p><strong>Caixa Postal de destino:</strong> <?= htmlspecialchars($caixaDestino) ?></p>

  <?php if ($erro): ?>
    <div class="alert alert-danger text-center mt-3"><?= $erro ?></div>
  <?php endif; ?>

  <?php if (!$comprovante): ?>
    <form method="POST" class="mt-4" style="max-width: 400px; margin: auto;">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="mb-3">
        <label for="senha" class="form-label">Digite sua senha para confirmar:</label>
        <input type="password" name="senha" id="senha" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Confirmar Envio</button>
    </form>
  <?php endif; ?>

  <?= $comprovante ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
