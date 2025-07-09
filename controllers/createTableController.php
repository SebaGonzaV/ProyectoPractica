<?php
session_start();
if (!isset($_SESSION["conexion"])) {
    header("Location: ../index.php");
    exit();
}

$conexion = $_SESSION["conexion"];
$dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
$usuario = $conexion["usuario"];
$clave = $conexion["clave"];

if (!isset($_POST["tabla"], $_POST["campos"])) {
    die("❌ Faltan datos.");
}

$tabla = $_POST["tabla"];
$campos = $_POST["campos"];

try {
    $pdo = new PDO($dsn, $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columnas = [];
    $pks = [];
    $fks = [];

    foreach ($campos as $campo) {
        $nombre = $campo["nombre"];
        $tipo = $campo["tipo"];
        $def = "`$nombre` $tipo NOT NULL";
        $columnas[] = $def;

        if (!empty($campo["pk"])) {
            $pks[] = "`$nombre`";
        }

        if (!empty($campo["fk_tabla"])) {
            $referencia = $campo["fk_tabla"];
            $fks[] = "FOREIGN KEY (`$nombre`) REFERENCES `$referencia`(id)";
        }
    }

    if (!empty($pks)) {
        $columnas[] = "PRIMARY KEY (" . implode(", ", $pks) . ")";
    }
    if (!empty($fks)) {
        $columnas = array_merge($columnas, $fks);
    }

    $sql = "CREATE TABLE `$tabla` (" . implode(", ", $columnas) . ")";
    $pdo->exec($sql);

    echo "<script>
        alert('✅ Tabla \"$tabla\" creada exitosamente.');
        window.location.href = '../views/tables.php';
    </script>";

} catch (PDOException $e) {
    echo "<script>
        alert('❌ Error al crear tabla: " . addslashes($e->getMessage()) . "');
        window.location.href = '../views/tables.php';
    </script>";
}
