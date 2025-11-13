<?php
$conn = new mysqli("localhost", "root", "", "s&w");
if($conn->connect_error){
    die("Conexión fallida: " . $conn->connect_error);
}