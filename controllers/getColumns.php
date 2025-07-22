<?php
session_start();
if (!isset($_SESSION["conexion"])) {
    http_response_code(401);
    echo json_encode(["error" => "Sin conexión"]);
    exit();
}

if (!isset($_GET["tabla"])) {
    http_response_code(400);
    echo json_encode(["error" => "Tabla no especificada"]);
    exit();
}

$conexion = $_SESSION["conexion"];
$dsn = "{$conexion['tipo']}:host={$conexion['host']};port={$conexion['puerto']};dbname={$conexion['base']}";
$usuario = $conexion["usuario"];
$clave = $conexion["clave"];

try {
    $pdo = new PDO($dsn, $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tabla = $_GET["tabla"];
    $stmt = $pdo->prepare("DESCRIBE `$tabla`");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($columnas);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener columnas: " . $e->getMessage()]);
}
