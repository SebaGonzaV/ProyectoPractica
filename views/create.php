<?php
session_start();
$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || !isset($_GET['tabla'])) {
    header("Location: ../dashboard.php");
    exit();
}

$tabla = $_GET['tabla'];

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($conexion['tipo'] === 'mysql') {
        $stmt = $pdo->query("DESCRIBE $tabla");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = :tabla");
        $stmt->execute(['tabla' => $tabla]);
        $columnas = array_map(fn($row) => ['Field' => $row['column_name']], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear nuevo registro - <?= htmlspecialchars($tabla) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <script>
        function confirmar() {
            return confirm("¿Deseas crear este nuevo registro?");
        }
    </script>
</head>
<body>
<div class="dash-container">
    <h2>Nuevo registro en <span style="color:#003366"><?= htmlspecialchars($tabla) ?></span></h2>
    <form action="../controllers/insertController.php" method="POST" onsubmit="return confirmar();">
        <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla) ?>">

        <?php foreach ($columnas as $col): ?>
            <label><?= htmlspecialchars($col['Field']) ?></label>
            <input type="text" name="datos[<?= htmlspecialchars($col['Field']) ?>]">
        <?php endforeach; ?>

        <button type="submit">Crear</button>
    </form>
    <br>
    <a href="crud.php?tabla=<?= htmlspecialchars($tabla) ?>">← Volver</a>
</div>
</body>
</html>