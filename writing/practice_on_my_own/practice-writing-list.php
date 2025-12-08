<?php
// File: writing/practice_on_my_own/practice-writing-list.php

require_once "db_connection.php";
require_once "../../session_helper.php";

startSecureSession();

if (!isUserLoggedIn()) {
    header('Location: ../../auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Obtener información del usuario actual
$user_role = getUserRole();
$user_class = getUserClass();
$user_fullname = getUserFullName();
$user_id = getUserId();

$isTeacher = ($user_role === 'teacher');
$isThirdYearStudent = ($user_role === 'student' && $user_class === 'third');

// ===================================
// VALIDATE MODE/PART
// ===================================
if (!isset($_GET['part'])) { 
    die("Error: No part selected."); 
}

$part = $_GET['part'];

// part can be: 1, 2, 3, or 'full'
$isFull = ($part === "full");

// ===================================
// FETCH TESTS BASED ON USER ROLE
// ===================================
if ($isFull) {
    // FULL TEST MODE - Buscar test_numbers que tengan las 3 partes
    if ($isTeacher) {
        // Teachers ven todos los tests completos (con partes 1, 2 y 3)
        $sql = "
            SELECT test_number, 
                   GROUP_CONCAT(part ORDER BY part) as parts_available,
                   COUNT(*) as part_count
            FROM writing_tests 
            GROUP BY test_number 
            HAVING COUNT(*) >= 3 
               AND SUM(part = 1) >= 1 
               AND SUM(part = 2) >= 1 
               AND SUM(part = 3) >= 1
            ORDER BY test_number ASC;
        ";
        
        $result = $practice_conn->query($sql);
        if (!$result) {
            die("Error en consulta: " . $practice_conn->error);
        }
    } else {
        // Estudiantes solo ven full tests visibles
        $sql = "
            SELECT test_number, 
                   GROUP_CONCAT(part ORDER BY part) as parts_available,
                   COUNT(*) as part_count
            FROM writing_tests 
            WHERE is_visible_to_students = 1
            GROUP BY test_number 
            HAVING COUNT(*) >= 3 
               AND SUM(part = 1) >= 1 
               AND SUM(part = 2) >= 1 
               AND SUM(part = 3) >= 1
            ORDER BY test_number ASC;
        ";
        
        $stmt = $practice_conn->prepare($sql);
        if (!$stmt) {
            die("Error en preparación: " . $practice_conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $title = "FULL Writing Test (Parts 1-2-3)";
} 
else {
    // SINGLE PART MODE
    $part = intval($part);
    
    if ($part < 1 || $part > 3) { 
        die("Invalid part"); 
    }
    
    if ($isTeacher) {
        // Teachers ven todos los tests de esta parte
        $query = "SELECT test_number, title, access_code, is_visible_to_students 
                  FROM writing_tests 
                  WHERE part=? 
                  ORDER BY test_number ASC";
        $stmt = $practice_conn->prepare($query);
        $stmt->bind_param("i", $part);
    } else {
        // Estudiantes solo ven tests visibles
        $query = "SELECT test_number, title, access_code 
                  FROM writing_tests 
                  WHERE part=? 
                  AND is_visible_to_students = 1
                  ORDER BY test_number ASC";
        $stmt = $practice_conn->prepare($query);
        $stmt->bind_param("i", $part);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $titles = [
        1 => "Write a Sentence Based on a Picture",
        2 => "Respond to a Written Request",
        3 => "Write an Opinion Essay"
    ];
    $title = $titles[$part];
}

$tests = [];
if ($isFull) {
    while ($row = $result->fetch_assoc()) {
        // Para full tests, necesitamos obtener el título de una de las partes
        $test_number = $row['test_number'];
        $title_query = "SELECT title FROM writing_tests WHERE test_number = ? AND part = 1 LIMIT 1";
        $title_stmt = $practice_conn->prepare($title_query);
        $title_stmt->bind_param("i", $test_number);
        $title_stmt->execute();
        $title_stmt->bind_result($test_title);
        $title_stmt->fetch();
        $title_stmt->close();
        
        // Para teachers, obtener visibilidad
        if ($isTeacher) {
            $visibility_query = "SELECT is_visible_to_students FROM writing_tests WHERE test_number = ? LIMIT 1";
            $vis_stmt = $practice_conn->prepare($visibility_query);
            $vis_stmt->bind_param("i", $test_number);
            $vis_stmt->execute();
            $vis_stmt->bind_result($is_visible);
            $vis_stmt->fetch();
            $vis_stmt->close();
            
            $tests[] = [
                'test_number' => $test_number,
                'title' => $test_title ?: "Full Test #$test_number",
                'access_code' => null,
                'is_visible_to_students' => $is_visible,
                'parts_available' => $row['parts_available']
            ];
        } else {
            $tests[] = [
                'test_number' => $test_number,
                'title' => $test_title ?: "Full Test #$test_number",
                'access_code' => null
            ];
        }
    }
} else {
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="practice-writing-list.css">
<style>
    /* Header con logout */
    .main-header {
        background-color: #003B95;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 15px rgba(0, 59, 149, 0.1);
        margin-bottom: 30px;
    }
    
    .main-header h1 {
        color: white;
        font-size: 1.4rem;
        margin: 0;
        font-weight: 600;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-profile-btn {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .user-profile-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
    }
    
    .logout-btn {
        background: #0056D2;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
    }
    
    .logout-btn:hover {
        background: #003B95;
        transform: translateY(-1px);
        color: white;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 10px;
    }
    
    .role-badge.teacher {
        background-color: #28a745;
        color: white;
    }
    
    .role-badge.student {
        background-color: #17a2b8;
        color: white;
    }
    
    /* Badge de visibilidad */
    .visibility-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .visibility-badge.visible {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .visibility-badge.hidden {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    /* Badge de partes disponibles */
    .parts-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 5px;
        background-color: #e8f0fe;
        color: #0056D2;
        border: 1px solid #c8d6fd;
    }
    
    /* Botón de configuración para teachers */
    .config-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.8rem;
        margin-top: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .config-btn:hover {
        background: #545b62;
        transform: translateY(-1px);
    }
    
    /* Modal personalizado */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 25px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e8f0fe;
    }
    
    .modal-title {
        font-size: 1.5rem;
        color: #003b95;
        font-weight: 600;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #6c757d;
        cursor: pointer;
    }
    
    .close-modal:hover {
        color: #003b95;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .info-label {
        font-weight: 600;
        color: #495057;
        min-width: 140px;
    }
    
    .info-value {
        color: #212529;
    }
    
    .code-display {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #e8f0fe;
        padding: 10px 15px;
        border-radius: 8px;
        border: 1px solid #c8d6fd;
    }
    
    .code-text {
        font-family: monospace;
        font-size: 1.1rem;
        font-weight: 600;
        color: #003b95;
        letter-spacing: 1px;
    }
    
    .copy-btn {
        background: #0056d2;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .copy-btn:hover {
        background: #003b95;
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 30px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #28a745;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(30px);
    }
    
    .toggle-label {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 20px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .save-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 20px;
    }
    
    .save-btn:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    
    .save-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .main-header {
            flex-direction: column;
            padding: 12px 15px;
            gap: 15px;
        }
        
        .user-info {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .modal-content {
            width: 95%;
            padding: 20px;
        }
        
        .info-row {
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            min-width: auto;
        }
    }
</style>
</head>
<body>
<!-- HEADER CON LOGOUT -->
<header class="main-header">
    <h1>TOEIC Writing Practice - <?php echo $title; ?></h1>
    <div class="user-info">
        <button class="user-profile-btn" onclick="showUserInfo()">
            <i class="bi bi-person-circle"></i>
            <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
            <span class="role-badge <?php echo $user_role; ?>">
                <?php echo ucfirst($user_role); ?>
                <?php if ($user_class): ?>
                    (<?php echo $user_class; ?> year)
                <?php endif; ?>
            </span>
        </button>
        <button class="logout-btn" onclick="handleLogout()">
            <i class="bi bi-box-arrow-right"></i> Logout
        </button>
    </div>
</header>

<main class="container">
<div class="tests-grid">

<?php if (count($tests) > 0): ?>
    <?php foreach ($tests as $t): ?>
        <?php if ($isFull): ?>
            <!-- FULL TEST CARD -->
            <div class="test-card" data-test-number="<?php echo $t['test_number']; ?>" data-test-type="full">
                <div class="card-top"><i class="bi bi-collection card-icon"></i></div>
                <div class="card-bottom">
                    <h3 class="test-title">Full Test #<?php echo $t['test_number']; ?></h3>
                    <?php if (isset($t['parts_available'])): ?>
                        <div class="parts-badge">
                            Parts: <?php echo $t['parts_available']; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isTeacher && isset($t['is_visible_to_students'])): ?>
                        <div class="visibility-badge <?php echo $t['is_visible_to_students'] ? 'visible' : 'hidden'; ?>">
                            <?php echo $t['is_visible_to_students'] ? 'Visible to Students' : 'Hidden from Students'; ?>
                        </div>
                        <button class="config-btn" onclick="openTestConfig(<?php echo $t['test_number']; ?>, 'full')">
                            <i class="bi bi-gear"></i> Configure
                        </button>
                    <?php endif; ?>
                    <a href="practice-writing-test.php?test_number=<?php echo $t['test_number']; ?>&mode=full" 
                       class="btn btn-primary mt-2" style="width: 100%;">
                        <i class="bi bi-play-circle"></i> Start Full Test
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- SINGLE PART CARD -->
            <div class="test-card" data-test-number="<?php echo $t['test_number']; ?>" data-test-part="<?php echo $part; ?>">
                <div class="card-top"><i class="bi bi-pencil-square card-icon"></i></div>
                <div class="card-bottom">
                    <h3 class="test-title"><?php echo htmlspecialchars($t['title']); ?></h3>
                    <?php if ($isTeacher && isset($t['is_visible_to_students'])): ?>
                        <div class="visibility-badge <?php echo $t['is_visible_to_students'] ? 'visible' : 'hidden'; ?>">
                            <?php echo $t['is_visible_to_students'] ? 'Visible to Students' : 'Hidden from Students'; ?>
                        </div>
                        <button class="config-btn" onclick="openTestConfig(<?php echo $t['test_number']; ?>, <?php echo $part; ?>)">
                            <i class="bi bi-gear"></i> Configure
                        </button>
                    <?php endif; ?>
                    <a href="practice-writing-test.php?test_number=<?php echo $t['test_number']; ?>&part=<?php echo $part; ?>" 
                       class="btn btn-primary mt-2" style="width: 100%;">
                        <i class="bi bi-play-circle"></i> Start Test
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p class="no-tests">No tests available.</p>
    <?php if ($isFull && $isTeacher): ?>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> 
            <strong>No Full Tests Available:</strong> A Full Test requires a test number that has all three parts (1, 2, and 3) created. 
            Create tests for each part with the same test number, then they will appear here as a Full Test.
        </div>
    <?php endif; ?>
<?php endif; ?>

</div>
</main>

<!-- MODAL DE CONFIGURACIÓN PARA TEACHERS -->
<?php if ($isTeacher): ?>
<div class="modal-overlay" id="configModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Test Configuration</h3>
            <button class="close-modal" onclick="closeConfigModal()">&times;</button>
        </div>
        
        <div id="modalBody">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>
<?php endif; ?>

<script src="practice-writing-list.js"></script>
<script>
// Información del usuario
const currentUser = {
    id: <?php echo $user_id ?: 'null'; ?>,
    role: '<?php echo $user_role; ?>',
    name: '<?php echo addslashes($user_fullname); ?>'
};

// Mostrar información del usuario
function showUserInfo() {
    const info = `User Information:\n\nName: ${currentUser.name}\nRole: ${currentUser.role}`;
    alert(info);
}

// Manejar logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../welcome.php?logout=1';
    }
}

<?php if ($isTeacher): ?>
// Variables globales para la configuración
let currentTestNumber = null;
let currentTestPart = null;
let currentTestType = null;
let currentVisibility = false;
let currentAccessCode = '';

// Abrir modal de configuración
function openTestConfig(testNumber, testPartOrType) {
    currentTestNumber = testNumber;
    
    if (testPartOrType === 'full') {
        currentTestType = 'full';
        currentTestPart = null;
    } else {
        currentTestType = 'single';
        currentTestPart = testPartOrType;
    }
    
    // Cargar información del test
    fetch('get_test_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `test_number=${testNumber}&part=${testPartOrType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentVisibility = data.is_visible_to_students;
            currentAccessCode = data.access_code;
            updateModalContent(data);
            document.getElementById('configModal').style.display = 'flex';
        } else {
            alert('Error loading test information: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading test information.');
    });
}

// Actualizar contenido del modal
function updateModalContent(testData) {
    const modalBody = document.getElementById('modalBody');
    
    let content = `
        <div class="info-row">
            <div class="info-label">Test Title:</div>
            <div class="info-value">${testData.title || 'Full Test #' + currentTestNumber}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Test Type:</div>
            <div class="info-value">${currentTestType === 'full' ? 'Full Test (Parts 1-2-3)' : 'Part ' + currentTestPart}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Test Number:</div>
            <div class="info-value">${currentTestNumber}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Created At:</div>
            <div class="info-value">${testData.created_at}</div>
        </div>
        
        ${testData.access_code ? `
        <div class="info-row">
            <div class="info-label">Part Access Code:</div>
            <div class="info-value">
                <div class="code-display">
                    <span class="code-text">${testData.access_code}</span>
                    <button class="copy-btn" onclick="copyToClipboard('${testData.access_code}')">
                        <i class="bi bi-copy"></i> Copy
                    </button>
                </div>
                <small class="text-muted">For individual part access</small>
            </div>
        </div>
        ` : ''}
        
        ${testData.full_test_code ? `
        <div class="info-row">
            <div class="info-label">Full Test Code:</div>
            <div class="info-value">
                <div class="code-display" style="background: #e8f7ff;">
                    <span class="code-text" style="color: #0066cc;">${testData.full_test_code}</span>
                    <button class="copy-btn" onclick="copyToClipboard('${testData.full_test_code}')" style="background: #0066cc;">
                        <i class="bi bi-copy"></i> Copy
                    </button>
                </div>
                <small class="text-muted">For full test access (all 3 parts)</small>
            </div>
        </div>
        ` : ''}
        
        <div class="toggle-label">
            <span>Make test visible to students:</span>
            <label class="toggle-switch">
                <input type="checkbox" id="visibilityToggle" ${currentVisibility ? 'checked' : ''} onchange="updateVisibility()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            ${currentTestType === 'full' ? 
                'Note: Changing visibility for a full test will affect all its parts (1, 2, and 3).' : 
                'Students can access this test through the practice list or using the access code.'}
        </div>
        
        <button class="save-btn" id="saveBtn" onclick="saveTestConfig()">
            <i class="bi bi-save"></i> Save Changes
        </button>
    `;
    
    modalBody.innerHTML = content;
}

// Actualizar visibilidad en tiempo real
function updateVisibility() {
    currentVisibility = document.getElementById('visibilityToggle').checked;
}

// Copiar código de acceso
function copyAccessCode() {
    navigator.clipboard.writeText(currentAccessCode)
        .then(() => {
            alert('Access code copied to clipboard!');
        })
        .catch(err => {
            console.error('Failed to copy: ', err);
            alert('Failed to copy access code.');
        });
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
        .then(() => {
            alert('Code copied to clipboard: ' + text);
        })
        .catch(err => {
            console.error('Failed to copy: ', err);
            alert('Failed to copy code.');
        });
}

// Guardar configuración
function saveTestConfig() {
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    
    fetch('update_test_config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `test_number=${currentTestNumber}&part=${currentTestPart || 'full'}&is_visible=${currentVisibility ? 1 : 0}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Configuration saved successfully!');
            location.reload(); // Recargar para ver cambios
        } else {
            alert('Error saving configuration: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving configuration.');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
    });
}

// Cerrar modal
function closeConfigModal() {
    document.getElementById('configModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('configModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfigModal();
    }
});
<?php endif; ?>
</script>
</body>
</html>