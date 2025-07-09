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
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

$tabla = $_GET['tabla'] ?? '';
if (!$tabla) die("❌ Tabla no especificada.");

// Obtener relaciones
$relaciones = [];
$rel_stmt = $pdo->prepare("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = :tabla
    AND TABLE_SCHEMA = :bd
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
$rel_stmt->execute(['tabla' => $tabla, 'bd' => $conexion['base']]);
foreach ($rel_stmt->fetchAll(PDO::FETCH_ASSOC) as $rel) {
    $relaciones[$rel['COLUMN_NAME']] = [
        'tabla' => $rel['REFERENCED_TABLE_NAME'],
        'columna' => $rel['REFERENCED_COLUMN_NAME']
    ];
}

// Columnas y datos
$stmt = $pdo->query("DESCRIBE `$tabla`");
$columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT * FROM `$tabla`");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para obtener los nombres vinculados a FKs
function obtenerNombreRelacionado($pdo, $tabla_fk, $columna_fk, $valor_fk) {
    try {
        // Buscar un campo de tipo texto descriptivo
        $info = $pdo->query("DESCRIBE `$tabla_fk`")->fetchAll(PDO::FETCH_ASSOC);
        $columna_nombre = null;
        foreach ($info as $col) {
            if (
                stripos($col['Field'], 'nombre') !== false ||
                stripos($col['Field'], 'titulo') !== false ||
                stripos($col['Field'], 'descripcion') !== false
            ) {
                $columna_nombre = $col['Field'];
                break;
            }
        }
        if (!$columna_nombre) return null;

        // Consultar el valor
        $stmt = $pdo->prepare("SELECT `$columna_nombre` FROM `$tabla_fk` WHERE `$columna_fk` = :valor LIMIT 1");
        $stmt->execute(['valor' => $valor_fk]);
        $resultado = $stmt->fetchColumn();
        return $resultado ?: null;
    } catch (Exception $e) {
        return null;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD - <?= htmlspecialchars($tabla) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8fafc;
            padding: 20px;
        }
        .boton-accion {
            padding: 6px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 6px;
            background-color: #005dab;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        .boton-accion:hover {
            background-color: #004b8a;
        }
        .crud-actions {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        #buscador {
            padding: 8px;
            width: 100%;
            margin-bottom: 10px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ccc;
            vertical-align: middle;
            text-align: center;
        }
        table th {
            background-color: #f0f0f0;
        }
        .resaltado {
            background-color: #d1ecf1 !important;
        }
        .fk-info {
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            padding: 6px;
            margin-top: 4px;
            border-radius: 4px;
            font-size: 13px;
        }
        .info-fk {
            padding: 2px 6px;
            background-color: #003b6f;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .info-fk:hover {
            background-color: #005dab;
        }
    </style>
</head>
<body>
    <h2>CRUD de la tabla: <strong><?= htmlspecialchars($tabla) ?></strong></h2>

    <div class="crud-actions">
        <form action="insert.php" method="GET">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">
            <button type="submit" class="boton-accion">➕ Crear nuevo registro</button>
        </form>
        <form action="tables.php" method="GET">
            <button type="submit" class="boton-accion">⬅️ Volver a tablas</button>
        </form>
    </div>

    <input type="text" id="buscador" placeholder="🔍 Buscar en la tabla...">

    <table>
        <thead>
            <tr>
                <?php foreach ($columnas as $col): ?>
                    <th><?= htmlspecialchars($col['Field']) ?></th>
                <?php endforeach; ?>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $fila): ?>
                <tr id="registro_<?= $fila[$columnas[0]['Field']] ?>">
                    <?php foreach ($columnas as $col):
                        $campo = $col['Field'];
                        $valor = $fila[$campo];
                        if (isset($relaciones[$campo])) {
                            $tabla_fk = $relaciones[$campo]['tabla'];
                            $columna_fk = $relaciones[$campo]['columna'];
                            $nombre_ref = obtenerNombreRelacionado($pdo, $tabla_fk, $columna_fk, $valor);
                            echo "<td data-campo='" . htmlspecialchars($campo) . "'>
                                <span style='display:inline-flex; align-items:center; gap:5px;'>
                                    <a href='crud.php?tabla={$tabla_fk}&resaltar={$valor}&columna={$columna_fk}' style='color:#005dab;text-decoration:underline;'>{$valor}</a>
                                    <button type='button' class='info-fk' onclick='toggleFkInfo(this)' title='Ver relación'>ℹ️</button>
                                </span>
                                <div class='fk-info' style='display:none;'>
                                    🔗 <strong>{$tabla_fk}.{$columna_fk}</strong><br>
                                    🧷 Valor: <strong>{$valor}</strong><br>";
                                    if ($nombre_ref) {
                                        echo "🏷️ Nombre: <em>$nombre_ref</em>";
                                    }
                            echo "</div></td>";
                        } else {
                            echo "<td data-campo='" . htmlspecialchars($campo) . "'>" . htmlspecialchars($valor) . "</td>";
                        }
                    endforeach; ?>
                    <td>
                        <a href="edit.php?tabla=<?= urlencode($tabla) ?>&id=<?= urlencode($fila[$columnas[0]['Field']]) ?>&pk=<?= urlencode($columnas[0]['Field']) ?>" class="boton-accion" style="background-color: #ffc107; color: black;">✏️ Editar</a>
                        <a href="../controllers/delete_record.php?tabla=<?= urlencode($tabla) ?>&id=<?= urlencode($fila[$columnas[0]['Field']]) ?>&pk=<?= urlencode($columnas[0]['Field']) ?>" 
                        class="boton-accion" style="background-color: #dc3545;" 
                        onclick="return confirm('¿Seguro que deseas eliminar este registro?')">
                        🗑️ Eliminar
</a>


                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Buscador
        document.getElementById("buscador").addEventListener("keyup", function () {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll("table tbody tr");
            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(filtro) ? "" : "none";
            });
        });

        // Resaltado preciso
        const params = new URLSearchParams(window.location.search);
        const resaltar = params.get("resaltar");
        const columna = params.get("columna");

        if (resaltar && columna) {
            const filas = document.querySelectorAll("tbody tr");
            filas.forEach(fila => {
                const celda = fila.querySelector(`td[data-campo='${columna}']`);
                if (celda && celda.textContent.includes(resaltar)) {
                    fila.classList.add("resaltado");
                }
            });
        }

        // Panel FK toggle
        function toggleFkInfo(btn) {
            const info = btn.closest("td").querySelector(".fk-info");
            info.style.display = info.style.display === "none" ? "block" : "none";
        }
    </script>

    <?php if (isset($_GET['error'])): ?>
    <script>
        alert("<?= htmlspecialchars($_GET['error']) ?>");
    </script>
    <?php endif; ?>

</body>
</html>
