<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$usuario = $_SESSION["username"];
$mensagem = "";
$comprovante = null;

$conn = new mysqli("localhost", "u839226731_cztuap", "Meu6595869Trator", "u839226731_meutrator");
if ($conn->connect_error) die("Erro na conexão: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 🔍 Buscar caixa_postal do usuário logado
$stmtUser = $conn->prepare("SELECT caixa_postal FROM identificacao WHERE username = ? LIMIT 1");
$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$caixaUsuario = $resUser->fetch_assoc()["caixa_postal"] ?? null;
$stmtUser->close();

// 🔄 Buscar saldo baseado em caixa_postal e username
$stmtSaldo = $conn->prepare("SELECT saldo_total FROM identificacao WHERE caixa_postal = ? AND username = ?");
$stmtSaldo->bind_param("ss", $caixaUsuario, $usuario);
$stmtSaldo->execute();
$resSaldo = $stmtSaldo->get_result();
$saldoAura = $resSaldo->fetch_assoc()["saldo_total"] ?? 0;
$stmtSaldo->close();

// ✅ Redirecionamento pós POST
if (isset($_GET["transacao"]) && isset($_SESSION["comprovante_aura"])) {
    $mensagem = "✅ Transação registrada com sucesso!";
    $comprovante = $_SESSION["comprovante_aura"];
    unset($_SESSION["comprovante_aura"]);
}

// 🚀 Processar transferência via POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enviar_aura"])) {
    $destinoCaixa = $_POST["caixa_destino"];
    $valor = (int)$_POST["valor"];
    $senhaInformada = $_POST["senha_usuario"];

    $stmtSenha = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmtSenha->bind_param("s", $usuario);
    $stmtSenha->execute();
    $resSenha = $stmtSenha->get_result();
    $senhaCorreta = $resSenha->fetch_assoc()["password"] ?? null;
    $stmtSenha->close();

    if (!$senhaCorreta || !password_verify($senhaInformada, $senhaCorreta)) {
        $mensagem = "⚠️ Senha incorreta. Transação não autorizada.";
    } elseif ($valor <= 0 || $saldoAura < $valor) {
        $mensagem = "⚠️ Valor inválido ou saldo insuficiente.";
    } else {
        // Verifica se caixa_postal de destino existe
        $stmtDest = $conn->prepare("SELECT saldo_total FROM identificacao WHERE caixa_postal = ?");
        $stmtDest->bind_param("s", $destinoCaixa);
        $stmtDest->execute();
        $resDest = $stmtDest->get_result();
        $destData = $resDest->fetch_assoc();
        $stmtDest->close();

        if ($destData) {
            $conn->begin_transaction();
            try {
                // Atualizar saldo do remetente
                $stmtDeb = $conn->prepare("UPDATE identificacao SET saldo_total = saldo_total - ? WHERE caixa_postal = ? AND username = ?");
                $stmtDeb->bind_param("iss", $valor, $caixaUsuario, $usuario);
                $stmtDeb->execute();
                $stmtDeb->close();

                // Atualizar saldo do destinatário via caixa_postal
                $stmtCred = $conn->prepare("UPDATE identificacao SET saldo_total = saldo_total + ? WHERE caixa_postal = ?");
                $stmtCred->bind_param("is", $valor, $destinoCaixa);
                $stmtCred->execute();
                $stmtCred->close();

                // Registrar transação
                $stmtInsert = $conn->prepare("
                    INSERT INTO transacoes_aura (remetente, destinatario, valor, caixa_origem, caixa_destino)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtInsert->bind_param("ssiss", $usuario, $destinoCaixa, $valor, $caixaUsuario, $destinoCaixa);
                $stmtInsert->execute();
                $stmtInsert->close();

                // Gerar comprovante
                $comprovante = [
                    "remetente" => $usuario,
                    "destinatario" => $destinoCaixa,
                    "valor" => $valor,
                    "caixa_origem" => $caixaUsuario,
                    "caixa_destino" => $destinoCaixa,
                    "data" => date("d/m/Y H:i:s")
                ];
                $_SESSION["comprovante_aura"] = $comprovante;

                $stmtComp = $conn->prepare("
                    INSERT INTO comprovantes_aura (remetente, destinatario, valor, caixa_origem, caixa_destino)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtComp->bind_param("ssiss", $comprovante["remetente"], $comprovante["destinatario"], $comprovante["valor"], $comprovante["caixa_origem"], $comprovante["caixa_destino"]);
                $stmtComp->execute();
                $stmtComp->close();

                $conn->commit();
                header("Location: " . $_SERVER["PHP_SELF"] . "?transacao=ok");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $mensagem = "❌ Erro ao transferir aura: " . $e->getMessage();
            }
        } else {
            $mensagem = "⚠️ Caixa postal não encontrada.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>🔐 Painel de Aura</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background: #eef;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
    }

    h2, h3 {
      color: #5c3dc4;
      text-align: center;
      margin-bottom: 10px;
    }

    .saldo {
      margin: 20px 0;
      text-align: center;
      font-size: 1.2rem;
      color: #333;
    }

    form {
      margin-top: 20px;
    }

    input[type="text"],
    input[type="number"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    button {
      background: #5c3dc4;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
      font-size: 1rem;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #432da0;
    }

    .mensagem {
      margin: 15px 0;
      text-align: center;
      font-size: 1rem;
      color: #333;
    }

    .comprovante {
      margin-top: 20px;
      padding: 15px;
      background: #f0fff0;
      border: 2px dashed #28a745;
      border-radius: 10px;
    }

    .comprovante p {
      margin: 5px 0;
      font-size: 0.95rem;
    }

    img.qr {
      display: block;
      margin: 10px auto;
    }

    @media (max-width: 600px) {
      .container {
        padding: 15px;
      }

      h2, h3 {
        font-size: 1.2rem;
      }

      .saldo {
        font-size: 1rem;
      }

      button {
        padding: 10px;
        font-size: 0.95rem;
      }
    }
    @media print {
  .saldo {
    display: none;
  }

  form,
  .mensagem,
  h2,
  h3:not(:has(+ .comprovante)) {
    display: none;
  }

  body {
    background: white;
  }

  .comprovante {
    border: none;
    box-shadow: none;
  }

  .comprovante button {
    display: none;
  }
}

  </style>
</head>
<body>
  <div class="container">
    <h2>🔐 Painel de Aura</h2>
<p><strong>Caixa Origem:</strong> <?= htmlspecialchars(html_entity_decode($caixaUsuario, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></p>

    <?php if ($mensagem): ?>
      <div class="mensagem"><?= $mensagem ?></div>
    <?php endif; ?>

    <?php if ($comprovante): ?>
    <div class="comprovante">
      <h3>📄 Comprovante de Transação</h3>
      <p><strong>Remetente:</strong> <?= htmlspecialchars($comprovante["remetente"]) ?></p>
      <p><strong>Caixa Origem:</strong> <?= htmlspecialchars($comprovante["caixa_origem"]) ?></p>
      <p><strong>Caixa Destino:</strong> <?= htmlspecialchars($comprovante["caixa_destino"]) ?></p>
      <p><strong>Quantidade:</strong> <?= $comprovante["valor"] ?> aura</p>
      <p><strong>Data:</strong> <?= $comprovante["data"] ?></p>
      <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode(json_encode($comprovante)) ?>&size=150x150" alt="QR Comprovante">
      <button onclick="window.print()">🖨️ Imprimir Comprovante</button>
    </div>
    <?php endif; ?>

    <div class="saldo">✨ Saldo total de aura: <strong><?= $saldoAura ?></strong></div>

    <h3>📦 Enviar Aura</h3>
    <form method="POST">
      <input type="text" name="caixa_destino" placeholder="Caixa postal destino" required>
      <input type="number" name="valor" placeholder="Quantidade de aura a enviar" required>
      <input type="password" name="senha_usuario" placeholder="Sua senha para confirmar" required>
      <input type="hidden" name="enviar_aura" value="1">
      <button>⚡ Confirmar e Enviar Aura</button>
    </form>
  </div>
</body>
</html>

