<?php
session_start();
$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || !isset($_GET['accion'], $_GET['tabla'])) {
    header("Location: ../dashboard.php");
    exit();
}

$accion = $_GET['accion'];
$tabla = $_GET['tabla'];
$id = $_GET['id'] ?? null;

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($accion == 'eliminar' && $id) {
        $clave = $pdo->query("SHOW KEYS FROM $tabla WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC)['Column_name'];
        $stmt = $pdo->prepare("DELETE FROM $tabla WHERE $clave = ?");
        $stmt->execute([$id]);
    } elseif ($accion == 'eliminar_tabla') {
        $pdo->exec("DROP TABLE $tabla");
    } elseif ($accion == 'crear') {
        $cols = $pdo->query("DESCRIBE $tabla")->fetchAll(PDO::FETCH_COLUMN);
        $vals = array_map(fn($col) => "'demo_$col'", $cols);
        $pdo->exec("INSERT INTO $tabla VALUES (" . implode(',', $vals) . ")");
    } elseif ($accion == 'editar' && $id) {
        $col = $pdo->query("DESCRIBE $tabla")->fetch(PDO::FETCH_ASSOC)['Field'];
        $stmt = $pdo->prepare("UPDATE $tabla SET $col = CONCAT($col, '_mod') WHERE $col = ?");
        $stmt->execute([$id]);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

header("Location: ../views/crud.php?tabla=$tabla");
exit();
