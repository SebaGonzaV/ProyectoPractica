<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Proyecto Practica Seba</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Ingreso al Sistema</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'credenciales'): ?>
            <div class="error" style="color: red; margin-bottom: 10px;">
                ⚠️ Credenciales incorrectas. Intenta de nuevo.
            </div>
        <?php endif; ?>

        <form action="controllers/loginController.php" method="POST">
            <input type="text" name="correo" placeholder="Correo" required>
            <input type="password" name="clave" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
