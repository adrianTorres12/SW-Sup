<?php
// upload-writing-test.php - VERSIÓN CON CÓDIGOS AUTOMÁTICOS
require_once "db_connection.php";

// Verificar si el usuario es teacher
session_start();
if (isset($_SESSION['logged_in_user'])) {
    $user_role = $_SESSION['logged_in_user']['role'] ?? '';
    if ($user_role !== 'teacher') {
        header('Location: ../../welcome.php');
        exit();
    }
} else {
    header('Location: ../../auth.php');
    exit();
}

$existingTests = [];
$res = $practice_conn->query("SELECT test_number, title, access_code FROM writing_tests ORDER BY test_number ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $existingTests[] = $r;
    }
}

// flash message for JS
$flash = ['status'=>'', 'title'=>'', 'message'=>''];

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // retrieve and sanitize
    $test_number = isset($_POST['test_number']) ? (int)$_POST['test_number'] : 0;
    $title = trim($_POST['test_title'] ?? ("Test " . $test_number));
    $part = $_POST['part'] ?? '';
    
    // server-side validations
    if ($test_number <= 0) {
        $flash = ['status'=>'error','title'=>'Invalid test number','message'=>'Please provide a valid test number.'];
    } elseif (!in_array($part, ['1','2','3'])) {
        $flash = ['status'=>'error','title'=>'Invalid part','message'=>'Part must be 1, 2 or 3.'];
    } else {
        // Verificar si ya existe este test+part
        $stmt = $practice_conn->prepare("SELECT id, access_code FROM writing_tests WHERE test_number = ? AND part = ?");
        $stmt->bind_param("is", $test_number, $part);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($existing_id, $existing_code);
        $exists = $stmt->fetch();
        $stmt->close();
        
        // Si no existe, generar código nuevo
        if (!$exists || empty($existing_code)) {
            $access_code = generateAccessCode('WR' . $part, 6); // WR1, WR2, WR3 prefijo
        } else {
            $access_code = $existing_code; // Mantener código existente
        }
        
        // Verificar si ya existe este test_number para generar código de Full Test
        $check_full = $practice_conn->prepare("SELECT COUNT(*) FROM writing_tests WHERE test_number = ?");
        $check_full->bind_param("i", $test_number);
        $check_full->execute();
        $check_full->bind_result($test_count);
        $check_full->fetch();
        $check_full->close();
        
        // Si es el primer test de este número, generar código de Full Test
        $full_test_code = null;
        if ($test_count == 0) {
            $full_test_code = generateFullTestCode($test_number);
        }
        
        if (!$exists) {
            // Crear nuevo test con código
            $ins = $practice_conn->prepare("INSERT INTO writing_tests (test_number, part, title, access_code) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiss", $test_number, $part, $title, $access_code);

            if (!$ins->execute()) {
                $flash = ['status'=>'error','title'=>'DB error','message'=>'Could not create test header: '.$practice_conn->error];
                $ins->close();
            } else {
                $ins->close();
                
                // Si se generó código de Full Test, actualizar todas las partes futuras
                if ($full_test_code) {
                    // Por ahora solo actualizamos esta parte
                    // Más adelante cuando se creen las otras partes, se puede actualizar
                    $update_stmt = $practice_conn->prepare("UPDATE writing_tests SET full_test_code = ? WHERE test_number = ?");
                    $update_stmt->bind_param("si", $full_test_code, $test_number);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        } else {
            // Actualizar test existente (mantener código)
            $up = $practice_conn->prepare("UPDATE writing_tests SET title = ? WHERE test_number = ? AND part = ?");
            $up->bind_param("sis", $title, $test_number, $part);
            $up->execute();
            $up->close();
        }

        // retrieve test id
        $q = $practice_conn->prepare("SELECT id FROM writing_tests WHERE test_number = ? AND part = ?");
        $q->bind_param("is", $test_number, $part);
        $q->execute();
        $q->bind_result($test_id);
        $q->fetch();
        $q->close();

        if (!$test_id) {
            $flash = ['status'=>'error','title'=>'DB error','message'=>'Could not locate test id after creating header.'];
        } else {
            // Part-specific storage (mantener el código existente)
            if ($part === '1') {
                // loop 1..5
                $err = false;
                $stmt = $practice_conn->prepare("INSERT INTO writing_part1_questions (test_id, question_number, keyword1, keyword2, image_base64, image_name, image_type, suggested_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                for ($i=1;$i<=5;$i++){
                    $k1 = trim($_POST["p1_keyword1_$i"] ?? '');
                    $k2 = trim($_POST["p1_keyword2_$i"] ?? '');
                    $ans = trim($_POST["p1_answer_$i"] ?? '');
                    // image optional, but recommended
                    $img_base64 = '';
                    $img_name = '';
                    $img_type = '';
                    if (!empty($_FILES["p1_image_$i"]) && $_FILES["p1_image_$i"]['error'] === UPLOAD_ERR_OK) {
                        $tmp = $_FILES["p1_image_$i"]['tmp_name'];
                        $img_type = $_FILES["p1_image_$i"]['type'];
                        $extension = pathinfo($_FILES["p1_image_$i"]['name'], PATHINFO_EXTENSION);
                        $img_name = uniqid("p1_q{$i}_") . '_' . random_int(1000,9999) . "." . $extension;

                        $data = file_get_contents($tmp);
                        $img_base64 = base64_encode($data);
                    }
                    if ($k1 === '' || $k2 === '' || $ans === '') {
                        $flash = ['status'=>'error','title'=>'Missing fields','message'=>"Please provide keywords and suggested answer for Part1 question $i."];
                        $err = true; break;
                    }
                    $stmt->bind_param("iissssss", $test_id, $i, $k1, $k2, $img_base64, $img_name, $img_type, $ans);
                    if (!$stmt->execute()) {
                        $flash = ['status'=>'error','title'=>'DB error','message'=>'Error inserting part1 question '.$i.': '.$practice_conn->error];
                        $err = true; break;
                    }
                }
                $stmt->close();
                if (!$err && $flash['status']==='') {
                    $flash = [
                        'status'=>'success',
                        'title'=>'Saved',
                        'message'=>'Part 1 questions saved successfully.',
                        'access_code' => $access_code,
                        'full_test_code' => $full_test_code
                    ];
                }
            } elseif ($part === '2') {
                // 2 email questions
                $stmt = $practice_conn->prepare("INSERT INTO writing_part2_questions (test_id, question_number, instructions, email_image_base64, image_name, image_type, suggested_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $err = false;
                for ($i=1;$i<=2;$i++){
                    $instr = trim($_POST["p2_instructions_$i"] ?? '');
                    $ans = trim($_POST["p2_answer_$i"] ?? '');
                    if (!isset($_FILES["p2_image_$i"]) || $_FILES["p2_image_$i"]['error'] !== UPLOAD_ERR_OK) {
                        $flash = ['status'=>'error','title'=>'Missing image','message'=>"Please upload email image for part2 question $i."];
                        $err = true; break;
                    }
                    $tmp = $_FILES["p2_image_$i"]['tmp_name'];
                    $img_type = $_FILES["p2_image_$i"]['type'];
                    $extension = pathinfo($_FILES["p2_image_$i"]['name'], PATHINFO_EXTENSION);
                    $img_name = uniqid("p2_q{$i}_") . '_' . random_int(1000,9999) . "." . $extension;
                    $data = file_get_contents($tmp);
                    $img_base64 = base64_encode($data);
                    if ($instr === '' || $ans === '') {
                        $flash = ['status'=>'error','title'=>'Missing fields','message'=>"Please provide instructions and suggested answer for Part2 question $i."];
                        $err = true; break;
                    }
                    $stmt->bind_param("iisssss", $test_id, $i, $instr, $img_base64, $img_name, $img_type, $ans);
                    if (!$stmt->execute()) {
                        $flash = ['status'=>'error','title'=>'DB error','message'=>'Error inserting part2 question '.$i.': '.$practice_conn->error];
                        $err = true; break;
                    }
                }
                $stmt->close();
                if (!$err && $flash['status']==='') {
                    $flash = [
                        'status'=>'success',
                        'title'=>'Saved',
                        'message'=>'Part 2 questions saved successfully.',
                        'access_code' => $access_code,
                        'full_test_code' => $full_test_code
                    ];
                }
            } elseif ($part === '3') {
                $qtext = trim($_POST['p3_question'] ?? '');
                $ans = trim($_POST['p3_answer'] ?? '');
                if ($qtext === '' || $ans === '') {
                    $flash = ['status'=>'error','title'=>'Missing data','message'=>'Please provide essay question and suggested answer for Part 3.'];
                } else {
                    $stmt = $practice_conn->prepare("INSERT INTO writing_part3_questions (test_id, question_text, suggested_answer) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $test_id, $qtext, $ans);
                    if ($stmt->execute()) {
                        $flash = [
                            'status'=>'success',
                            'title'=>'Saved',
                            'message'=>'Part 3 saved successfully.',
                            'access_code' => $access_code,
                            'full_test_code' => $full_test_code
                        ];
                    } else {
                        $flash = ['status'=>'error','title'=>'DB error','message'=>'Could not save part 3: '.$practice_conn->error];
                    }
                    $stmt->close();
                }
            }
        }
    }
    // refresh existing tests list for display
    $existingTests = [];
    $res = $practice_conn->query("SELECT test_number, title, access_code FROM writing_tests ORDER BY test_number ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $existingTests[] = $r;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Upload Writing Test</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="upload-writing-test.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    window.EXISTING_TESTS = <?php echo json_encode($existingTests); ?>;
    window.PHP_FLASH = <?php echo json_encode($flash); ?>;
  </script>
</head>
<body>
  <header class="header">
    <img src="../../img/ets.png" alt="TOEIC" class="logo">
    <div style="width:12px"></div>
  </header>

  <main class="container">
    <div class="card">

      <div class="top-row">
        <h1><i class="bi bi-cloud-upload-fill"></i> Upload Writing Practice (Practice on my own)</h1>
        <div class="note">All uploaded media and text are stored in the database (base64).</div>
      </div>

      <div>
        <strong>Existing tests:</strong>
        <div class="badges">
          <?php
            if (count($existingTests) === 0) {
              echo '<div class="note">No tests yet.</div>';
            } else {
              foreach ($existingTests as $test) {
                echo "<div class='badge'>Test {$test['test_number']}";
                if (!empty($test['access_code'])) {
                  echo " <small>Code: {$test['access_code']}</small>";
                }
                echo "</div>";
              }
            }
          ?>
        </div>
      </div>

      <form id="uploadForm" method="POST" enctype="multipart/form-data" class="mt-12" novalidate>

        <div class="form-row">
          <div class="form-field">
            <label for="test_number">Test number</label>
            <input type="number" id="test_number" name="test_number" min="1" placeholder="e.g. 1" required>
            <div class="note">Use "Suggest next" to auto-fill next available number.</div>
            <div style="margin-top:6px;">
              <button id="suggestNext" class="btn btn-ghost" type="button">Suggest next</button>
            </div>
          </div>

          <div class="form-field">
            <label for="test_title">Test title</label>
            <input type="text" id="test_title" name="test_title" placeholder="Example: Test 1 (Writing)">
          </div>

          <div class="form-field">
            <label for="part">Select part to upload</label>
            <select id="part" name="part" required>
              <option value="">Choose...</option>
              <option value="1">Part 1 – Picture Descriptions (5 questions)</option>
              <option value="2">Part 2 – Respond to Emails (2 questions)</option>
              <option value="3">Part 3 – Opinion Essay (1 question)</option>
            </select>
          </div>
        </div>

        <!-- PART 1 -->
        <div id="part1" class="part-section" style="display:none;">
          <h3>Part 1 — Picture Descriptions (5)</h3>
          <?php for ($i=1;$i<=5;$i++): ?>
            <div style="margin-top:12px;">
              <strong>Question <?php echo $i; ?></strong>
              <div class="form-row">
                <div class="form-field">
                  <label for="p1_image_<?php echo $i; ?>">Upload image (recommended)</label>
                  <input type="file" id="p1_image_<?php echo $i; ?>" name="p1_image_<?php echo $i; ?>" accept="image/*">
                  <div id="p1_preview_<?php echo $i; ?>" class="preview" style="display:none;"><img src="#" alt="preview"></div>
                </div>
                <div class="form-field">
                  <label for="p1_keyword1_<?php echo $i; ?>">Keyword 1</label>
                  <input type="text" id="p1_keyword1_<?php echo $i; ?>" name="p1_keyword1_<?php echo $i; ?>">
                  <label for="p1_keyword2_<?php echo $i; ?>" style="margin-top:10px;">Keyword 2</label>
                  <input type="text" id="p1_keyword2_<?php echo $i; ?>" name="p1_keyword2_<?php echo $i; ?>">
                </div>
              </div>
              <div class="form-row">
                <div class="form-field">
                  <label for="p1_answer_<?php echo $i; ?>">Suggested answer</label>
                  <textarea id="p1_answer_<?php echo $i; ?>" name="p1_answer_<?php echo $i; ?>"></textarea>
                </div>
              </div>
              <hr>
            </div>
          <?php endfor; ?>
        </div>

        <!-- PART 2 -->
        <div id="part2" class="part-section" style="display:none;">
          <h3>Part 2 — Respond to Emails (2)</h3>
          <?php for ($i=1;$i<=2;$i++): ?>
            <div style="margin-top:12px;">
              <strong>Email <?php echo $i; ?></strong>
              <div class="form-row">
                <div class="form-field">
                  <label for="p2_instructions_<?php echo $i; ?>">Instructions</label>
                  <textarea id="p2_instructions_<?php echo $i; ?>" name="p2_instructions_<?php echo $i; ?>"></textarea>
                </div>
                <div class="form-field">
                  <label for="p2_image_<?php echo $i; ?>">Upload email image (required)</label>
                  <input type="file" id="p2_image_<?php echo $i; ?>" name="p2_image_<?php echo $i; ?>" accept="image/*">
                  <div id="p2_preview_<?php echo $i; ?>" class="preview" style="display:none;"><img src="#" alt="preview"></div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-field">
                  <label for="p2_answer_<?php echo $i; ?>">Suggested answer</label>
                  <textarea id="p2_answer_<?php echo $i; ?>" name="p2_answer_<?php echo $i; ?>"></textarea>
                </div>
              </div>
              <hr>
            </div>
          <?php endfor; ?>
        </div>

        <!-- PART 3 -->
        <div id="part3" class="part-section" style="display:none;">
          <h3>Part 3 — Opinion Essay (1)</h3>
          <div class="form-row">
            <div class="form-field">
              <label for="p3_question">Essay question</label>
              <textarea id="p3_question" name="p3_question"></textarea>
            </div>
          </div>
          <div class="form-row">
            <div class="form-field">
              <label for="p3_answer">Suggested answer</label>
              <textarea id="p3_answer" name="p3_answer"></textarea>
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary">Save part</button>
        </div>

      </form>

    </div>
  </main>

  <script src="upload-writing-test.js"></script>

  <script>
  // show PHP flash using SweetAlert if present
  (function(){
    const flash = window.PHP_FLASH || {};
    if (flash && flash.status) {
      if (flash.status === 'success') {
        let message = flash.message;
        if (flash.access_code) {
          message += '\n\nAccess Code: ' + flash.access_code;
        }
        if (flash.full_test_code) {
          message += '\nFull Test Code: ' + flash.full_test_code + ' (will be activated when all 3 parts are created)';
        }
        
        Swal.fire({
          icon: 'success',
          title: flash.title,
          html: message.replace(/\n/g, '<br>'),
          confirmButtonText: 'OK'
        });
      } else {
        Swal.fire({icon:'error', title:flash.title || 'Error', text:flash.message || ''});
      }
    }
    
    // Suggest next initial
    document.getElementById('suggestNext').addEventListener('click', function(e){
      e.preventDefault();
      const arr = window.EXISTING_TESTS || [];
      let next = 1;
      if (arr.length) {
        const testNumbers = arr.map(test => parseInt(test.test_number));
        next = Math.max(...testNumbers) + 1;
      }
      document.getElementById('test_number').value = next;
    });
    
    // prefill next if no existing
    if ((window.EXISTING_TESTS || []).length === 0) {
      document.getElementById('test_number').value = 1;
    }
  })();
  </script>

</body>
</html>