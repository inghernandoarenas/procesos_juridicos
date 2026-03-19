-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2026 at 08:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oficina_juridica`
--

-- --------------------------------------------------------

--
-- Table structure for table `actuaciones`
--

CREATE TABLE `actuaciones` (
  `id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `id_api` varchar(50) DEFAULT NULL,
  `fecha` date NOT NULL,
  `actuacion` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `actuaciones`
--

INSERT INTO `actuaciones` (`id`, `proceso_id`, `id_api`, `fecha`, `actuacion`, `observaciones`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 3, '2262021351', '2025-07-08', 'AUTO ADMITE DEMANDA', ' ', '2026-03-15 21:44:40', NULL, NULL, NULL),
(2, 3, '3369562291', '2025-06-12', 'AUTO ADMITE DEMANDA', ' ', '2026-03-15 21:44:40', NULL, NULL, NULL),
(3, 3, '2261179611', '2025-05-23', 'AUTO INADMITE LA DEMANDA', ' ', '2026-03-15 21:44:40', NULL, NULL, NULL),
(4, 3, '3369422071', '2025-05-23', 'AUTO INADMITE LA DEMANDA', ' ', '2026-03-15 21:44:40', NULL, NULL, NULL),
(5, 3, '2258316081', '2025-02-05', 'RADICACIÓN', ' ', '2026-03-15 21:44:40', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `anexos`
--

CREATE TABLE `anexos` (
  `id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anexos`
--

INSERT INTO `anexos` (`id`, `proceso_id`, `nombre_archivo`, `ruta_archivo`, `tipo_archivo`, `fecha_subida`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 3, 'Cuenta_hugo_ruiz__2025.pdf', 'uploads/69ba132b885ef.pdf', 'application/pdf', '2026-03-18 02:51:23', NULL, NULL, NULL),
(2, 3, 'Hoja de vida Hugo Ruiz.pdf', 'uploads/69ba133a26e78.pdf', 'application/pdf', '2026-03-18 02:51:38', NULL, NULL, NULL),
(3, 3, 'Carta cancelacion pac.pdf', 'uploads/69ba134c12bf4.pdf', 'application/pdf', '2026-03-18 02:51:56', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `email`, `telefono`, `direccion`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 'Juan', 'Pérez', 'juan.perez@email.com', '3001112233', 'Calle 1 #2-3, Bogotá', '2026-03-15 21:25:46', NULL, NULL, NULL),
(2, 'María', 'González', 'maria.gonzalez@email.com', '3102223344', 'Carrera 4 #5-6, Medellín', '2026-03-15 21:25:46', NULL, NULL, NULL),
(3, 'Carlos', 'Rodríguez', 'carlos.rodriguez@email.com', '3203334455', 'Calle 45 #20-30, Bogotá', '2026-03-15 21:25:46', NULL, NULL, NULL),
(4, 'Ana', 'Martínez', 'ana.martinez@email.com', '3014445566', 'Carrera 32 #15-25, Medellín', '2026-03-15 21:25:46', NULL, NULL, NULL),
(5, 'Pedro', 'Sánchez', 'pedro.sanchez@email.com', '3025556677', 'Avenida 6 #5-10, Cali', '2026-03-15 21:25:46', NULL, NULL, NULL),
(6, 'Luisa', 'Fernández', 'luisa.fernandez@email.com', '3036667788', 'Diagonal 23 #8-40, Barranquilla', '2026-03-15 21:25:46', NULL, NULL, NULL),
(7, 'Diego', 'Ramírez', 'diego.ramirez@email.com', '3047778899', 'Calle 8 #10-20, Cartagena', '2026-03-15 21:25:46', NULL, NULL, NULL),
(8, 'Laura', 'Torres', 'laura.torres@email.com', '3058889900', 'Carrera 12 #15-30, Santa Marta', '2026-03-15 21:25:46', NULL, NULL, NULL),
(9, 'Andrés', 'Muñoz', 'andres.munoz@email.com', '3069990011', 'Avenida 3 #4-50, Bucaramanga', '2026-03-15 21:25:46', NULL, NULL, NULL),
(10, 'Carolina', 'Rojas', 'carolina.rojas@email.com', '3070001122', 'Transversal 5 #8-70, Pereira', '2026-03-15 21:25:46', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `estados_proceso`
--

CREATE TABLE `estados_proceso` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT '#3498db',
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estados_proceso`
--

INSERT INTO `estados_proceso` (`id`, `nombre`, `color`, `activo`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 'Activo', '#f39c12', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(2, 'En espera', '#3498db', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(3, 'Vencido', '#e74c3c', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(4, 'Finalizado', '#2ecc71', 1, '2026-03-15 21:25:46', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `procesos`
--

CREATE TABLE `procesos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_proceso_id` int(11) DEFAULT NULL,
  `estado_proceso_id` int(11) DEFAULT NULL,
  `numero_radicado` varchar(50) NOT NULL,
  `tipo_proceso` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Activo',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procesos`
--

INSERT INTO `procesos` (`id`, `cliente_id`, `tipo_proceso_id`, `estado_proceso_id`, `numero_radicado`, `tipo_proceso`, `descripcion`, `estado`, `fecha_inicio`, `fecha_vencimiento`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 8, 6, 1, '2024-00126', 'Civil', 'Demanda ejecutiva hipotecaria', 'Activo', '2026-01-15', '2026-03-20', '2026-03-15 21:25:46', NULL, NULL, NULL),
(2, 10, 3, 1, '2024-00127', NULL, 'Proceso de fuero sindical', NULL, '2026-02-01', '2026-03-22', '2026-03-15 21:25:46', NULL, NULL, NULL),
(3, 3, 4, 1, '08638310500120250000600', 'Penal', 'Proceso penal - prueba con API', 'Activo', '2025-02-05', '2026-04-15', '2026-03-15 21:25:46', NULL, NULL, NULL),
(4, 4, 5, 2, '2024-00129', 'Familia', 'Divorcio contencioso', 'En espera', '2026-02-10', '2026-04-20', '2026-03-15 21:25:46', NULL, NULL, NULL),
(5, 5, 4, 1, '2024-00130', 'Administrativo', 'Nulidad de acto administrativo', 'Activo', '2026-01-05', '2026-05-15', '2026-03-15 21:25:46', NULL, NULL, NULL),
(6, 6, 1, 2, '2024-00131', 'Civil', 'Excepciones previas en proceso ejecutivo', 'En espera', '2026-01-10', '2026-06-01', '2026-03-15 21:25:46', NULL, NULL, NULL),
(7, 7, 2, 3, '2024-00132', 'Penal', 'Término para presentar pruebas', 'Vencido', '2026-01-01', '2026-02-01', '2026-03-15 21:25:46', NULL, NULL, NULL),
(8, 8, 3, 4, '2024-00133', 'Laboral', 'Calificación de origen de enfermedad', 'Finalizado', '2025-11-15', '2026-01-15', '2026-03-15 21:25:46', NULL, NULL, NULL),
(9, 9, 1, 1, '2024-00134', 'Civil', 'Recurso de apelación', 'Activo', '2026-02-18', '2026-04-30', '2026-03-15 21:25:46', NULL, NULL, NULL),
(10, 10, 4, 2, '2024-00135', 'Familia', 'Liquidación de sociedad conyugal', 'En espera', '2026-02-12', '2026-05-10', '2026-03-15 21:25:46', NULL, NULL, NULL),
(11, 10, 1, 2, '000874522145', NULL, 'Prueba de insercion', 'Activo', '2026-03-18', '2026-05-18', '2026-03-19 01:31:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tipos_proceso`
--

CREATE TABLE `tipos_proceso` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipos_proceso`
--

INSERT INTO `tipos_proceso` (`id`, `nombre`, `descripcion`, `activo`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 'Civil', 'Procesos civiles y comerciales', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(2, 'Penal', 'Procesos penales', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(3, 'Laboral', 'Procesos laborales y de seguridad social', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(4, 'Administrativo', 'Procesos contencioso administrativos', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(5, 'Familia', 'Procesos de familia', 1, '2026-03-15 21:25:46', NULL, NULL, NULL),
(6, 'Contencioso', 'Procesos contencioso administrativos', 1, '2026-03-17 21:51:02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `usuario`, `password`, `activo`, `created_at`) VALUES
(1, 'Administrador', 'admin@local.com', 'admin', '0192023a7bbd73250516f069df18b500', 1, '2026-03-18 02:33:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actuaciones`
--
ALTER TABLE `actuaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proceso_id` (`proceso_id`);

--
-- Indexes for table `anexos`
--
ALTER TABLE `anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proceso_id` (`proceso_id`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `estados_proceso`
--
ALTER TABLE `estados_proceso`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `procesos`
--
ALTER TABLE `procesos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `tipo_proceso_id` (`tipo_proceso_id`),
  ADD KEY `estado_proceso_id` (`estado_proceso_id`);

--
-- Indexes for table `tipos_proceso`
--
ALTER TABLE `tipos_proceso`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actuaciones`
--
ALTER TABLE `actuaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `anexos`
--
ALTER TABLE `anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `estados_proceso`
--
ALTER TABLE `estados_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `procesos`
--
ALTER TABLE `procesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tipos_proceso`
--
ALTER TABLE `tipos_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actuaciones`
--
ALTER TABLE `actuaciones`
  ADD CONSTRAINT `actuaciones_ibfk_1` FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anexos`
--
ALTER TABLE `anexos`
  ADD CONSTRAINT `anexos_ibfk_1` FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `procesos`
--
ALTER TABLE `procesos`
  ADD CONSTRAINT `procesos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `procesos_ibfk_2` FOREIGN KEY (`tipo_proceso_id`) REFERENCES `tipos_proceso` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `procesos_ibfk_3` FOREIGN KEY (`estado_proceso_id`) REFERENCES `estados_proceso` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
