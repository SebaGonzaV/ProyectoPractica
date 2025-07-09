<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=ProyectoUsers", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND clave = ?");
        $stmt->execute([$correo, $clave]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['usuario'] = $usuario;
            header("Location: ../dashboard.php");
            exit();
        } else {
            header("Location: ../index.php?error=credenciales");
            exit();
        }

    } catch (PDOException $e) {
        die("Error en la conexión o consulta: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}
