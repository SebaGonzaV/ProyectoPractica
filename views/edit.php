<?php
session_start();
if (!isset($_SESSION["conexion"]) || !isset($_GET["tabla"]) || !isset($_GET["id"])) {
    header("Location: ../index.php");
    exit();
}

$conexion = $_SESSION["conexion"];
$tabla = $_GET["tabla"];
$id = $_GET["id"];

$dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
$usuario = $conexion["usuario"];
$clave = $conexion["clave"];

try {
    $pdo = new PDO($dsn, $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener claves primarias
    $stmtPk = $pdo->prepare("SHOW KEYS FROM `$tabla` WHERE Key_name = 'PRIMARY'");
    $stmtPk->execute();
    $pkData = $stmtPk->fetch(PDO::FETCH_ASSOC);
    $pk = $pkData["Column_name"];

    // Obtener columnas
    $stmtCols = $pdo->query("DESCRIBE `$tabla`");
    $columnas = $stmtCols->fetchAll(PDO::FETCH_ASSOC);

    // Obtener datos del registro
    $stmtData = $pdo->prepare("SELECT * FROM `$tabla` WHERE `$pk` = ?");
    $stmtData->execute([$id]);
    $registro = $stmtData->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Registro</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        h2 {
            color: #002855;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            background: white;
            max-width: 700px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            background-color: #0072ce;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
    </style>
</head>
<body>

<h2>✏️ Editar registro en "<?= htmlspecialchars($tabla) ?>"</h2>

<form action="../controllers/updateController.php" method="POST">
    <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">
    <input type="hidden" name="pk" value="<?= $pk ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <?php foreach ($columnas as $col): ?>
        <?php $nombre = $col["Field"]; ?>
        <label><?= $nombre ?> <small>(<?= $col["Type"] ?>)</small></label>
        <input type="text" name="datos[<?= $nombre ?>]" value="<?= htmlspecialchars($registro[$nombre]) ?>" <?= $nombre === $pk ? "readonly" : "" ?>>
    <?php endforeach; ?>

    <button type="submit">💾 Guardar</button>
</form>

</body>
</html>
