<?php
session_start();
require_once __DIR__ . '/auditoriaHelper.php';

if (!isset($_SESSION['conexion'])) {
    die("❌ No hay conexión activa.");
}

$conexion = $_SESSION['conexion'];
$usuario = $_SESSION['usuario'] ?? 'desconocido';

$host = $conexion['host'];
$puerto = $conexion['puerto'];
$base = $conexion['base'];
$user = $conexion['usuario'];
$pass = $conexion['clave'];

// Validar nombre de archivo personalizado
$nombreArchivo = $_GET['nombre_archivo'] ?? "backup_" . date("Ymd_His");
$nombreArchivo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nombreArchivo); // Sanitizar
$nombreArchivo = rtrim($nombreArchivo, '.sql') . '.sql';

// Construir comando mysqldump
$dumpCmd = "mysqldump --host={$host} --port={$puerto} -u{$user}";
if (!empty($pass)) {
    $dumpCmd .= " -p\"{$pass}\"";
}
$dumpCmd .= " {$base}";

// Ejecutar y capturar salida
exec($dumpCmd, $output, $resultCode);

if ($resultCode !== 0) {
    echo "❌ Error al exportar la base de datos. Código: $resultCode";
    exit();
}

$contenido = implode("\n", $output);

// Registrar en auditoría
auditoriaHelper::log(
        $_SESSION["usuario"]['nombre'] ?? 'desconocido', 
        'EXPORT',
        $tabla,
        "Se exportó la base '$base' bajo el nombre $nombreArchivo"
    );

// Enviar archivo al navegador
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header("Content-Type: application/sql");
echo $contenido;
exit();
