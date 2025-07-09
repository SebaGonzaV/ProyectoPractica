<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tablas'])) {
    $tablas = json_decode($_POST['tablas'], true);
    $contenido = "Listado de tablas/colecciones:\n\n";

    foreach ($tablas as $tabla) {
        $contenido .= "- $tabla\n";
    }

    header("Content-Disposition: attachment; filename=tablas_exportadas.txt");
    header("Content-Type: text/plain");
    echo $contenido;
    exit();
}
?>
