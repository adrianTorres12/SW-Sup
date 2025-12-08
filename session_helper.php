<?php
// session_helper.php - VERSIÓN ACTUALIZADA
// Archivo para manejar sesiones de forma consistente en todo el proyecto

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'use_only_cookies' => 1,
            'cookie_lifetime' => 0,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax'
        ]);
    }
    
    // Regenerar ID de sesión periódicamente para mayor seguridad
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function isUserLoggedIn() {
    if (!isset($_SESSION['logged_in_user'])) {
        return false;
    }
    
    // Verificar que la sesión tenga datos válidos
    $required_fields = ['id', 'fullname', 'lastnames', 'email', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($_SESSION['logged_in_user'][$field])) {
            return false;
        }
    }
    
    // Verificar roles válidos
    $valid_roles = ['student', 'teacher'];
    if (!in_array($_SESSION['logged_in_user']['role'], $valid_roles)) {
        return false;
    }
    
    // Verificar expiración de sesión (2 horas)
    $session_lifetime = 7200;
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > $session_lifetime)) {
        logoutUser();
        return false;
    }
    
    // Actualizar timestamp de actividad
    $_SESSION['last_activity'] = time();
    
    return true;
}

function logoutUser() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // También limpiar cookies de recuerdame
    setcookie('remember_user', '', time() - 3600, "/");
    setcookie('session_user', '', time() - 3600, "/");
}

function getUserRole() {
    if (isUserLoggedIn()) {
        return $_SESSION['logged_in_user']['role'];
    }
    return null;
}

function getUserClass() {
    if (isUserLoggedIn() && isset($_SESSION['logged_in_user']['class_of'])) {
        return $_SESSION['logged_in_user']['class_of'];
    }
    return null;
}

function getUserId() {
    if (isUserLoggedIn()) {
        return $_SESSION['logged_in_user']['id'] ?? null;
    }
    return null;
}

function getUserFullName() {
    if (isUserLoggedIn()) {
        $fullname = $_SESSION['logged_in_user']['fullname'] ?? '';
        $lastnames = $_SESSION['logged_in_user']['lastnames'] ?? '';
        return trim($fullname . ' ' . $lastnames);
    }
    return '';
}

function getUserEmail() {
    if (isUserLoggedIn()) {
        return $_SESSION['logged_in_user']['email'] ?? '';
    }
    return '';
}

// Nueva función para obtener toda la información del usuario
function getCurrentUserInfo() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    return [
        'id' => getUserId(),
        'fullname' => getUserFullName(),
        'role' => getUserRole(),
        'class_of' => getUserClass(),
        'email' => getUserEmail()
    ];
}
?>