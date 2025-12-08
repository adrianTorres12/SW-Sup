-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-12-2025 a las 07:54:23
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
-- Base de datos: `superate_platform`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `allowed_teachers`
--

CREATE TABLE `allowed_teachers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `allowed_teachers`
--

INSERT INTO `allowed_teachers` (`id`, `email`, `created_at`) VALUES
(1, 'adrian.torres@adoc.superate.org.sv', '2025-12-03 06:23:25'),
(2, 'justin.valladares@adoc.superate.org.sv', '2025-12-03 06:23:53'),
(3, 'benhur.zepeda@adoc.superate.org.sv', '2025-12-03 06:24:12'),
(4, 'blanca.melendez@adoc.superate.org.sv', '2025-12-03 06:24:28'),
(5, 'victor.arias@adoc.superate.org.sv', '2025-12-03 06:24:55'),
(6, 'blanca.portillo@adoc.superate.org.sv', '2025-12-03 06:25:15'),
(7, 'keiry.rodriguez@adoc.superate.org.sv', '2025-12-03 06:25:36'),
(8, 'carlos.ponce@adoc.superate.org.sv', '2025-12-03 06:25:52'),
(9, 'emerson.orellana@adoc.superate.org.sv', '2025-12-03 06:26:20'),
(10, 'kevin.hernandez@adoc.superate.org.sv', '2025-12-03 06:26:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `class_codes`
--

CREATE TABLE `class_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `class_codes`
--

INSERT INTO `class_codes` (`id`, `code`, `name`, `created_by`, `created_at`) VALUES
(1, 'morning2025', 'Morning Shift 2025', 0, '2025-12-03 05:02:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `lastnames` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `class_of` enum('first','second','third','teacher') DEFAULT NULL,
  `class_code` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('student','teacher') DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `fullname`, `lastnames`, `email`, `class_of`, `class_code`, `password_hash`, `remember_token`, `created_at`, `role`) VALUES
(1, 'Justin Josué', 'Valladares López', 'justin.valladares2025@adoc.superate.org.sv', 'third', 'CLASS123', '$2y$10$6SgxT9IeKiYCmSRbbe0cNuhV5uzAZhITFxNYZYM/Ao/cyW9qG3Uye', '915c02e9f6150f133fa5555f2ceb23a640c8238f2c46fc6486fe88ae2494dd7d', '2025-11-25 00:15:16', 'student'),
(3, 'Justin Josué', 'Valladares López', 'justin.valladares@adoc.superate.org.sv', 'teacher', NULL, '$2y$10$edzIqGEC5PsAtxmM5ZjxjOS06WZIkOyOxwvGYIaFreg6VsG.WEPhO', '9cc301a6d69dd62785a5d6fbbb40f8b023460355997ea9103e65a05c3b29475d', '2025-11-30 23:25:29', 'teacher'),
(4, 'Sample Student', 'Demo User', 'adrian.torres@adoc.superate.org.sv', 'teacher', NULL, '$2y$10$SIRckaVuS5LvziWk.IklfuG3NH16l9brvXH429g3muly6Szy23rmS', NULL, '2025-12-03 14:15:43', 'teacher'),
(5, 'Karla Alejandra', 'Chicas Aguilar', 'karla.chicas2027@adoc.superate.org.sv', 'first', NULL, '$2y$10$5cLp7XpH2cNeKivzkBlTk.D0e6ckQRtnGDncC9fNBKF95IK8SSsd2', 'c70c6a846211eaeeb414f307eec52b50156d1f1a52318ba1543b361d75b27a54', '2025-12-04 22:25:22', 'student');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `allowed_teachers`
--
ALTER TABLE `allowed_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `class_codes`
--
ALTER TABLE `class_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `allowed_teachers`
--
ALTER TABLE `allowed_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `class_codes`
--
ALTER TABLE `class_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
