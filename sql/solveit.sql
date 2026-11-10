CREATE DATABASE IF NOT EXISTS solveit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solveit;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS problemas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  expresion VARCHAR(255) NOT NULL,          -- la expresión confirmada por el usuario
  latex VARCHAR(255) NULL,
  tipo ENUM('operacion','ecuacion') NOT NULL,
  resultado_texto VARCHAR(255) NOT NULL,
  pasos_json LONGTEXT NOT NULL,
  verificacion ENUM('verificado','no_verificado','inconsistente') NOT NULL,
  nivel ENUM('basico','avanzado') NOT NULL DEFAULT 'basico',
  imagen_ruta VARCHAR(255) NULL,            -- PNG de lo escrito (opcional)
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
