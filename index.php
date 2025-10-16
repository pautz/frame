<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Painel Musical</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f0f4f8, #d9e2ec);
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }
    h1 {
      color: #2c3e50;
      margin-bottom: 30px;
      text-align: center;
    }
    .panel {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      justify-content: center;
      max-width: 1000px;
      width: 100%;
    }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
      padding: 30px;
      text-align: center;
      flex: 1 1 300px;
      transition: transform 0.2s ease;
    }
    .card:hover {
      transform: scale(1.03);
    }
    .card h2 {
      margin-bottom: 15px;
      color: #34495e;
    }
    .card p {
      font-size: 15px;
      margin-bottom: 20px;
    }
    .card a {
      display: inline-block;
      padding: 12px 20px;
      background-color: #3498db;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .card a:hover {
      background-color: #2980b9;
    }
    .btn-login {
  display: inline-block;
  margin-bottom: 40px;
  padding: 12px 24px;
  background-color: #2ecc71;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: bold;
  transition: background 0.3s ease;
}
.btn-login:hover {
  background-color: #27ae60;
}

    @media (max-width: 768px) {
      .card {
        flex: 1 1 100%;
      }
    }
  </style>
</head>
<body>
  <h1>🎧 Painel Musical</h1>

  <a href="https://carlitoslocacoes.com/site/login.php" class="btn-login">🔐 Entrar no Sistema</a>

  <div class="panel">
    <div class="card">
      <h2>🎼 Catálogo</h2>
      <p>Vincule suas músicas ou podcast com aura.</p>
      <a href="https://carlitoslocacoes.com/catalogo_musica/">Acessar Catálogo</a>
    </div>
    <div class="card">
      <h2>💬 Publicar Faixas ou Podcast</h2>
      <p>Vincule suas músicas ou podcast com aura.</p>
      <a href="https://carlitoslocacoes.com/site/msg_track.php">Acessar Mensagens</a>
    </div>
  </div>
</body>

</html>
