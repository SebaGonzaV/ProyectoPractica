<?php
session_start();
if (!isset($_SESSION["conexion"])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["tabla"])) {
    die("⚠️ No se especificó la tabla.");
}

$tabla = $_GET["tabla"];
$conexion = $_SESSION["conexion"];
$dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
$usuario = $conexion["usuario"];
$clave = $conexion["clave"];

try {
    $pdo = new PDO($dsn, $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("DESCRIBE `$tabla`");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $relacionesStmt = $pdo->prepare("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :base
        AND TABLE_NAME = :tabla
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $relacionesStmt->execute(["base" => $conexion["base"], "tabla" => $tabla]);
    $relaciones = $relacionesStmt->fetchAll(PDO::FETCH_ASSOC);
    $mapaRelaciones = [];
    foreach ($relaciones as $r) {
        $mapaRelaciones[$r["COLUMN_NAME"]] = [
            "tabla" => $r["REFERENCED_TABLE_NAME"],
            "columna" => $r["REFERENCED_COLUMN_NAME"]
        ];
    }

} catch (Exception $e) {
    die("❌ Error al cargar formulario: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear registro en <?= htmlspecialchars($tabla) ?></title>
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
            max-width: 600px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #aaa;
            border-radius: 4px;
        }
        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #0072ce;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            color: #00427a;
        }
    </style>
</head>
<body>
    <h2>Crear nuevo registro en <?= htmlspecialchars($tabla) ?></h2>

    <form action="../controllers/insertController.php" method="POST">
        <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">

        <?php foreach ($columnas as $col): 
            $nombre = $col["Field"];
            $tipo = strtoupper($col["Type"]);
            $esAI = strpos($col["Extra"], "auto_increment") !== false;
            if ($esAI) continue;
        ?>
            <label><?= htmlspecialchars($nombre) ?> <span style="font-weight:normal;color:gray;">(<?= $tipo ?>)</span></label>

            <?php if (isset($mapaRelaciones[$nombre])): 
                $ref = $mapaRelaciones[$nombre];
                $q = $pdo->query("SELECT * FROM `{$ref['tabla']}`");
                $datos = $q->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <select name="datos[<?= $nombre ?>]" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($datos as $fila): 
                        $val = $fila[$ref["columna"]];
                        $desc = $val;
                        foreach ($fila as $k => $v) {
                            if ($k !== $ref["columna"] && is_string($v)) {
                                $desc = $v;
                                break;
                            }
                        }
                    ?>
                        <option value="<?= $val ?>"><?= $val ?> → <?= htmlspecialchars($desc) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input name="datos[<?= $nombre ?>]" type="text" required>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit">💾 Guardar</button>
    </form>

    <a href="crud.php?tabla=<?= urlencode($tabla) ?>">← Volver a tabla</a>
</body>
</html>
