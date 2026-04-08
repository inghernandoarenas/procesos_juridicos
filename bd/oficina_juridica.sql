-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 01:28 PM
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
  `despacho` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `actuacion` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anexos`
--

CREATE TABLE `anexos` (
  `id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
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

INSERT INTO `anexos` (`id`, `proceso_id`, `categoria_id`, `nombre_archivo`, `ruta_archivo`, `tipo_archivo`, `fecha_subida`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(2, 11, NULL, 'Cuenta_hugo_ruiz__2025.pdf', 'uploads/69cd8b76137fe.pdf', 'application/pdf', '2026-04-01 21:17:42', NULL, NULL, NULL),
(3, 11, 1, 'Cuenta_hugo_ruiz__2025.pdf', 'uploads/69cd8b88e53ff.pdf', 'application/pdf', '2026-04-01 21:18:00', NULL, NULL, NULL),
(4, 3, 5, 'cedula hugo.pdf', 'uploads/69cff1143a2f8.pdf', 'application/pdf', '2026-04-03 16:55:48', NULL, NULL, NULL),
(5, 3, 1, 'Hoja de vida Hugo Ruiz.pdf', 'uploads/69cff11c293bd.pdf', 'application/pdf', '2026-04-03 16:55:56', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `anexo_categorias`
--

CREATE TABLE `anexo_categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anexo_categorias`
--

INSERT INTO `anexo_categorias` (`id`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Facturas', 1, '2026-04-01 20:51:05'),
(2, 'Documentos de pago', 1, '2026-04-01 20:51:05'),
(3, 'Evidencias', 1, '2026-04-01 20:51:05'),
(4, 'Respuestas del juez', 1, '2026-04-01 20:51:05'),
(5, 'Alegatos', 1, '2026-04-01 20:51:05'),
(6, 'Pólizas', 1, '2026-04-01 20:51:05'),
(7, 'Expediente', 1, '2026-04-01 20:51:05'),
(8, 'Otros', 1, '2026-04-01 20:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `tipo_identificacion` varchar(10) DEFAULT NULL,
  `numero_identificacion` varchar(30) DEFAULT NULL,
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

INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `tipo_identificacion`, `numero_identificacion`, `email`, `telefono`, `direccion`, `created_at`, `usuario_creacion`, `usuario_modificacion`, `fecha_modificacion`) VALUES
(1, 'Juan', 'Pérez', NULL, NULL, 'juan.perez@email.com', '3001112233', 'Calle 1 #2-3, Bogotá', '2026-03-30 02:07:11', NULL, NULL, NULL),
(2, 'María', 'González', NULL, NULL, 'maria.gonzalez@email.com', '3102223344', 'Carrera 4 #5-6, Medellín', '2026-03-30 02:07:11', NULL, NULL, NULL),
(3, 'Carlos', 'Rodríguez', NULL, NULL, 'carlos.rodriguez@email.com', '3203334455', 'Calle 45 #20-30, Bogotá', '2026-03-30 02:07:11', NULL, NULL, NULL),
(4, 'Ana', 'Martínez', NULL, NULL, 'ana.martinez@email.com', '3014445566', 'Carrera 32 #15-25, Medellín', '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Pedro', 'Sánchez', NULL, NULL, 'pedro.sanchez@email.com', '3025556677', 'Avenida 6 #5-10, Cali', '2026-03-30 02:07:11', NULL, NULL, NULL),
(6, 'Luisa', 'Fernández', NULL, NULL, 'luisa.fernandez@email.com', '3036667788', 'Diagonal 23 #8-40, Barranquilla', '2026-03-30 02:07:11', NULL, NULL, NULL),
(7, 'Diego', 'Ramírez', NULL, NULL, 'diego.ramirez@email.com', '3047778899', 'Calle 8 #10-20, Cartagena', '2026-03-30 02:07:11', NULL, NULL, NULL),
(8, 'Laura', 'Torres', NULL, NULL, 'laura.torres@email.com', '3058889900', 'Carrera 12 #15-30, Santa Marta', '2026-03-30 02:07:11', NULL, NULL, NULL),
(9, 'Andrés', 'Muñoz', NULL, NULL, 'andres.munoz@email.com', '3069990011', 'Avenida 3 #4-50, Bucaramanga', '2026-03-30 02:07:11', NULL, NULL, NULL),
(10, 'Carolina', 'Rojas', NULL, NULL, 'carolina.rojas@email.com', '3070001122', 'Transversal 5 #8-70, Pereira', '2026-03-30 02:07:11', NULL, NULL, NULL),
(11, 'Cliente de prueba_ update', 'Prueba', NULL, NULL, 'nnn@gmail.com', '3255877445', 'Este cliente es para probar update', '2026-03-30 22:45:15', NULL, NULL, NULL),
(12, 'Kety ', 'Ruiz', NULL, NULL, 'kettyruiz@gmail.com', '', '', '2026-04-03 17:25:32', NULL, NULL, NULL),
(13, 'Elizabeth', 'Mercado', NULL, NULL, '', '', '', '2026-04-03 17:52:32', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`, `updated_at`) VALUES
(1, 'nombre_empresa', 'TICO & Asociados', 'Nombre del despacho o firma', '2026-04-01 18:43:12'),
(2, 'subtitulo', 'Sistema de Gestión de Procesos Judiciales', 'Subtítulo o eslogan', '2026-04-01 11:14:52'),
(3, 'nit', '000.000.000.000-0', 'NIT o documento de identificación', '2026-04-02 01:04:00'),
(4, 'telefono', '', 'Teléfono de contacto', '2026-04-01 11:14:52'),
(5, 'email', '', 'Email de contacto', '2026-04-01 11:14:52'),
(6, 'direccion', '', 'Dirección física', '2026-04-01 11:14:52'),
(7, 'ciudad', '', 'Ciudad', '2026-04-01 11:14:52'),
(8, 'website', '', 'Sitio web', '2026-04-01 11:14:52'),
(9, 'pie_reporte', 'Documento generado automáticamente por el Sistema de Gestión de Procesos Judiciales', 'Texto del pie de página en reportes', '2026-04-01 11:14:52'),
(10, 'anio_copyright', '2026', 'Año para el copyright en el footer', '2026-04-02 01:04:00');

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
(4, 'Terminado', '#2ecc71', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Limbo juridico', '#021522', 1, '2026-03-30 23:30:40', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `honorarios`
--

CREATE TABLE `honorarios` (
  `id` int(11) NOT NULL,
  `proceso_id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `tipo` enum('cuota_periodica','honorario_exito','pago_puntual','anticipo','gasto_reembolsable') NOT NULL DEFAULT 'pago_puntual',
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_causacion` date NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','vencido') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `honorarios`
--

INSERT INTO `honorarios` (`id`, `proceso_id`, `concepto`, `tipo`, `valor`, `fecha_causacion`, `fecha_pago`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cuota inicial', 'anticipo', 1500000.00, '2026-01-05', '2026-01-06', 'pagado', 'Anticipo inicial', '2026-04-02 13:07:44', NULL),
(2, 1, 'Cuota mensual enero', 'cuota_periodica', 500000.00, '2026-01-30', '2026-02-02', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(3, 1, 'Gastos notariales', 'gasto_reembolsable', 120000.00, '2026-02-10', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(4, 2, 'Anticipo servicios', 'anticipo', 800000.00, '2026-01-03', '2026-01-04', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(5, 2, 'Cuota mensual enero', 'cuota_periodica', 400000.00, '2026-01-31', '2026-02-05', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(6, 2, 'Cuota mensual febrero', 'cuota_periodica', 400000.00, '0000-00-00', NULL, 'vencido', NULL, '2026-04-02 13:07:44', NULL),
(7, 2, 'Gastos transporte', 'gasto_reembolsable', 90000.00, '2026-03-02', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(8, 3, 'Pago puntual revisión', 'pago_puntual', 350000.00, '2026-01-15', '2026-01-16', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(9, 3, 'Honorario de éxito', 'honorario_exito', 2200000.00, '2026-02-20', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(10, 4, 'Anticipo inicial', 'anticipo', 1000000.00, '2026-01-08', '2026-01-08', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(11, 4, 'Cuota mensual enero', 'cuota_periodica', 300000.00, '2026-01-31', '2026-02-01', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(12, 4, 'Cuota mensual febrero', 'cuota_periodica', 300000.00, '2026-02-28', NULL, 'vencido', NULL, '2026-04-02 13:07:44', NULL),
(13, 5, 'Pago puntual audiencia', 'pago_puntual', 450000.00, '2026-01-20', '2026-01-20', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(14, 5, 'Gasto peritaje', 'gasto_reembolsable', 200000.00, '2026-02-05', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(15, 5, 'Honorario de éxito', 'honorario_exito', 1800000.00, '2026-03-01', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(16, 6, 'Anticipo', 'anticipo', 600000.00, '2026-01-10', '2026-01-11', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(17, 6, 'Cuota mensual enero', 'cuota_periodica', 250000.00, '2026-01-31', '2026-02-01', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(18, 6, 'Cuota mensual febrero', 'cuota_periodica', 250000.00, '2026-02-28', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(19, 6, 'Gastos copias', 'gasto_reembolsable', 60000.00, '2026-02-15', '2026-02-16', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(20, 7, 'Pago puntual consulta', 'pago_puntual', 200000.00, '2026-01-12', '2026-01-12', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(21, 7, 'Gastos radicación', 'gasto_reembolsable', 75000.00, '2026-01-18', '2026-01-19', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(22, 7, 'Honorario de éxito', 'honorario_exito', 950000.00, '2026-03-10', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(23, 8, 'Anticipo', 'anticipo', 700000.00, '2026-01-02', '2026-01-03', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(24, 8, 'Cuota mensual enero', 'cuota_periodica', 280000.00, '2026-01-31', '2026-02-03', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(25, 8, 'Cuota mensual febrero', 'cuota_periodica', 280000.00, '2026-02-28', NULL, 'vencido', NULL, '2026-04-02 13:07:44', NULL),
(26, 9, 'Pago puntual revisión contrato', 'pago_puntual', 320000.00, '2026-01-22', '2026-01-23', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(27, 9, 'Gasto autenticaciones', 'gasto_reembolsable', 50000.00, '2026-02-01', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(28, 10, 'Anticipo inicial', 'anticipo', 900000.00, '2026-01-04', '2026-01-05', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(29, 10, 'Cuota mensual enero', 'cuota_periodica', 350000.00, '2026-01-31', '2026-02-02', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(30, 10, 'Cuota mensual febrero', 'cuota_periodica', 350000.00, '2026-02-28', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(31, 10, 'Gastos mensajería', 'gasto_reembolsable', 45000.00, '2026-02-10', '2026-02-10', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(32, 11, 'Pago puntual demanda', 'pago_puntual', 500000.00, '2026-01-14', '2026-01-15', 'pagado', NULL, '2026-04-02 13:07:44', NULL),
(33, 11, 'Gastos judiciales', 'gasto_reembolsable', 130000.00, '2026-02-12', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54'),
(34, 11, 'Honorario de éxito', 'honorario_exito', 2100000.00, '2026-03-20', NULL, 'vencido', NULL, '2026-04-02 13:07:44', '2026-04-02 13:07:54');

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
(11, 7, 3, 3, '089954478220025454', NULL, 'Demanda alcaldía Candelaria', 'Activo', '2026-03-30', '2026-06-25', 0, '2026-03-30 19:35:11', NULL, NULL, NULL),
(12, 12, 1, 1, '08001418900220260035500', NULL, 'Proceso ejecutivo, acta de conciliación en contra del malparido ese', 'Activo', '2026-03-09', '2026-05-09', 0, '2026-04-03 17:34:09', NULL, NULL, NULL),
(13, 12, 1, 1, '08001418900520250169500', NULL, '', 'Activo', '2025-11-01', '2026-02-06', 0, '2026-04-03 17:36:12', NULL, NULL, NULL),
(14, 13, 1, 1, '08001405301420220040400', NULL, '', 'Activo', '2026-02-01', '2026-09-24', 0, '2026-04-03 17:53:54', NULL, NULL, NULL);

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
(4, 'Contencioso administrativo', 'Procesos contencioso administrativos', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(5, 'Familia', 'Procesos de familia', 1, '2026-03-30 02:07:11', NULL, NULL, NULL),
(6, 'Contencioso administrativo', 'Procesos contencioso administrativos', 0, '2026-03-30 02:07:11', NULL, NULL, NULL),
(7, 'Tipo de prueba', 'Este es un registro de prueba_ update', 1, '2026-03-30 23:29:39', NULL, NULL, NULL),
(8, 'Tutelas', '', 1, '2026-04-03 17:26:53', NULL, NULL, NULL);

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
  ADD KEY `proceso_id` (`proceso_id`),
  ADD KEY `fk_anexo_categoria` (`categoria_id`);

--
-- Indexes for table `anexo_categorias`
--
ALTER TABLE `anexo_categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indexes for table `estados_proceso`
--
ALTER TABLE `estados_proceso`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `honorarios`
--
ALTER TABLE `honorarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proceso_id` (`proceso_id`),
  ADD KEY `estado` (`estado`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `anexos`
--
ALTER TABLE `anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `anexo_categorias`
--
ALTER TABLE `anexo_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `estados_proceso`
--
ALTER TABLE `estados_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `honorarios`
--
ALTER TABLE `honorarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notificaciones_config`
--
ALTER TABLE `notificaciones_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notificaciones_log`
--
ALTER TABLE `notificaciones_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `procesos`
--
ALTER TABLE `procesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tipos_proceso`
--
ALTER TABLE `tipos_proceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  ADD CONSTRAINT `anexos_ibfk_1` FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_anexo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `anexo_categorias` (`id`);

--
-- Constraints for table `honorarios`
--
ALTER TABLE `honorarios`
  ADD CONSTRAINT `fk_honorario_proceso` FOREIGN KEY (`proceso_id`) REFERENCES `procesos` (`id`) ON DELETE CASCADE;

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
