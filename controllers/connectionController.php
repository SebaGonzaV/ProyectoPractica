<?php
// controllers/connectionController.php

session_start();

// Verifica si el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura los datos enviados desde el formulario
    $tipo = $_POST['tipo'];
    $host = $_POST['host'];
    $puerto = $_POST['puerto'];
    $base = $_POST['base'];
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Guarda los datos en sesión para usarlos luego
    $_SESSION['conexion'] = [
        'tipo' => $tipo,
        'host' => $host,
        'puerto' => $puerto,
        'base' => $base,
        'usuario' => $usuario,
        'clave' => $clave
    ];

    // Redirige a la vista de tablas
    header("Location: ../views/tables.php");
    exit();
} else {
    // Si no es una petición POST, redirige al dashboard
    header("Location: ../dashboard.php");
    exit();
}
?>
