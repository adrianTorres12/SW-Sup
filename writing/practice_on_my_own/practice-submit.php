<?php
// practice-submit.php
session_start();
require_once "db_connection.php";
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success'=>false,'message'=>'Invalid JSON']);
    exit;
}
$test_number = isset($data['test_number']) ? (int)$data['test_number'] : 0;
$mode = isset($data['mode']) ? $data['mode'] : 'single';
$answers = $data['answers'] ?? [];
$times = $data['times'] ?? [];
$trigger = $data['trigger'] ?? 'manual';
$received_at = $data['received_at'] ?? date('c');

if ($test_number <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid test_number']);
    exit;
}

// init session containers
if (!isset($_SESSION['practice_attempts'])) $_SESSION['practice_attempts'] = [];
if (!isset($_SESSION['review_answers'])) $_SESSION['review_answers'] = [];

// store an attempt record
$key = 'attempt_' . time();
$_SESSION['practice_attempts'][$key] = [
    'test_number' => $test_number,
    'mode' => $mode,
    'answers' => $answers,
    'times' => $times,
    'trigger' => $trigger,
    'received_at' => $received_at
];

// map answers into review_answers shape expected by review-writing.php
// For single mode: answers are {qnum: text} (number keys or q1, etc).
// For full mode: answers are {partNumber: {qnum: text}}
if (!isset($_SESSION['review_answers'][$test_number])) $_SESSION['review_answers'][$test_number] = [];
if ($mode === 'single') {
    // We need part number from answers object keys or times. But frontend sends keys by part when single too:
    // We assume the single payload used key names of question numbers only. So we try best-effort:
    // We'll find which part times are present to detect part number
    $partNumber = null;
    if (!empty($times)) {
        // use the first key
        foreach ($times as $k=>$v){ $partNumber = (int)$k; break; }
    }
    if (!$partNumber) {
        // as fallback, try to detect single part number by checking keys in answers: if keys are like "1","2" use the only part in POST, but we can't be sure.
        $partNumber = 1;
    }
    $_SESSION['review_answers'][$test_number][$partNumber] = $answers;
} else {
    // full mode: answers is {partNum: {qnum: text}}
    foreach ($answers as $p => $vals) {
        $pnum = (int)$p;
        $_SESSION['review_answers'][$test_number][$pnum] = $vals;
    }
}

echo json_encode(['success'=>true,'message'=>'Stored answers in session.']);
exit;