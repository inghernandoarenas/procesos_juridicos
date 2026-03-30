-- ============================================
-- BASE DE DATOS: oficina_juridica
-- ============================================
CREATE DATABASE IF NOT EXISTS oficina_juridica;
USE oficina_juridica;

-- ============================================
-- TABLA: clientes
-- ============================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL
);

-- ============================================
-- TABLA: tipos_proceso
-- ============================================
CREATE TABLE tipos_proceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL
);

-- ============================================
-- TABLA: estados_proceso
-- ============================================
CREATE TABLE estados_proceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL
);

-- ============================================
-- TABLA: procesos
-- ============================================
CREATE TABLE procesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tipo_proceso_id INT NULL,
    estado_proceso_id INT NULL,
    numero_radicado VARCHAR(50) NOT NULL,
    tipo_proceso VARCHAR(50),
    descripcion TEXT,
    estado VARCHAR(50) DEFAULT 'Activo',
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    sincronizar_api TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_proceso_id) REFERENCES tipos_proceso(id) ON DELETE SET NULL,
    FOREIGN KEY (estado_proceso_id) REFERENCES estados_proceso(id) ON DELETE SET NULL
);

-- ============================================
-- TABLA: anexos
-- ============================================
CREATE TABLE anexos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    nombre_archivo VARCHAR(255),
    ruta_archivo VARCHAR(255),
    tipo_archivo VARCHAR(50),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- ============================================
-- TABLA: actuaciones
-- ============================================
CREATE TABLE actuaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    id_api VARCHAR(50) NULL,
    fecha DATE NOT NULL,
    actuacion VARCHAR(255) NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP NULL,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    cargo VARCHAR(100),
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: notificaciones_config
-- ============================================
CREATE TABLE notificaciones_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('email', 'whatsapp', 'ambos') DEFAULT 'email',
    email VARCHAR(100),
    telefono VARCHAR(20),
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ============================================
-- TABLA: notificaciones_log
-- ============================================
CREATE TABLE notificaciones_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    actuacion_id INT NOT NULL,
    tipo_envio ENUM('email', 'whatsapp') NOT NULL,
    destinatario VARCHAR(100) NOT NULL,
    estado ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
    mensaje TEXT,
    fecha_envio TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE,
    FOREIGN KEY (actuacion_id) REFERENCES actuaciones(id) ON DELETE CASCADE
);

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Tipos de proceso
INSERT INTO tipos_proceso (nombre, descripcion) VALUES
('Civil', 'Procesos civiles y comerciales'),
('Penal', 'Procesos penales'),
('Laboral', 'Procesos laborales y de seguridad social'),
('Administrativo', 'Procesos contencioso administrativos'),
('Familia', 'Procesos de familia'),
('Contencioso', 'Procesos contencioso administrativos');

-- Estados de proceso
INSERT INTO estados_proceso (nombre, color) VALUES
('Activo', '#f39c12'),
('En espera', '#3498db'),
('Vencido', '#e74c3c'),
('Finalizado', '#2ecc71');

-- Usuario administrador (password: admin123)
INSERT INTO usuarios (nombre, email, telefono, usuario, password, cargo) VALUES
('Administrador', 'admin@local.com', '3001234567', 'admin', MD5('admin123'), 'Administrador');

-- Clientes de ejemplo
INSERT INTO clientes (nombre, apellido, email, telefono, direccion) VALUES
('Juan', 'Pérez', 'juan.perez@email.com', '3001112233', 'Calle 1 #2-3, Bogotá'),
('María', 'González', 'maria.gonzalez@email.com', '3102223344', 'Carrera 4 #5-6, Medellín'),
('Carlos', 'Rodríguez', 'carlos.rodriguez@email.com', '3203334455', 'Calle 45 #20-30, Bogotá'),
('Ana', 'Martínez', 'ana.martinez@email.com', '3014445566', 'Carrera 32 #15-25, Medellín'),
('Pedro', 'Sánchez', 'pedro.sanchez@email.com', '3025556677', 'Avenida 6 #5-10, Cali'),
('Luisa', 'Fernández', 'luisa.fernandez@email.com', '3036667788', 'Diagonal 23 #8-40, Barranquilla'),
('Diego', 'Ramírez', 'diego.ramirez@email.com', '3047778899', 'Calle 8 #10-20, Cartagena'),
('Laura', 'Torres', 'laura.torres@email.com', '3058889900', 'Carrera 12 #15-30, Santa Marta'),
('Andrés', 'Muñoz', 'andres.munoz@email.com', '3069990011', 'Avenida 3 #4-50, Bucaramanga'),
('Carolina', 'Rojas', 'carolina.rojas@email.com', '3070001122', 'Transversal 5 #8-70, Pereira');

-- Procesos de ejemplo
INSERT INTO procesos (cliente_id, tipo_proceso_id, estado_proceso_id, numero_radicado, descripcion, fecha_inicio, fecha_vencimiento, sincronizar_api) VALUES
(1, 1, 1, '2024-00126', 'Demanda ejecutiva hipotecaria', '2026-01-15', '2026-03-20', 0),
(2, 3, 1, '2024-00127', 'Proceso de fuero sindical', '2026-02-01', '2026-03-22', 0),
(3, 2, 1, '08638310500120250000600', 'Proceso penal - prueba con API', '2025-02-05', '2026-04-15', 1),
(4, 5, 2, '2024-00129', 'Divorcio contencioso', '2026-02-10', '2026-04-20', 0),
(5, 4, 1, '2024-00130', 'Nulidad de acto administrativo', '2026-01-05', '2026-05-15', 0),
(6, 1, 2, '2024-00131', 'Excepciones previas', '2026-01-10', '2026-06-01', 0),
(7, 2, 3, '2024-00132', 'Término para presentar pruebas', '2026-01-01', '2026-02-01', 0),
(8, 3, 4, '2024-00133', 'Calificación de origen de enfermedad', '2025-11-15', '2026-01-15', 0),
(9, 1, 1, '2024-00134', 'Recurso de apelación', '2026-02-18', '2026-04-30', 0),
(10, 4, 2, '2024-00135', 'Liquidación de sociedad conyugal', '2026-02-12', '2026-05-10', 0);

-- ============================================
-- AJUSTES FINALES
-- ============================================
-- Actualizar procesos existentes con relaciones correctas
UPDATE procesos SET tipo_proceso_id = 1 WHERE tipo_proceso = 'Civil' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 2 WHERE tipo_proceso = 'Penal' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 3 WHERE tipo_proceso = 'Laboral' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 4 WHERE tipo_proceso = 'Administrativo' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 5 WHERE tipo_proceso = 'Familia' AND tipo_proceso_id IS NULL;

UPDATE procesos SET estado_proceso_id = 1 WHERE estado = 'Activo' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 2 WHERE estado = 'En espera' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 3 WHERE estado = 'Vencido' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 4 WHERE estado = 'Finalizado' AND estado_proceso_id IS NULL;