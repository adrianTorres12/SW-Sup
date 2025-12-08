<?php
// practice-writing-test.php
// Single file for running single-part or full writing practice (Practice on my own)
// Usage:
//  - Single part: practice-writing-test.php?test_number=1&mode=single&part=1
//  - Full test:   practice-writing-test.php?test_number=1&mode=full

session_start();
require_once "db_connection.php"; // Esto establece $practice_conn, NO $conn

// ----------------- validate input -----------------
$test_number = isset($_GET['test_number']) ? (int)$_GET['test_number'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'single'; // 'single' or 'full'
$part = isset($_GET['part']) ? (int)$_GET['part'] : 0;

if ($test_number <= 0) {
    http_response_code(400);
    echo "Invalid request: test_number required.";
    exit;
}
if (!in_array($mode, ['single','full'])) $mode = 'single';
if ($mode === 'single' && !in_array($part, [1,2,3])) {
    http_response_code(400);
    echo "Invalid request: for single mode provide &part=1|2|3";
    exit;
}

// times per part (seconds)
$PART_TIMES = [
    1 => 8 * 60,
    2 => 10 * 60,
    3 => 30 * 60
];

// helper to fetch test header - CAMBIAR $conn por $practice_conn
function get_test_header($practice_conn, $test_number, $part) {
    $stmt = $practice_conn->prepare("SELECT id, title FROM writing_tests WHERE test_number = ? AND part = ?");
    if (!$stmt) return null;
    $stmt->bind_param("ii", $test_number, $part);
    $stmt->execute();
    $stmt->bind_result($id, $title);
    if ($stmt->fetch()) {
        $stmt->close();
        return ['id'=>$id, 'title'=>$title];
    }
    $stmt->close();
    return null;
}

// helper to fetch questions for a part given writing_tests.id - CAMBIAR $conn por $practice_conn
function get_questions_part($practice_conn, $part_table, $test_id) {
    $rows = [];
    if ($part_table === 1) {
        $sql = "SELECT question_number, keyword1, keyword2, image_base64, image_type, suggested_answer FROM writing_part1_questions WHERE test_id = ? ORDER BY question_number ASC";
    } elseif ($part_table === 2) {
        $sql = "SELECT question_number, instructions, email_image_base64 AS image_base64, image_type, suggested_answer FROM writing_part2_questions WHERE test_id = ? ORDER BY question_number ASC";
    } else { // part 3
        // part3 has single essay prompt per test (question_number maybe absent but store as 1)
        $sql = "SELECT id, question_text AS prompt, suggested_answer FROM writing_part3_questions WHERE test_id = ? ORDER BY id ASC";
    }
    $stmt = $practice_conn->prepare($sql);
    if (!$stmt) return $rows;
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}

// ----------------- prepare payload -----------------
$payload = [
    'mode' => $mode,
    'test_number' => $test_number,
    'parts' => [] // each part: {part_number, title, time_allowed_seconds, questions: [...]}
];

if ($mode === 'single') {
    // CAMBIAR $conn por $practice_conn
    $header = get_test_header($practice_conn, $test_number, $part);
    if (!$header) {
        http_response_code(404);
        echo "Test not found for test_number={$test_number} part={$part}";
        exit;
    }
    // CAMBIAR $conn por $practice_conn
    $partQuestions = get_questions_part($practice_conn, $part, $header['id']);
    // normalize questions array for JS
    $questions = [];
    if ($part === 1) {
        foreach ($partQuestions as $q) {
            $questions[] = [
                'question_number' => (int)($q['question_number'] ?? 0),
                'keyword1' => $q['keyword1'] ?? '',
                'keyword2' => $q['keyword2'] ?? '',
                'image_base64' => $q['image_base64'] ?? '',
                'image_type' => $q['image_type'] ?? '',
                'suggested_answer' => $q['suggested_answer'] ?? ''
            ];
        }
    } elseif ($part === 2) {
        foreach ($partQuestions as $q) {
            $questions[] = [
                'question_number' => (int)($q['question_number'] ?? 0),
                'instructions' => $q['instructions'] ?? '',
                'image_base64' => $q['image_base64'] ?? '',
                'image_type' => $q['image_type'] ?? '',
                'suggested_answer' => $q['suggested_answer'] ?? ''
            ];
        }
    } else { // part 3 (essay)
        foreach ($partQuestions as $idx => $q) {
            $questions[] = [
                'question_number' => $idx+1,
                'prompt' => $q['prompt'] ?? '',
                'suggested_answer' => $q['suggested_answer'] ?? ''
            ];
        }
    }

    $payload['parts'][] = [
        'part_number' => $part,
        'title' => $header['title'],
        'time_allowed_seconds' => $PART_TIMES[$part],
        'questions' => $questions
    ];
} else { // full mode -> need to check all three parts exist for this test_number
    for ($p=1;$p<=3;$p++) {
        // CAMBIAR $conn por $practice_conn
        $hdr = get_test_header($practice_conn, $test_number, $p);
        if (!$hdr) {
            http_response_code(404);
            echo "Full test not available. Missing part {$p} for test_number={$test_number}.";
            exit;
        }
        // CAMBIAR $conn por $practice_conn
        $pq = get_questions_part($practice_conn, $p, $hdr['id']);
        // normalize questions
        $questions = [];
        if ($p === 1) {
            foreach ($pq as $q) {
                $questions[] = [
                    'question_number' => (int)($q['question_number'] ?? 0),
                    'keyword1' => $q['keyword1'] ?? '',
                    'keyword2' => $q['keyword2'] ?? '',
                    'image_base64' => $q['image_base64'] ?? '',
                    'image_type' => $q['image_type'] ?? '',
                    'suggested_answer' => $q['suggested_answer'] ?? ''
                ];
            }
        } elseif ($p === 2) {
            foreach ($pq as $q) {
                $questions[] = [
                    'question_number' => (int)($q['question_number'] ?? 0),
                    'instructions' => $q['instructions'] ?? '',
                    'image_base64' => $q['image_base64'] ?? '',
                    'image_type' => $q['image_type'] ?? '',
                    'suggested_answer' => $q['suggested_answer'] ?? ''
                ];
            }
        } else {
            foreach ($pq as $idx => $q) {
                $questions[] = [
                    'question_number' => $idx+1,
                    'prompt' => $q['prompt'] ?? '',
                    'suggested_answer' => $q['suggested_answer'] ?? ''
                ];
            }
        }

        $payload['parts'][] = [
            'part_number' => $p,
            'title' => $hdr['title'],
            'time_allowed_seconds' => $PART_TIMES[$p],
            'questions' => $questions
        ];
    }
}

// ----------------- render page and include payload for JS -----------------
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Practice Test <?php echo htmlspecialchars($test_number); ?> (<?php echo $mode === 'full' ? 'Full' : 'Part '.$payload['parts'][0]['part_number']; ?>)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="practice-writing-test.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>window.PRACTICE_PAYLOAD = <?php echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;</script>
</head>
<body>
  <header class="test-header">
    <div class="header-left">
      <img src="./../../img/ets.webp" alt="TOEIC" class="toeic-logo">
    </div>
    <div class="header-center">
      <div id="global-title" class="global-title">
        <?php if ($mode === 'full'): ?>
          Full Writing Test — Test <?php echo htmlspecialchars($test_number); ?>
        <?php else: ?>
          Part <?php echo $payload['parts'][0]['part_number']; ?> — <?php echo htmlspecialchars($payload['parts'][0]['title']); ?>
        <?php endif; ?>
      </div>
      <div id="question-counter" class="question-counter">Question</div>
    </div>
    <div class="header-right">
      <div id="timer" class="timer">00:00</div>
    </div>
  </header>

  <main class="test-main" id="test-main">
    <div class="directions" id="directions-box">
      <strong>Directions:</strong>
      <div id="directions-text">Follow the instructions for the part you are doing.</div>
    </div>

    <section class="part-area" id="part-area">
      <!-- JS will render current part/question here -->
      <!-- Layout: image/instructions/prompt, keywords or instructions, answer input area, nav -->
      <div class="content-box" id="content-box"></div>

      <div class="nav-row">
        <button id="prevBtn" class="nav-btn" title="Previous"><i class="bi bi-arrow-left-circle"></i></button>
        <div class="spacer"></div>
        <button id="nextBtn" class="nav-btn" title="Next"><i class="bi bi-arrow-right-circle"></i></button>
      </div>
    </section>
  </main>

  <footer class="test-footer">
    <div class="footer-left">Test number: <?php echo htmlspecialchars($test_number); ?></div>
    <div class="footer-center"><?php echo $mode === 'full' ? 'Full practice — Writing' : 'Practice on my own'; ?></div>
    <div class="footer-right">Mode: <?php echo htmlspecialchars($mode); ?></div>
  </footer>

  <script src="practice-writing-test.js"></script>
</body>
</html>