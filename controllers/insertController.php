<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

if (!isset($_SESSION['conexion']) || !isset($_POST['tabla'], $_POST['datos'])) {
    header("Location: ../index.php");
    exit();
}

$conexion = $_SESSION['conexion'];
$tabla = $_POST['tabla'];
$datos = $_POST['datos'];

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columnas = array_keys($datos);
    $marcadores = array_fill(0, count($columnas), '?');
    $valores = array_values($datos);

    $sql = "INSERT INTO `$tabla` (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $marcadores) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    auditoriaHelper::log(
    $_SESSION["usuario"]['nombre'] ?? 'desconocido', // usuario
    'INSERT',                                        // acción
    $tabla,                                          // tabla
    "Se insertó un registro con id $id"             // detalle
);



    echo "<script>
        alert('✅ Registro creado correctamente en \"$tabla\"');
        window.location.href = '../views/crud.php?tabla=" . urlencode($tabla) . "';
    </script>";
    exit();

} catch (PDOException $e) {
    echo "<script>
        alert('❌ Error al insertar: " . addslashes($e->getMessage()) . "');
        window.location.href = '../views/crud.php?tabla=" . urlencode($tabla) . "';
    </script>";
    exit();
}
