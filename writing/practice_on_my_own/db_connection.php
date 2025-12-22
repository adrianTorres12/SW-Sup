<?php
// File: writing/practice_on_my_own/db_connection.php

$host = "localhost";
$username = "root";
$password = "";
$database = "practice_writing";

try {
    $practice_conn = new mysqli($host, $username, $password, $database);
    
    if ($practice_conn->connect_error) {
        throw new Exception("Connection failed: " . $practice_conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support
    $practice_conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Función para generar código de acceso único
function generateAccessCode($prefix = 'WR', $length = 8) {
    global $practice_conn;
    
    do {
        // Generar código: prefijo + caracteres aleatorios (letras mayúsculas y números)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Eliminamos caracteres ambiguos
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        $code = $prefix . $random;
        
        // Verificar si el código ya existe
        $stmt = $practice_conn->prepare("SELECT id FROM writing_tests WHERE access_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
    } while ($exists);
    
    return $code;
}

// Función para generar código para Full Test
function generateFullTestCode($test_number, $prefix = 'FT') {
    global $practice_conn;
    
    do {
        // Código para full test: FT + test_number + caracteres aleatorios
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $random = '';
        for ($i = 0; $i < 4; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        $code = $prefix . str_pad($test_number, 3, '0', STR_PAD_LEFT) . $random;
        
        // Verificar si el código ya existe
        $stmt = $practice_conn->prepare("SELECT id FROM writing_tests WHERE access_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
    } while ($exists);
    
    return $code;
}

// Función para verificar si usuario es teacher (usando session_helper)
function isUserTeacher() {
    require_once '../../session_helper.php';
    startSecureSession();
    return (getUserRole() === 'teacher');
}
?>