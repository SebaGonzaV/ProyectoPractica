<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

// Validar que existe conexión activa en la sesión
if (!isset($_SESSION['conexion'])) {
    echo "<script>
        alert('⚠️ La sesión ha expirado o no se estableció una conexión. Por favor, vuelve a iniciar sesión.');
        window.location.href = '../index.php';
    </script>";
    exit();
}

// Validar datos del formulario
if (!isset($_POST['tabla'], $_POST['campos'])) {
    echo "<script>
        alert('❌ Error: No se recibieron los datos necesarios para crear la tabla.');
        window.location.href = '../views/create_table.php';
    </script>";
    exit();
}

$conexion = $_SESSION['conexion'];
$tabla = htmlspecialchars(trim($_POST['tabla']));
$campos = $_POST['campos'];

try {
    // Construir conexión PDO
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columnDefs = [];
    $primaryKeys = [];
    $foreignKeys = [];

    foreach ($campos as $campo) {
        $nombre = htmlspecialchars(trim($campo['nombre']));
        $tipo = htmlspecialchars(trim($campo['tipo']));
        $extra = strtoupper(htmlspecialchars(trim($campo['extra'] ?? '')));
        $fk_tabla = htmlspecialchars(trim($campo['fk_tabla'] ?? ''));
        $fk_columna = htmlspecialchars(trim($campo['fk_columna'] ?? ''));

        if (empty($nombre) || empty($tipo)) continue;

        $linea = "`$nombre` $tipo";

        if (!empty($campo['pk']) && $campo['pk'] === "1") {
            $primaryKeys[] = "`$nombre`";
        }

        if ($extra === "AUTO_INCREMENT") {
            $linea .= " AUTO_INCREMENT";
        }

        $columnDefs[] = $linea;

        // Validar existencia de tabla y campo referenciado
        if (!empty($fk_tabla) && !empty($fk_columna)) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$fk_tabla'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SHOW KEYS FROM `$fk_tabla` WHERE Column_name = '$fk_columna'");
                if ($stmt->rowCount() > 0) {
                    $foreignKeys[] = "FOREIGN KEY (`$nombre`) REFERENCES `$fk_tabla`(`$fk_columna`)";
                } else {
                    throw new Exception("⚠️ El campo '$fk_columna' de la tabla '$fk_tabla' no tiene índice (PK/UNIQUE).");
                }
            } else {
                throw new Exception("⚠️ La tabla referenciada '$fk_tabla' no existe.");
            }
        }
    }

    // Agregar clave primaria
    if (count($primaryKeys) > 0) {
        $columnDefs[] = "PRIMARY KEY (" . implode(", ", $primaryKeys) . ")";
    }

    // Combinar campos normales + claves foráneas
    $todo = array_merge($columnDefs, $foreignKeys);
    $sql = "CREATE TABLE `$tabla` (" . implode(", ", $todo) . ") ENGINE=InnoDB";

    // Ejecutar creación
    $pdo->exec($sql);

    // Guardar auditoría
    auditoriaHelper::log(
        $_SESSION["usuario"]['nombre'] ?? 'desconocido', 
        'CREATE',
        $tabla,
        "Se creó la tabla '$tabla' con " . count($campos) . " columnas"
    );

    echo "<script>
        alert('✅ Tabla \"$tabla\" creada exitosamente.');
        window.location.href = '../views/tables.php';
    </script>";
    exit();

} catch (Exception $e) {
    // Guardar error en archivo de logs
    file_put_contents(__DIR__ . '/../logs/auditoria.txt', "[" . date("Y-m-d H:i:s") . "] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);

    echo "<script>
        alert('❌ Error al crear tabla: " . addslashes($e->getMessage()) . "');
        window.location.href = '../views/create_table.php';
    </script>";
    exit();
}
