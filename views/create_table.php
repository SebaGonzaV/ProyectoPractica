<?php
session_start();
if (!isset($_SESSION["conexion"])) {
    header("Location: ../index.php");
    exit();
}

$conexion = $_SESSION["conexion"];
$dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
$usuario = $conexion["usuario"];
$clave = $conexion["clave"];

try {
    $pdo = new PDO($dsn, $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tablasExistentes = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Tabla</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            padding: 30px;
        }
        h2 {
            color: #002855;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            max-width: 800px;
            box-shadow: 0 0 10px #bbb;
        }
        input, select {
            padding: 7px;
            width: 100%;
            margin-top: 5px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #aaa;
        }
        .campo {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        button {
            padding: 10px 20px;
            background-color: #0072ce;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        .botones {
            margin-top: 20px;
        }
    </style>
    <script>
        function agregarCampo() {
            const contenedor = document.getElementById("campos");
            const index = contenedor.children.length;
            const div = document.createElement("div");
            div.className = "campo";
            div.innerHTML = `
                <label>Nombre del campo:</label>
                <input type="text" name="campos[${index}][nombre]" required>

                <label>Tipo de dato:</label>
                <select name="campos[${index}][tipo]" required>
                    <option value="INT">INT</option>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="DATE">DATE</option>
                    <option value="TEXT">TEXT</option>
                </select>

                <label>¿Clave primaria?</label>
                <select name="campos[${index}][pk]">
                    <option value="">No</option>
                    <option value="1">Sí</option>
                </select>

                <label>¿Clave foránea?</label>
                <select name="campos[${index}][fk_tabla]">
                    <option value="">No</option>
                    <?php foreach ($tablasExistentes as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            `;
            contenedor.appendChild(div);
        }
    </script>
</head>
<body>
    <h2>🧩 Crear Nueva Tabla</h2>

    <form action="../controllers/createTableController.php" method="POST">
        <label>Nombre de la tabla:</label>
        <input type="text" name="tabla" required>

        <div id="campos"></div>

        <div class="botones">
            <button type="button" onclick="agregarCampo()">➕ Agregar campo</button>
            <button type="submit">💾 Crear tabla</button>
            <a href="tables.php" style="margin-left: 20px; color: #00427a;">← Volver</a>
        </div>
    </form>
</body>
</html>
