
CREATE DATABASE IF NOT EXISTS ProyectoUsers;
USE ProyectoUsers;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL
);

INSERT INTO usuarios (nombre, correo, clave) VALUES
('Administrador', 'admin@uc.cl', 'admin123'),
('Usuario de prueba', 'user@uc.cl', 'user123');
