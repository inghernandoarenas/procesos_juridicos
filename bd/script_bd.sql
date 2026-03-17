-- Crear base de datos
CREATE DATABASE IF NOT EXISTS oficina_juridica;
USE oficina_juridica;

-- =====================================================
-- TABLA: clientes
-- =====================================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: tipos_proceso
-- =====================================================
CREATE TABLE tipos_proceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: estados_proceso
-- =====================================================
CREATE TABLE estados_proceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: procesos
-- =====================================================
CREATE TABLE procesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tipo_proceso_id INT NULL,
    estado_proceso_id INT NULL,
    numero_radicado VARCHAR(50) NOT NULL,
    tipo_proceso VARCHAR(50), -- Campo legacy, se mantiene por compatibilidad
    descripcion TEXT,
    estado VARCHAR(50) DEFAULT 'Activo', -- Campo legacy, se mantiene por compatibilidad
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_proceso_id) REFERENCES tipos_proceso(id) ON DELETE SET NULL,
    FOREIGN KEY (estado_proceso_id) REFERENCES estados_proceso(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: anexos
-- =====================================================
CREATE TABLE anexos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    nombre_archivo VARCHAR(255),
    ruta_archivo VARCHAR(255),
    tipo_archivo VARCHAR(50),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: actuaciones
-- =====================================================
CREATE TABLE actuaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    id_api VARCHAR(50) NULL,
    fecha DATE NOT NULL,
    actuacion VARCHAR(255) NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERTS: Datos iniciales
-- =====================================================

-- Tipos de proceso
INSERT INTO tipos_proceso (nombre, descripcion) VALUES
('Civil', 'Procesos civiles y comerciales'),
('Penal', 'Procesos penales'),
('Laboral', 'Procesos laborales y de seguridad social'),
('Administrativo', 'Procesos contencioso administrativos'),
('Familia', 'Procesos de familia');

-- Estados de proceso
INSERT INTO estados_proceso (nombre, color) VALUES
('Activo', '#f39c12'),
('En espera', '#3498db'),
('Vencido', '#e74c3c'),
('Finalizado', '#2ecc71');

-- Clientes (10 clientes)
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

-- Procesos (con datos de ejemplo)
INSERT INTO procesos (cliente_id, tipo_proceso_id, estado_proceso_id, numero_radicado, tipo_proceso, descripcion, estado, fecha_inicio, fecha_vencimiento) VALUES
-- Proceso 1: Juan Pérez - Civil - Activo (próximo a vencer)
(1, 1, 1, '2024-00126', 'Civil', 'Demanda ejecutiva hipotecaria', 'Activo', '2026-01-15', '2026-03-20'),
-- Proceso 2: María González - Laboral - Activo (próximo a vencer)
(2, 3, 1, '2024-00127', 'Laboral', 'Proceso de fuero sindical', 'Activo', '2026-02-01', '2026-03-22'),
-- Proceso 3: Carlos Rodríguez - Penal - Activo (con radicado real para pruebas)
(3, 2, 1, '08638310500120250000600', 'Penal', 'Proceso penal - prueba con API', 'Activo', '2025-02-05', '2026-04-15'),
-- Proceso 4: Ana Martínez - Familia - En espera
(4, 5, 2, '2024-00129', 'Familia', 'Divorcio contencioso', 'En espera', '2026-02-10', '2026-04-20'),
-- Proceso 5: Pedro Sánchez - Administrativo - Activo
(5, 4, 1, '2024-00130', 'Administrativo', 'Nulidad de acto administrativo', 'Activo', '2026-01-05', '2026-05-15'),
-- Proceso 6: Luisa Fernández - Civil - En espera
(6, 1, 2, '2024-00131', 'Civil', 'Excepciones previas en proceso ejecutivo', 'En espera', '2026-01-10', '2026-06-01'),
-- Proceso 7: Diego Ramírez - Penal - Vencido
(7, 2, 3, '2024-00132', 'Penal', 'Término para presentar pruebas', 'Vencido', '2026-01-01', '2026-02-01'),
-- Proceso 8: Laura Torres - Laboral - Finalizado
(8, 3, 4, '2024-00133', 'Laboral', 'Calificación de origen de enfermedad', 'Finalizado', '2025-11-15', '2026-01-15'),
-- Proceso 9: Andrés Muñoz - Civil - Activo
(9, 1, 1, '2024-00134', 'Civil', 'Recurso de apelación', 'Activo', '2026-02-18', '2026-04-30'),
-- Proceso 10: Carolina Rojas - Familia - En espera
(10, 5, 2, '2024-00135', 'Familia', 'Liquidación de sociedad conyugal', 'En espera', '2026-02-12', '2026-05-10');

-- =====================================================
-- ACTUALIZAR: Relacionar IDs de tipos_proceso y estados_proceso
-- =====================================================
-- Actualizar procesos que tienen valores legacy pero no IDs (por si acaso)
UPDATE procesos SET tipo_proceso_id = 1 WHERE tipo_proceso = 'Civil' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 2 WHERE tipo_proceso = 'Penal' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 3 WHERE tipo_proceso = 'Laboral' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 4 WHERE tipo_proceso = 'Administrativo' AND tipo_proceso_id IS NULL;
UPDATE procesos SET tipo_proceso_id = 5 WHERE tipo_proceso = 'Familia' AND tipo_proceso_id IS NULL;

UPDATE procesos SET estado_proceso_id = 1 WHERE estado = 'Activo' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 2 WHERE estado = 'En espera' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 3 WHERE estado = 'Vencido' AND estado_proceso_id IS NULL;
UPDATE procesos SET estado_proceso_id = 4 WHERE estado = 'Finalizado' AND estado_proceso_id IS NULL;