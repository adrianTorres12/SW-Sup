<?php
include "conecction.php";
$name = $_POST['first'];
$last = $_POST['last'];
$id = $_POST['id'];
$birth = $_POST['birth'];
$country = $_POST['country'];
$language = $_POST['language'];

$stmt = $conn->prepare("INSERT INTO data (`name`, `studentId`, `Birthday`, `country`, `language`) VALUES (?, ?, ?, ?, ?)");
        if(!$stmt) die("Error prepare: ".$conn->error);

        $stmt->bind_param("ssdss", $name, $id, $birth, $country, $language);
        $stmt->execute();
        $stmt->close();
        $conn->close();