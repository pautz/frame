<?php
session_start();


// ⚙️ Conexão com o banco
$conn = new mysqli("localhost", "u839226731_cztuap", "Meu6595869Trator", "u839226731_meutrator");
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 🔍 Filtros
$limite = 9;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina - 1) * $limite;

$searchQuery = "WHERE 1=1";


if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $searchQuery .= " AND id = $id";
}
if (!empty($_GET['cv'])) {
    $cv = $_GET['cv'];
    $searchQuery .= " AND cv='" . $conn->real_escape_string($cv) . "'";
}
if (!empty($_GET['nome'])) {
    $nome = $_GET['nome'];
    $searchQuery .= " AND nome_recebedor LIKE '%" . $conn->real_escape_string($nome) . "%'";
}
if (!empty($_GET['eq_user'])) {
    $eq_user = $_GET['eq_user'];
    $searchQuery .= " AND eq_user='" . $conn->real_escape_string($eq_user) . "'";
}
if (!empty($_GET['cidade'])) {
    $cidade = $_GET['cidade'];
    $searchQuery .= " AND cidade_recebedor LIKE '%" . $conn->real_escape_string($cidade) . "%'";
}

// 🔄 Obter registros
$sql = "SELECT * FROM catalogo_musica $searchQuery ORDER BY id DESC LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);

// 🔢 Total de páginas
$sqlTotal = "SELECT COUNT(*) AS total FROM catalogo_musica $searchQuery";
$resultTotal = $conn->query($sqlTotal);
$totalRegistros = $resultTotal->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limite);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Catálogo Musical</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #f0f4f8, #d9e2ec);
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }
        .form-group {
            flex: 1 1 200px;
            display: flex;
            flex-direction: column;
        }
        input[type="text"],
        input[type="number"],
        input[type="submit"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #2980b9;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: scale(1.02);
        }
        .card img {
            max-width: 180px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .card h3 {
            margin: 10px 0;
            color: #34495e;
        }
        .card p {
            margin: 5px 0;
            font-size: 15px;
        }
        .pagination {
            text-align: center;
            margin-top: 30px;
        }
        .pagination a {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            background-color: #ecf0f1;
            color: #2c3e50;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .pagination a:hover {
            background-color: #bdc3c7;
        }
        @media (max-width: 768px) {
            .form-group {
                flex: 1 1 100%;
            }
            .card {
                padding: 15px;
            }
            .card img {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🎵 Catálogo Musical</h2>
    <form method="get" action="">
        <div class="form-group">
            <label for="id">Pesquisar por ID:</label>
            <input type="number" name="id" id="id">
        </div>
        <div class="form-group">
            <label for="nome">Nome do Recebedor:</label>
            <input type="text" name="nome" id="nome">
        </div>
        <div class="form-group">
    <label for="eq_user">EQ User:</label>
    <input type="text" name="eq_user" id="eq_user">
</div>

        <input type="submit" value="🔍 Buscar">
    </form>

    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='card'>";
            echo "<p><strong>ID:</strong> " . intval($row['id']) . "</p>";
            echo "<p><strong>Recebedor:</strong> " . htmlspecialchars($row['nome_recebedor']) . "</p>";
            echo "<p><strong>Cidade:</strong> " . htmlspecialchars($row['cidade_recebedor']) . "</p>";
            echo "<p><strong>Caixa Postal:</strong> " . htmlspecialchars($row['caixa_postal']) . "</p>";
            echo "<p><strong>Documento:</strong> " . htmlspecialchars($row['documento']) . "</p>";
            echo "<p><strong>EQ User:</strong> " . htmlspecialchars($row['eq_user']) . "</p>";
            echo "<p><strong>Valor de Aura:</strong> " . intval($row['valor_de_aura']) . "</p>";
            echo "<p><strong>Data:</strong> " . htmlspecialchars($row['data_now']) . "</p>";
         
           if (!empty($row['foto_recebedor'])) {
    echo "<img src=../site/" . htmlspecialchars($row['foto_recebedor']) . " alt='Foto do Recebedor'>";
}
if (!empty($row['destino_recebedor'])) {
    echo "<audio controls style='margin-top:10px; width:100%; max-width:300px;'>
            <source src='" . htmlspecialchars($row['destino_recebedor']) . "' type='audio/mpeg'>
            Seu navegador não suporta o elemento de áudio.
          </audio>";
}

echo "<p><a href='../mensagem_track.php?id=" . intval($row['id']) . "' class='btn-visualizar'>Comprar</a></p>";

            echo "</div>";
        }
    } else {
        echo "<p>Nenhum registro encontrado.</p>";
    }
    ?>

    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPaginas; $i++) {
            echo "<a href='?pagina=$i'>$i</a>";
        } ?>
    </div>
</div>
</body>
</html>
