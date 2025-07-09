<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Proyecto Practica Seba</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="dash-container">
        <h2>Conectar a una Base de Datos</h2>
        <form action="../controllers/connectionController.php" method="POST" class="form-box">
            <h3>Conectar a una base de datos</h3>

            <label>Tipo de Base</label>
            <select name="tipo" required>
                <option value="">Selecciona tipo</option>
                <option value="mysql">MySQL</option>
                <option value="pgsql">PostgreSQL</option>
                <option value="mongodb">MongoDB</option>
            </select>

            <label>Host</label>
            <input type="text" name="host" value="localhost" required>

            <label>Puerto</label>
            <input type="text" name="puerto" value="3306" required>

            <label>Base de datos</label>
            <input type="text" name="base" required>

            <label>Usuario</label>
            <input type="text" name="usuario" required>

            <label>Contraseña</label>
            <input type="password" name="clave">

            <button type="submit">Conectar</button>
        </form>

        <form action="../logout.php" method="POST" style="margin-top: 20px;">
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</body>
</html>
