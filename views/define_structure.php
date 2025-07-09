<?php
if (!isset($_POST['nombre_tabla'], $_POST['num_columnas'])) {
    header("Location: create_table.php");
    exit();
}

$nombre_tabla = $_POST['nombre_tabla'];
$num_columnas = (int)$_POST['num_columnas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Definir estructura</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="dash-container">
    <h2>Estructura para: <?= htmlspecialchars($nombre_tabla) ?></h2>
    <form action="../controllers/createTableController.php" method="POST">
        <input type="hidden" name="nombre_tabla" value="<?= htmlspecialchars($nombre_tabla) ?>">
        <input type="hidden" name="num_columnas" value="<?= $num_columnas ?>">

        <?php for ($i = 0; $i < $num_columnas; $i++): ?>
            <fieldset style="margin-bottom:10px">
                <legend>Columna <?= $i + 1 ?></legend>
                <label>Nombre:</label>
                <input type="text" name="columnas[<?= $i ?>][nombre]" required>

                <label>Tipo:</label>
                <select name="columnas[<?= $i ?>][tipo]">
                    <option value="INT">INT</option>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="DATE">DATE</option>
                    <option value="TEXT">TEXT</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                </select>

                <label>¿Clave primaria?</label>
                <input type="checkbox" name="columnas[<?= $i ?>][pk]" value="1">

                <label>¿Permite nulo?</label>
                <input type="checkbox" name="columnas[<?= $i ?>][null]" value="1">

                <label>¿Auto Increment?</label>
                <input type="checkbox" name="columnas[<?= $i ?>][ai]" value="1">
            </fieldset>
        <?php endfor; ?>

        <button type="submit">Crear tabla</button>
    </form>
</div>
</body>
</html>