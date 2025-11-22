-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-11-2025 a las 02:56:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `s&w`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `data`
--

CREATE TABLE `data` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `studentId` varchar(9) NOT NULL,
  `Birthday` date NOT NULL,
  `country` varchar(25) NOT NULL,
  `language` varchar(25) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `data`
--

INSERT INTO `data` (`id`, `name`, `studentId`, `Birthday`, `country`, `language`, `fecha`) VALUES
(40, 'Adriab Enmanuel Torres Ruano', '202502001', '2008-04-13', 'El Salvador', 'Spanish', '2025-11-16 19:11:34'),
(41, 'Adrian Enmanuel Torres Ruano', '202502001', '2008-04-13', 'El Salvador', 'Spanish', '2025-11-20 02:25:04'),
(42, 'Adrian Enmanuel Torres Ruano', '202502002', '2025-11-19', 'El Salvador', 'Spanish', '2025-11-20 02:37:14'),
(43, 'Adrián Torres', '202502003', '2009-06-28', 'El Salvador', 'Spanish', '2025-11-22 00:08:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `speaking`
--

CREATE TABLE `speaking` (
  `id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `practice` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `test`
--

CREATE TABLE `test` (
  `id` int(11) NOT NULL,
  `user` varchar(10) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ruta` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `test` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT '''audio/webm'''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `speaking`
--
ALTER TABLE `speaking`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `data`
--
ALTER TABLE `data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `speaking`
--
ALTER TABLE `speaking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `test`
--
ALTER TABLE `test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
