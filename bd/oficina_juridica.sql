-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 05:05 AM
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
(1, 3, '3393118941', '2025-06-12', 'AUTO ADMITE DEMANDA', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL),
(2, 3, '3392691871', '2025-05-23', 'AUTO INADMITE LA DEMANDA', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL),
(3, 3, '3392470971', '2025-05-14', 'AUTO ADMITE DEMANDA', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL),
(4, 3, '3391188071', '2025-03-05', 'AUTO ADMITE DEMANDA', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL),
(5, 3, '3382650431', '2025-02-05', 'RADICACIÓN', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL),
(6, 3, '3395518081', '2025-08-25', 'CONSTANCIA RECEPCIÓN DE REGISTRO DE NOTIFICACIÓN PERSONAL', ' ', '2026-03-30 19:19:40', NULL, NULL, NULL);

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
(1, 11, 'cedula hugo.pdf', 'uploads/69cad226153a2.pdf', 'application/pdf', '2026-03-30 19:42:30', NULL, NULL, NULL);

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
(1, 'Juan', 'Pérez', 'juan.perez@email.com', '3001112233', 'Calle 1 #2-3, Bogotá', '2026-03-30 02:07:11', NULL, NULL, NULL),
(2, 'María', 'González', 'maria.gonzalez@email.com', '3102223344', 'Carrera 4 #5-6, Medellín', '2026-03-30 02:07:11', NULL, NULL, NULL),
(3, 'Carlos', 'Rodríguez', 'carlos.rodriguez@email.com', '3203334455', 'Calle 45 #20-30, Bogotá', '2026-03-30 02:07:11', NULL, NULL, NULL),
(4, 'Ana', 'Martínez', 'ana.martinez@email.com', '3014445566', 'Carrera 32 #15-25, Medellín', '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Pedro', 'Sánchez', 'pedro.sanchez@email.com', '3025556677', 'Avenida 6 #5-10, Cali', '2026-03-30 02:07:11', NULL, NULL, NULL),
(6, 'Luisa', 'Fernández', 'luisa.fernandez@email.com', '3036667788', 'Diagonal 23 #8-40, Barranquilla', '2026-03-30 02:07:11', NULL, NULL, NULL),
(7, 'Diego', 'Ramírez', 'diego.ramirez@email.com', '3047778899', 'Calle 8 #10-20, Cartagena', '2026-03-30 02:07:11', NULL, NULL, NULL),
(8, 'Laura', 'Torres', 'laura.torres@email.com', '3058889900', 'Carrera 12 #15-30, Santa Marta', '2026-03-30 02:07:11', NULL, NULL, NULL),
(9, 'Andrés', 'Muñoz', 'andres.munoz@email.com', '3069990011', 'Avenida 3 #4-50, Bucaramanga', '2026-03-30 02:07:11', NULL, NULL, NULL),
(10, 'Carolina', 'Rojas', 'carolina.rojas@email.com', '3070001122', 'Transversal 5 #8-70, Pereira', '2026-03-30 02:07:11', NULL, NULL, NULL),
(11, 'Cliente de prueba_ update', 'Prueba', 'nnn@gmail.com', '3255877445', 'Este cliente es para probar update', '2026-03-30 22:45:15', NULL, NULL, NULL);

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
(1, 'Activo', '#e8a945', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(2, 'En espera', '#3498db', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(3, 'Vencido', '#e74c3c', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(4, 'Finalizado', '#2ecc71', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Limbo juridico', '#021522', 1, '2026-03-30 23:30:40', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notificaciones_config`
--

CREATE TABLE `notificaciones_config` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('email','whatsapp','ambos') DEFAULT 'email',
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notificaciones_config`
--

INSERT INTO `notificaciones_config` (`id`, `usuario_id`, `tipo`, `email`, `telefono`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'email', 'hernando.17@hotmail.com', '3244920873', 1, '2026-03-30 02:33:32', NULL),
(2, 2, 'ambos', 'ing.hernando.arenas@gmail.com', '3332913337', 1, '2026-03-30 23:32:59', '2026-03-30 23:33:09');

-- --------------------------------------------------------

--
-- Table structure for table `notificaciones_log`
--

CREATE TABLE `notificaciones_log` (
  `id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `actuacion_id` int(11) NOT NULL,
  `tipo_envio` enum('email','whatsapp') NOT NULL,
  `destinatario` varchar(100) NOT NULL,
  `estado` enum('pendiente','enviado','fallido') DEFAULT 'pendiente',
  `mensaje` text DEFAULT NULL,
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `sincronizar_api` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procesos`
--

INSERT INTO `procesos` (`id`, `cliente_id`, `tipo_proceso_id`, `estado_proceso_id`, `numero_radicado`, `tipo_proceso`, `descripcion`, `estado`, `fecha_inicio`, `fecha_vencimiento`, `sincronizar_api`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 1, 1, 1, '2024-00126', NULL, 'Demanda ejecutiva hipotecaria', 'Activo', '2026-03-01', '2026-04-05', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(2, 2, 3, 1, '2024-00127', NULL, 'Proceso de fuero sindical', 'Activo', '2026-02-15', '2026-03-25', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(3, 3, 2, 1, '08638310500120250000600', NULL, 'Proceso penal - prueba con API', 'Activo', '2025-02-05', '2026-04-15', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(4, 4, 5, 2, '2024-00129', NULL, 'Divorcio contencioso', 'En espera', '2026-01-10', '2026-03-20', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 5, 4, 1, '2024-00130', NULL, 'Nulidad de acto administrativo', 'Activo', '2026-02-20', '2026-04-20', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(6, 6, 1, 3, '2024-00131', NULL, 'Excepciones previas', 'Vencido', '2026-01-05', '2026-02-05', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(7, 7, 2, 3, '2024-00132', NULL, 'Término para presentar pruebas', 'Vencido', '2025-12-01', '2025-12-30', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(8, 8, 3, 4, '2024-00133', NULL, 'Calificación de origen de enfermedad', 'Finalizado', '2025-11-01', '2025-12-01', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(9, 9, 1, 1, '2024-00134', NULL, 'Recurso de apelación', 'Activo', '2026-03-01', '2026-03-28', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(10, 10, 4, 2, '2024-00135', NULL, 'Liquidación de sociedad conyugal_edit', 'En espera', '2026-02-01', '2026-03-18', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(11, 7, 3, 3, '089954478220025454', NULL, 'Demanda alcaldía Candelaria', 'Activo', '2026-03-30', '2026-06-25', 0, '2026-03-30 19:35:11', NULL, NULL, NULL);

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
(1, 'Civil', 'Procesos civiles y comerciales.', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(2, 'Penal', 'Procesos penales', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(3, 'Laboral', 'Procesos laborales y de seguridad social', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(4, 'Administrativo', 'Procesos contencioso administrativos', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Familia', 'Procesos de familia', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(6, 'Contencioso', 'Procesos contencioso administrativos', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(7, 'Tipo de prueba', 'Este es un registro de prueba_ update', 1, '2026-03-30 23:29:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `telefono`, `usuario`, `password`, `cargo`, `activo`, `created_at`) VALUES
(1, 'Administrador', 'hernando.17@hotmail.com', '3244920873', 'admin', '0192023a7bbd73250516f069df18b500', 'Administrador', 1, '2026-03-30 02:07:11'),
(2, 'Hernando Arenas Lambis', 'ing.hernando.arenas@gmail.com', '3332913337', 'nando', '9a286406c252a3d14218228974e1f567', NULL, 1, '2026-03-30 23:32:02');

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
-- Indexes for table `notificaciones_config`
--
ALTER TABLE `notificaciones_config`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indexes for table `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proceso_id` (`proceso_id`),
  ADD KEY `actuacion_id` (`actuacion_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `anexos`
--
ALTER TABLE `anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `estados_proceso`
--
ALTER TABLE `estados_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notificaciones_config`
--
ALTER TABLE `notificaciones_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procesos`
--
ALTER TABLE `procesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tipos_proceso`
--
ALTER TABLE `tipos_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `notificaciones_config`
--
ALTER TABLE `notificaciones_config`
  ADD CONSTRAINT `notificaciones_config_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  ADD CONSTRAINT `notificaciones_log_ibfk_1` FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificaciones_log_ibfk_2` FOREIGN KEY (`actuacion_id`) REFERENCES `actuaciones` (`id`) ON DELETE CASCADE;

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
