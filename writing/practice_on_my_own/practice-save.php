<?php
// practice-save.php
// Stores practice attempt in session (temporary). No DB writes.

session_start();
require_once "db_connection.php";

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success'=>false, 'message'=>'Invalid JSON']);
    exit;
}

$test_number = isset($data['test_number']) ? (int)$data['test_number'] : 0;
$part = isset($data['part']) ? (int)$data['part'] : 0;
$answers = $data['answers'] ?? [];
$time_left = isset($data['time_left']) ? (int)$data['time_left'] : null;
$trigger = $data['trigger'] ?? 'manual';
$timestamp = $data['timestamp'] ?? date('c');

if ($test_number <= 0 || !in_array($part, [1,2,3])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid payload: test_number or part']);
    exit;
}

// optional: verify test exists (not mandatory)
$stmt = $practice_conn->prepare("SELECT id FROM writing_tests WHERE test_number = ? AND part = ?");
if ($stmt) {
    $stmt->bind_param("ii", $test_number, $part);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
} else {
    $exists = false;
}

// store in session (history)
if (!isset($_SESSION['practice_attempts'])) $_SESSION['practice_attempts'] = [];

$key = 'attempt_' . time();
$_SESSION['practice_attempts'][$key] = [
    'test_number' => $test_number,
    'part' => $part,
    'answers' => $answers,
    'time_left' => $time_left,
    'trigger' => $trigger,
    'received_at' => $timestamp,
    'exists' => $exists
];

// store answers in a structure review page expects:
// $_SESSION['practice_answers'][<part>][<test_number>] = answers
if (!isset($_SESSION['practice_answers'])) $_SESSION['practice_answers'] = [];

if (!isset($_SESSION['practice_answers'][$part])) $_SESSION['practice_answers'][$part] = [];

$_SESSION['practice_answers'][$part][$test_number] = $answers;

echo json_encode(['success'=>true, 'message'=>'Practice answers stored in session.']);
exit;