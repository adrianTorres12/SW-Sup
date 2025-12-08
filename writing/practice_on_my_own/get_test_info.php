<?php
// File: writing/practice_on_my_own/get_test_info.php
require_once "db_connection.php";
require_once "../../session_helper.php";

startSecureSession();

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_role = getUserRole();
if ($user_role !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Teachers only']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$test_number = $_POST['test_number'] ?? null;
$part = $_POST['part'] ?? null;

if (!$test_number) {
    echo json_encode(['success' => false, 'message' => 'Test number required']);
    exit();
}

if ($part === 'full') {
    // Obtener información del full test
    $query = "SELECT 
                wt.title,
                wt.access_code,
                wt.full_test_code,
                wt.is_visible_to_students,
                wt.created_at,
                GROUP_CONCAT(DISTINCT wt.part ORDER BY wt.part) as parts
              FROM writing_tests wt
              WHERE wt.test_number = ?
              GROUP BY wt.test_number
              LIMIT 1";
    $stmt = $practice_conn->prepare($query);
    $stmt->bind_param("i", $test_number);
} else {
    // Obtener información de una parte específica
    $query = "SELECT 
                title,
                access_code,
                full_test_code,
                is_visible_to_students,
                created_at,
                part
              FROM writing_tests 
              WHERE test_number = ? AND part = ?
              LIMIT 1";
    $stmt = $practice_conn->prepare($query);
    $stmt->bind_param("ii", $test_number, $part);
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'title' => $row['title'],
        'access_code' => $row['access_code'],
        'full_test_code' => $row['full_test_code'],
        'is_visible_to_students' => (bool)$row['is_visible_to_students'],
        'created_at' => date('F j, Y, g:i a', strtotime($row['created_at'])),
        'parts' => $part === 'full' ? $row['parts'] : null
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Test not found']);
}

$stmt->close();
?>