<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || !isset($_POST['tabla'], $_POST['id'], $_POST['pk'], $_POST['datos'])) {
    header("Location: ../dashboard.php");
    exit();
}

$tabla = $_POST['tabla'];
$pk = $_POST['pk'];
$id = $_POST['id'];
$datos = $_POST['datos'];

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $campos = [];
    $valores = [];

    foreach ($datos as $col => $valor) {
        if ($col !== $pk) {
            $campos[] = "`$col` = ?";
            $valores[] = $valor;
        }
    }

    $valores[] = $id;

    $sql = "UPDATE `$tabla` SET " . implode(", ", $campos) . " WHERE `$pk` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    auditoriaHelper::log(
    $_SESSION["usuario"]['nombre'] ?? 'desconocido', // usuario
    'UPDATE',                                        // acción
    $tabla,                                          // tabla
    "Se modificó un registro con id $id"             // detalle
);



    header("Location: ../views/crud.php?tabla=$tabla");
    exit();

} catch (Exception $e) {
    die("❌ Error al actualizar: " . $e->getMessage());
}
