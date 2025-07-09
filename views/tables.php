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
    <title>Relaciones entre Tablas</title>
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            padding: 30px;
        }
        h2 {
            color: #002855;
        }
        #network {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .tabla-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px #ccc;
        }
        select, button {
            padding: 10px;
            font-size: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }
        button {
            background-color: #0072ce;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
    </style>
</head>
<body>
    <h2>🧩 Diagrama de Relaciones</h2>
    <div id="network"></div>

    <div class="tabla-form">
        <form action="create_table.php" method="GET">
            <button type="submit">➕ Crear nueva tabla</button>
        </form>

        <form action="dashboard.php" method="GET">
            <button type="submit">⬅️ Volver al panel</button>
        </form>

        <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta tabla?')">
            <select name="tabla_a_eliminar" required>
                <option value="" disabled selected>Selecciona una tabla</option>
                <?php foreach ($tablas as $tabla): ?>
                    <option value="<?= $tabla ?>"><?= $tabla ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">🗑️ Eliminar tabla</button>
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
            layout: { improvedLayout: true },
            physics: {
                enabled: true,
                solver: "forceAtlas2Based",
                forceAtlas2Based: {
                    gravitationalConstant: -30,
                    springLength: 120,
                    springConstant: 0.05
                },
                stabilization: { iterations: 250 }
            },
            nodes: {
                shape: 'box',
                font: { face: 'Segoe UI' }
            }
        };
        const network = new vis.Network(container, data, options);

        network.on("doubleClick", function (params) {
            if (params.nodes.length > 0) {
                const tabla = params.nodes[0];
                window.location.href = "crud.php?tabla=" + encodeURIComponent(tabla);
            }
        });
    </script>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tabla_a_eliminar"])) {
    try {
        $tabla = $_POST["tabla_a_eliminar"];
        $pdo->exec("DROP TABLE `$tabla`");
        echo "<script>alert('✅ Tabla $tabla eliminada correctamente'); location.href='tables.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('❌ Error al eliminar tabla: " . $e->getMessage() . "');</script>";
    }
}
?>
</body>
</html>

