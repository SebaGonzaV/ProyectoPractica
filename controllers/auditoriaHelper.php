<?php
class auditoriaHelper {
    public static function log($usuario, $accion, $tabla, $detalle) {
        // Zona horaria de Chile (Santiago)
        date_default_timezone_set("America/Santiago");

        // Fecha y hora actual
        $fecha = date("d-m-Y H:i:s");

        // 📂 Asegura existencia del directorio /logs
        $rutaLogs = __DIR__ . '/../logs';
        if (!is_dir($rutaLogs)) {
            mkdir($rutaLogs, 0777, true);
        }

        //  Usa nombre de la base de datos actual desde la sesión
        $nombreBase = $_SESSION['conexion']['base'] ?? 'desconocida';

        //  Ruta del archivo de auditoría por base
        $archivo = $rutaLogs . "/auditoria_{$nombreBase}.txt";

        //  Línea a registrar
        $linea = "[$fecha] Usuario: $usuario | Acción: $accion | Tabla: $tabla | Detalle: $detalle\n";

        // Guarda en el archivo
        file_put_contents($archivo, $linea, FILE_APPEND);
    }
}
