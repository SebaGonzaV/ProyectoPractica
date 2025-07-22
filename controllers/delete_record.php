<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

if (!isset($_SESSION["conexion"])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["tabla"], $_GET["id"], $_GET["pk"])) {
    die("❌ Parámetros incompletos para eliminación.");
}

$conexion = $_SESSION["conexion"];
$tabla = $_GET["tabla"];
$id = $_GET["id"];
$pk = $_GET["pk"];

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion["usuario"], $conexion["clave"]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM `$tabla` WHERE `$pk` = ?");
    $stmt->execute([$id]);

    auditoriaHelper::log(
    $_SESSION["usuario"]['nombre'] ?? 'desconocido', // usuario
    'DELETE',                                        // acción
    $tabla,                                          // tabla
    "Se eliminó un registro con id $id"             // detalle
    );



    header("Location: ../views/crud.php?tabla=$tabla&msg=deleted");
    exit();

} catch (PDOException $e) {
    $msg = $e->getMessage();

    if (str_contains($msg, "Integrity constraint violation")) {
        $stmtRel = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = :tabla
              AND REFERENCED_COLUMN_NAME = :columna
              AND TABLE_SCHEMA = :bd
        ");
        $stmtRel->execute([
            'tabla' => $tabla,
            'columna' => $pk,
            'bd' => $conexion['base']
        ]);
        $referencias = $stmtRel->fetchAll(PDO::FETCH_ASSOC);

        $detalles = array_map(function($rel) {
            return "{$rel['TABLE_NAME']} ({$rel['COLUMN_NAME']})";
        }, $referencias);

        $detalleMsg = implode(", ", $detalles);
        $mensaje = urlencode("❌ No se puede eliminar este registro porque está siendo referenciado desde: $detalleMsg.");
    } else {
        $mensaje = urlencode("❌ Error al eliminar: " . $msg);
    }

    header("Location: ../views/crud.php?tabla=$tabla&error=$mensaje");
    exit();
}
