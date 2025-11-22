<?php
include "conecction.php";
session_start();
$user = $_SESSION['id'];

if(isset($_FILES['audio'])){
    $uploadsDir = '../uploads/';
    if(!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $nombreArchivo = $user.'_'.basename($_FILES['audio']['name']);
    $rutaCompleta = $uploadsDir.$nombreArchivo;

    if(move_uploaded_file($_FILES['audio']['tmp_name'], $rutaCompleta)){
        $tipo = $_FILES['audio']['type'];
        $rutaDB = 'uploads/'.$nombreArchivo;
        

        $stmt = $conn->prepare("INSERT INTO test (user, ruta, nombre, tipo) VALUES (?, ?, ?, ?)");
        if(!$stmt) die("Error prepare: ".$conn->error);
        $stmt->bind_param("ssss", $user, $rutaDB, $nombreArchivo, $tipo);
        $stmt->execute();
        $stmt->close();

        echo "Audio guardado correctamente". $user;
    } else {
        echo "Error al mover el archivo";
    }
} else {
    echo "No se recibió audio";
}

$conn->close();
