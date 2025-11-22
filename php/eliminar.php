<?php
include "conecction.php"; // tu conexión a la base de datos
session_start();

// Verificar que haya un ID de usuario en sesión
if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "msg" => "No hay ID en sesión"]);
    exit;
}

$id_usuario = $_SESSION['id'];

// 1. Obtener la ruta del archivo desde la BD
$sql = $conn->prepare("SELECT ruta FROM test WHERE user = ?");
$sql->bind_param("s", $id_usuario);
$sql->execute();
$sql->bind_result($ruta);

if (!$sql->fetch()) {
    echo json_encode(["status" => "error", "msg" => "Archivo no encontrado"]);
    exit;
}
$sql->close();

// 2. Construir ruta absoluta correcta
// Asumiendo que uploads está en SW-Sup/uploads/
$path = __DIR__ . '/../uploads/' . basename($ruta); // basename evita rutas maliciosas

// 3. Borrar archivo del disco
if (file_exists($path)) {
    if (unlink($path)) {
        $file_msg = "Archivo borrado: $path";
    } else {
        $file_msg = "No se pudo borrar el archivo: $path";
    }
} else {
    $file_msg = "Archivo no encontrado en el disco: $path";
}

// 4. Borrar registro de la base de datos
$stmt = $conn->prepare("DELETE FROM test WHERE user = ?");
$stmt->bind_param("s", $id_usuario);
if ($stmt->execute()) {
    $db_msg = "Registro eliminado de la base de datos";
} else {
    $db_msg = "No se pudo eliminar el registro de la base de datos";
}
$stmt->close();

// 5. Retornar resultado en JSON para la consola
echo json_encode([
    "status" => "success",
    "file_msg" => $file_msg,
    "db_msg" => $db_msg
]);