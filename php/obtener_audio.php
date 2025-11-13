<?php
include "conecction.php";

$sql = "SELECT ruta, tipo FROM test ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if($result && $row = $result->fetch_assoc()){
    $ruta = '../'.$row['ruta']; // ruta real en el servidor
    $tipo = $row['tipo'] ?: 'audio/webm';
    header('Content-Type: '.$tipo);
    readfile($ruta);
}else{
    echo "No se encontró audio";
}

$conn->close();