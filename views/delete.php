<?php
session_start();
$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || !isset($_GET['tabla'], $_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$tabla = $_GET['tabla'];
$id = $_GET['id'];
$forzar = isset($_GET['forzar']);

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener clave primaria
    $pk = $pdo->query("SHOW KEYS FROM `$tabla` WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC)['Column_name'];

    if ($forzar) {
        // Buscar y eliminar registros relacionados
        $refQuery = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = :tabla
              AND REFERENCED_COLUMN_NAME = :columna
              AND TABLE_SCHEMA = :bd
        ");
        $refQuery->execute([
            'tabla' => $tabla,
            'columna' => $pk,
            'bd' => $conexion['base']
        ]);

        foreach ($refQuery->fetchAll() as $ref) {
            $borrar = $pdo->prepare("DELETE FROM `{$ref['TABLE_NAME']}` WHERE `{$ref['COLUMN_NAME']}` = ?");
            $borrar->execute([$id]);
        }
    }

    // Intentar eliminar registro original
    $stmt = $pdo->prepare("DELETE FROM `$tabla` WHERE `$pk` = ?");
    $stmt->execute([$id]);

    header("Location: crud.php?tabla=" . urlencode($tabla));
    exit();

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Integrity constraint violation: 1451') !== false && !$forzar) {
        // Mostrar advertencia personalizada con detalles
        $refQuery = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = :tabla
              AND REFERENCED_COLUMN_NAME = :columna
              AND TABLE_SCHEMA = :bd
        ");
        $refQuery->execute([
            'tabla' => $tabla,
            'columna' => $pk,
            'bd' => $conexion['base']
        ]);

        $mensaje = "⚠️ Este registro está relacionado con otras tablas:\\n\\n";

        foreach ($refQuery->fetchAll(PDO::FETCH_ASSOC) as $ref) {
            $detalles = $pdo->prepare("SELECT * FROM `{$ref['TABLE_NAME']}` WHERE `{$ref['COLUMN_NAME']}` = ?");
            $detalles->execute([$id]);
            $filas = $detalles->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filas as $fila) {
                $info = [];
                foreach ($fila as $campo => $valor) {
                    $info[] = "$campo: $valor";
                }
                $mensaje .= "- {$ref['TABLE_NAME']} → " . implode(" | ", $info) . "\\n";
            }
        }

        $mensaje .= "\\n¿Deseas eliminar también estas relaciones?";

        echo "<script>
            if (confirm(`$mensaje`)) {
                window.location.href = 'delete.php?tabla=" . urlencode($tabla) . "&id=" . urlencode($id) . "&forzar=1';
            } else {
                window.location.href = 'crud.php?tabla=" . urlencode($tabla) . "';
            }
        </script>";
        exit();
    } else {
        die("❌ Error al eliminar: " . $e->getMessage());
    }
}
?>
