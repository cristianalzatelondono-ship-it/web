-- EcoAlerta · MySQL/MariaDB (XAMPP) · nombres en español.
-- Importar en phpMyAdmin. OJO: borra y recrea la base "ecoalerta".
DROP DATABASE IF EXISTS ecoalerta;
CREATE DATABASE ecoalerta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoalerta;
CREATE TABLE perfiles(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(80) NOT NULL,
 correo VARCHAR(120) NOT NULL UNIQUE,
 clave_cifrada VARCHAR(255) NOT NULL,
 rol ENUM('ciudadano','moderador','administrador') NOT NULL DEFAULT 'ciudadano',
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE categorias_ambientales(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(80) NOT NULL UNIQUE,
 color CHAR(7) NOT NULL,
 icono VARCHAR(8) NOT NULL);
CREATE TABLE reportes(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 categoria_id INT NOT NULL,
 titulo VARCHAR(120) NOT NULL,
 descripcion TEXT NOT NULL,
 ubicacion_texto VARCHAR(160) NULL,
 latitud DECIMAL(9,6) NULL,
 longitud DECIMAL(9,6) NULL,
 foto VARCHAR(60) NULL,
 estado ENUM('pendiente','en_revision','validado','remitido','seguimiento','solucionado','rechazado') NOT NULL DEFAULT 'pendiente',
 fecha_reporte DATE NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES perfiles(id),
 FOREIGN KEY(categoria_id) REFERENCES categorias_ambientales(id),
 CHECK(latitud IS NULL OR latitud BETWEEN -90 AND 90),
 CHECK(longitud IS NULL OR longitud BETWEEN -180 AND 180),
 INDEX(estado),INDEX(categoria_id),INDEX(creado_en));
CREATE TABLE historial_estados(
 id INT AUTO_INCREMENT PRIMARY KEY,
 reporte_id INT NOT NULL,
 estado_anterior VARCHAR(20) NULL,
 estado_nuevo VARCHAR(20) NOT NULL,
 nota VARCHAR(500) NULL,
 cambiado_por INT NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(reporte_id) REFERENCES reportes(id) ON DELETE CASCADE,
 FOREIGN KEY(cambiado_por) REFERENCES perfiles(id));
CREATE TABLE evidencias_reporte(
 id INT AUTO_INCREMENT PRIMARY KEY,
 reporte_id INT NOT NULL,
 nota VARCHAR(500) NOT NULL,
 foto VARCHAR(60) NULL,
 agregado_por INT NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(reporte_id) REFERENCES reportes(id) ON DELETE CASCADE,
 FOREIGN KEY(agregado_por) REFERENCES perfiles(id));
CREATE TABLE actividades_comunitarias(
 id INT AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(120) NOT NULL,
 descripcion TEXT NOT NULL,
 fecha_evento DATETIME NOT NULL,
 lugar VARCHAR(160) NOT NULL,
 organizador VARCHAR(100) NOT NULL,
 como_participar VARCHAR(255) NOT NULL,
 creado_por INT NOT NULL,
 FOREIGN KEY(creado_por) REFERENCES perfiles(id));
CREATE TABLE participantes_actividad(
 actividad_id INT NOT NULL,
 usuario_id INT NOT NULL,
 unido_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(actividad_id,usuario_id),
 FOREIGN KEY(actividad_id) REFERENCES actividades_comunitarias(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES perfiles(id) ON DELETE CASCADE);
INSERT INTO categorias_ambientales(nombre,color,icono) VALUES
('Basura y residuos','#8a5a2b','🗑️'),('Botaderos ilegales','#b45309','🚯'),
('Ríos y quebradas','#0e7490','💧'),('Árboles y zonas verdes','#2e8b4f','🌳'),
('Contaminación del aire','#64748b','🌫️'),('Contaminación auditiva','#7c3aed','🔊');
