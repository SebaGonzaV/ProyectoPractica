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
    $opcionesTablas = "";
    foreach ($tablasExistentes as $t) {
        $opcionesTablas .= "<option value='$t'>$t</option>";
    }
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

                <label>¿Tabla foránea?</label>
                <select name="campos[${index}][fk_tabla]" onchange="cargarColumnasForaneas(this, ${index})">
                    <option value="">No</option>
                    <?= $opcionesTablas ?>
                </select>

                <label>¿Columna foránea?</label>
                <select name="campos[${index}][fk_columna]" id="columna_fk_${index}">
                    <option value="">-- Selecciona tabla primero --</option>
                </select>
            `;

            contenedor.appendChild(div);
        }

        function cargarColumnasForaneas(select, index) {
            const tabla = select.value;
            const columnaSelect = document.getElementById("columna_fk_" + index);
            columnaSelect.innerHTML = '<option value="">Cargando...</option>';

            if (!tabla) {
                columnaSelect.innerHTML = '<option value="">-- Selecciona tabla primero --</option>';
                return;
            }

            fetch(`../controllers/getColumns.php?tabla=${tabla}`)
                .then(res => res.json())
                .then(data => {
                    columnaSelect.innerHTML = "";
                    data.forEach(col => {
                        const option = document.createElement("option");
                        option.value = col;
                        option.textContent = col;
                        columnaSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    console.error("Error al cargar columnas:", err);
                    columnaSelect.innerHTML = '<option value="">Error al cargar columnas</option>';
                });
        }
    </script>
</body>
</html>
