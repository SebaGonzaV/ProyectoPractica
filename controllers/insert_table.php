<?php
session_start();
$conexion = $_SESSION['conexion'] ?? null;

if (!$conexion || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$nombreTabla = $_POST['nombre_tabla'] ?? '';
$columnas = $_POST['columnas'] ?? [];

if (empty($nombreTabla) || empty($columnas)) {
    die("Faltan datos para crear la tabla.");
}

try {
    $dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
    $pdo = new PDO($dsn, $conexion['usuario'], $conexion['clave']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cols = [];
    $pks = [];
    $fks = [];

    foreach ($columnas as $col) {
        if (!empty($col['nombre']) && !empty($col['tipo'])) {
            $nombre = $col['nombre'];
            $tipo = $col['tipo'];
            $esPK = isset($col['pk']);
            $esFK = isset($col['fk']);
            $fkTabla = $col['fk_tabla'] ?? '';
            $fkColumna = $col['fk_columna'] ?? '';

            $cols[] = "`$nombre` $tipo";
            if ($esPK) $pks[] = "`$nombre`";
            if ($esFK && $fkTabla && $fkColumna) {
                $fks[] = "FOREIGN KEY (`$nombre`) REFERENCES `$fkTabla`(`$fkColumna`)";
            }
        }
    }

    if (empty($cols)) {
        die("Debe definir al menos una columna válida.");
    }

    $sql = "CREATE TABLE `$nombreTabla` (
";
    $sql .= implode(",
", $cols);
    if (!empty($pks)) {
        $sql .= ",
PRIMARY KEY (" . implode(", ", $pks) . ")";
    }
    if (!empty($fks)) {
        $sql .= ",
" . implode(",
", $fks);
    }
    $sql .= "
)";

    $pdo->exec($sql);

    echo "<script>alert('✅ Tabla creada correctamente'); window.location.href='../views/tables.php';</script>";
    exit();

} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage());
}
