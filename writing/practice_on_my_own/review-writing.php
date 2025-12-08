<?php
session_start();
require_once "db_connection.php";

/* -------------------------------------------------------
   VALIDAR GET
-------------------------------------------------------- */
$test_number = isset($_GET['test_number']) ? (int)$_GET['test_number'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : "single"; 
// mode = single → parte individual
// mode = full → mostrar 1,2,3

if ($test_number <= 0) {
    die("Invalid request. Provide ?test_number=X");
}

/* -------------------------------------------------------
   REUNIR RESPUESTAS GUARDADAS
-------------------------------------------------------- */
$finalAnswers = [];

// FULL TEST → respuestas agrupadas en 3 partes
if ($mode === "full") {
    if (!isset($_SESSION['review_answers'][$test_number])) {
        die("Error: No saved answers for full test. Please complete all parts first.");
    }
    $finalAnswers = $_SESSION['review_answers'][$test_number];
}

// SINGLE PART
else {
    $part = isset($_GET['part']) ? (int)$_GET['part'] : 0;

    if (!in_array($part, [1, 2, 3])) {
        die("Invalid request. Missing valid part.");
    }

    if (!isset($_SESSION['review_answers'][$test_number][$part])) {
        die("Error: No saved answers. Please take the test first.");
    }

    $finalAnswers[$part] = $_SESSION['review_answers'][$test_number][$part];
}

/* -------------------------------------------------------
   OBTENER LAS PREGUNTAS DE LA BASE
-------------------------------------------------------- */

function getQuestions($practice_conn, $test_number, $part) {
    if ($part === 1) {
        $sql = "SELECT question_number, keyword1, keyword2, image_base64, image_type, suggested_answer
                FROM writing_part1_questions
                WHERE test_id = (SELECT id FROM writing_tests WHERE test_number = ? AND part = 1)
                ORDER BY question_number ASC";
    }
    elseif ($part === 2) {
        $sql = "SELECT question_number, instructions, email_image_base64 AS image_base64,
                       image_type, suggested_answer
                FROM writing_part2_questions
                WHERE test_id = (SELECT id FROM writing_tests WHERE test_number = ? AND part = 2)
                ORDER BY question_number ASC";
    }
    else { // part 3
        $sql = "SELECT question_text AS prompt, suggested_answer
                FROM writing_part3_questions
                WHERE test_id = (SELECT id FROM writing_tests WHERE test_number = ? AND part = 3)
                ORDER BY id ASC";
    }

    $stmt = $practice_conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $practice_conn->error);
    }

    $stmt->bind_param("i", $test_number);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* -------------------------------------------------------
   TITULOS POR PARTE
-------------------------------------------------------- */
$partTitles = [
    1 => "Part 1 — Write a Sentence Based on a Picture",
    2 => "Part 2 — Respond to a Written Request",
    3 => "Part 3 — Opinion Essay"
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Review — Test <?php echo htmlspecialchars($test_number) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="review-writing.css">
</head>

<body>

<header class="header">
    <img src="../../img/ets.webp" class="logo">
    <h1>Review — Test <?php echo $test_number; ?></h1>
</header>

<main class="container">

<?php
/* -------------------------------------------------------
   MOSTRAR TODAS LAS PARTES
-------------------------------------------------------- */

foreach ($finalAnswers as $part => $answers) {

    echo "<h2 class='part-title'>{$partTitles[$part]}</h2>";

    $questions = getQuestions($practice_conn, $test_number, $part);

    if (count($questions) === 0) {
        echo "<p class='no-data'>No questions found for part $part.</p>";
        continue;
    }

    /* PART 1 ----------------------------------------------------- */
    if ($part === 1) {
        foreach ($questions as $q) {
            $num = (string)$q['question_number'];
            $user = $answers[$num] ?? "";
?>
            <section class="card">
                <h3>Question <?php echo $q['question_number']; ?></h3>

                <div class="image-wrap">
                    <img src="data:<?php echo $q['image_type'] ?>;base64,<?php echo $q['image_base64']; ?>">
                </div>

                <p class="keywords"><strong>Keywords:</strong>
                    <?php echo htmlspecialchars($q['keyword1'] . ", " . $q['keyword2']); ?>
                </p>

                <label>Your answer:</label>
                <textarea disabled class="answer-box"><?php echo htmlspecialchars($user); ?></textarea>

                <label>Suggested answer:</label>
                <div class="suggested"><?php echo nl2br(htmlspecialchars($q['suggested_answer'])); ?></div>
            </section>
<?php
        }
    }

    /* PART 2 ----------------------------------------------------- */
    elseif ($part === 2) {
        foreach ($questions as $q) {
            $num = (string)$q['question_number'];
            $user = $answers[$num] ?? "";
?>
            <section class="card">
                <h3>Email <?php echo $q['question_number']; ?></h3>

                <div class="instructions">
                    <?php echo nl2br(htmlspecialchars($q['instructions'])); ?>
                </div>

                <div class="image-wrap" style="margin-top:10px;">
                    <?php if (!empty($q['image_base64'])): ?>
                        <img src="data:<?php echo $q['image_type'] ?>;base64,<?php echo $q['image_base64']; ?>">
                    <?php endif; ?>
                </div>

                <label>Your answer:</label>
                <textarea disabled class="answer-box email"><?php echo htmlspecialchars($user); ?></textarea>

                <label>Suggested answer:</label>
                <div class="suggested"><?php echo nl2br(htmlspecialchars($q['suggested_answer'])); ?></div>
            </section>
<?php
        }
    }

    /* PART 3 ----------------------------------------------------- */
    else {
        // Part 3 stores only one answer under key "1"
        $user = $answers["1"] ?? "";

        foreach ($questions as $q) {
?>
            <section class="card">
                <h3>Essay Prompt</h3>

                <div class="instructions"><?php echo nl2br(htmlspecialchars($q['prompt'])); ?></div>

                <label>Your essay:</label>
                <textarea disabled class="answer-box essay"><?php echo htmlspecialchars($user); ?></textarea>

                <label>Suggested answer:</label>
                <div class="suggested"><?php echo nl2br(htmlspecialchars($q['suggested_answer'])); ?></div>
            </section>
<?php
        }
    }
}
?>

    <a href="../../homepage.php" class="back-btn">Return to Homepage</a>

</main>

</body>
</html>