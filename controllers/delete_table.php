<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || !isset($_GET['tabla'])) {
    header("Location: ../views/tables.php");
    exit();
}

$tabla = $_GET['tabla'];
$forzar = isset($_GET['forzar']);

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($forzar) {
        $refQuery = $pdo->prepare("
            SELECT TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = :tabla
              AND TABLE_SCHEMA = :bd
        ");
        $refQuery->execute([
            'tabla' => $tabla,
            'bd' => $conexion['base']
        ]);

        foreach ($refQuery->fetchAll(PDO::FETCH_ASSOC) as $ref) {
            $pdo->exec("DROP TABLE IF EXISTS `{$ref['TABLE_NAME']}`");

            // Registrar en auditoría cada tabla foránea eliminada
            $usuarioLog = isset($_SESSION["usuario"]["usuario"]) ? $_SESSION["usuario"]["usuario"] : "desconocido";
            auditoriaHelper::log(
                $usuarioLog,
                'BORRAR TABLA FORÁNEA',
                $ref['TABLE_NAME'],
                "Se eliminó la tabla relacionada '{$ref['TABLE_NAME']}' por forzado desde '$tabla'"
            );
        }
    }

    // Eliminar tabla principal
    $pdo->exec("DROP TABLE IF EXISTS `$tabla`");

    // ✅ Auditoría
    $usuarioLog = isset($_SESSION["usuario"]["nombre"]) ? $_SESSION["usuario"]["nombre"] : "desconocido";
    auditoriaHelper::log(
    $usuarioLog,
    'DELETE',
    $tabla,
    "Se borró la tabla '$tabla'"
);

    header("Location: ../views/tables.php");
    exit();

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Integrity constraint violation: 1451') !== false && !$forzar) {
        $refQuery = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = :tabla
              AND TABLE_SCHEMA = :bd
        ");
        $refQuery->execute([
            'tabla' => $tabla,
            'bd' => $conexion['base']
        ]);

        $mensaje = "⚠️ La tabla '$tabla' está relacionada con otras tablas:\\n\\n";

        foreach ($refQuery->fetchAll(PDO::FETCH_ASSOC) as $ref) {
            $mensaje .= "- {$ref['TABLE_NAME']} (columna: {$ref['COLUMN_NAME']})\\n";
        }

        $mensaje .= "\\n¿Deseas eliminar también estas tablas y continuar?";

        echo "<script>
            if (confirm(`$mensaje`)) {
                window.location.href = 'delete_table.php?tabla=" . urlencode($tabla) . "&forzar=1';
            } else {
                window.location.href = '../views/tables.php';
            }
        </script>";
        exit();
    } else {
        echo "<script>alert('❌ Error al eliminar tabla: " . addslashes($e->getMessage()) . "'); window.location.href = '../views/tables.php';</script>";
        exit();
    }
}
