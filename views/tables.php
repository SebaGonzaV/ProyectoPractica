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

    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $relacionesStmt = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '{$conexion['base']}'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $relaciones = $relacionesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Tablas</title>
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h2 {
            color: #00417d;
        }
        #network {
            width: 100%;
            height: 500px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        form {
            margin: 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        select, button, input[type="text"] {
            padding: 8px;
            font-size: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #0077cc;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        input[type="text"] {
            width: 200px;
        }
    </style>
</head>
<body>

<h2>Relaciones entre Tablas (<?= htmlspecialchars($conexion['base']) ?>)</h2>

<div id="network"></div>

<div class="acciones">
    <form action="create_table.php" method="GET">
        <button type="submit">➕ Crear nueva tabla</button>
    </form>

    <form action="dashboard.php" method="GET">
        <button type="submit">⬅️ Volver al panel</button>
    </form>

    <select id="tablaSeleccionada" required>
        <option value="" disabled selected>Selecciona tabla a eliminar</option>
        <?php foreach ($tablas as $tabla): ?>
            <option value="<?= $tabla ?>"><?= $tabla ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" onclick="eliminarTabla()">🗑️ Eliminar tabla</button>

    <form action="auditoria.php" method="GET">
        <button type="submit">📋 Ver auditoría</button>
    </form>

    <!-- 🗃️ Exportar base .sql con nombre definido -->
    <form action="../controllers/export_sql.php" method="GET" onsubmit="return validarNombreArchivo()">
        <input type="text" name="nombre_archivo" placeholder="Nombre archivo SQL" required>
        <button type="submit">🗃️ Exportar Base .sql</button>
    </form>
</div>

<script>
    const nodes = new vis.DataSet([
        <?php foreach ($tablas as $tabla): ?>
        { id: '<?= $tabla ?>', label: '<?= $tabla ?>', shape: 'box' },
        <?php endforeach; ?>
    ]);

    const edges = new vis.DataSet([
        <?php foreach ($relaciones as $rel): ?>
        { from: '<?= $rel["TABLE_NAME"] ?>', to: '<?= $rel["REFERENCED_TABLE_NAME"] ?>', arrows: 'to' },
        <?php endforeach; ?>
    ]);

    const container = document.getElementById('network');
    const data = { nodes: nodes, edges: edges };
    const options = {
        layout: {
            improvedLayout: true
        },
        physics: {
            stabilization: true
        }
    };
    const network = new vis.Network(container, data, options);

    network.on("doubleClick", function (params) {
        if (params.nodes.length > 0) {
            const tabla = params.nodes[0];
            window.location.href = "crud.php?tabla=" + encodeURIComponent(tabla);
        }
    });

    function eliminarTabla() {
        const select = document.getElementById("tablaSeleccionada");
        const tabla = select.value;
        if (!tabla) {
            alert("⚠️ Debes seleccionar una tabla primero.");
            return;
        }
        if (confirm(`¿Estás seguro de eliminar la tabla ${tabla}?`)) {
            window.location.href = `../controllers/delete_table.php?tabla=${encodeURIComponent(tabla)}`;
        }
    }

    function validarNombreArchivo() {
        const input = document.querySelector('input[name="nombre_archivo"]');
        const nombre = input.value.trim();
        if (nombre === '') {
            alert("⚠️ Debes ingresar un nombre para el archivo.");
            return false;
        }
        if (!/^[a-zA-Z0-9_\-]+$/.test(nombre)) {
            alert("❌ El nombre solo puede contener letras, números, guiones y guiones bajos.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>
