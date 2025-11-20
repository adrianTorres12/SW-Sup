<?php
include "conecction.php";
session_start();

$data = $_POST;

if (empty($data)) {
    $data = json_decode(file_get_contents("php://input"), true);
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($data['ajax']));

if ($isAjax) {

    $first = $data['first'];
    $last = $data['last'];
    $name = $first . " " . $last;
    $id = $data['id'];
    $birth = $data['birth'];
    $country = $data['country'];
    $language = $data['language'];

    $stmt = $conn->prepare("INSERT INTO data (`name`, `studentId`, `Birthday`, `country`, `language`) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    // Todos como strings
    $stmt->bind_param("sssss", $name, $id, $birth, $country, $language);
    $_SESSION['id'] = $id;
    $success = $stmt->execute();

    echo json_encode([
        'success' => $success
    ]);

    $stmt->close();
    $conn->close();
    exit; 
}

