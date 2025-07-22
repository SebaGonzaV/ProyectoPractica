<?php
session_start();
if (!isset($_SESSION["conexion"])) {
    header("Location: ../index.php");
    exit();
}

$conexion = $_SESSION["conexion"];
$base = $conexion['base'];
$usuario = $_SESSION['usuario'] ?? 'Invitado';

$archivo_log = __DIR__ . "/../logs/auditoria_{$base}.txt";

if (isset($_GET['descargar']) && file_exists($archivo_log)) {
    header("Content-Disposition: attachment; filename=auditoria_{$base}.txt");
    header("Content-Type: text/plain");
    readfile($archivo_log);
    exit();
}

$registros = [];
if (file_exists($archivo_log)) {
    $registros = file($archivo_log, FILE_IGNORE_NEW_LINES);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - <?= htmlspecialchars($base) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 25px;
            background-color: #f9f9f9;
        }
        h2 {
            color: #00417d;
            margin-bottom: 15px;
        }
        .acciones {
            margin-bottom: 20px;
        }
        .acciones form {
            display: inline-block;
            margin-right: 10px;
        }
        button {
            background-color: #0077cc;
            color: white;
            border: none;
            padding: 8px 14px;
            font-size: 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        pre {
            background-color: white;
            border: 1px solid #ccc;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<h2>Auditoría de la base <u><?= htmlspecialchars($base) ?></u></h2>

<div class="acciones">
    <form action="tables.php" method="GET">
        <button type="submit">⬅️ Volver a tablas</button>
    </form>
    <form method="GET">
        <input type="hidden" name="descargar" value="1">
        <button type="submit">📥 Descargar archivo</button>
    </form>
</div>

<?php if (empty($registros)): ?>
    <p>⚠️ No hay registros de auditoría aún.</p>
<?php else: ?>
    <pre>
<?php foreach ($registros as $linea): ?>
<?= htmlspecialchars($linea) . "\n" ?>
<?php endforeach; ?>
    </pre>
<?php endif; ?>

</body>
</html>
