<?php
include "conecction.php";
session_start();

if(isset($_FILES['audio'])){
    $uploadsDir = '../uploads/';
    if(!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $nombreArchivo = time().'_'.basename($_FILES['audio']['name']);
    $rutaCompleta = $uploadsDir.$nombreArchivo;

    if(move_uploaded_file($_FILES['audio']['tmp_name'], $rutaCompleta)){
        $tipo = $_FILES['audio']['type'];
        $rutaDB = 'uploads/'.$nombreArchivo;
        $user = $_SESSION['id'];

        $stmt = $conn->prepare("INSERT INTO test (user, ruta, tipo) VALUES (?, ?, ?)");
        if(!$stmt) die("Error prepare: ".$conn->error);
        $stmt->bind_param("sss", $user, $rutaDB, $tipo);
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
