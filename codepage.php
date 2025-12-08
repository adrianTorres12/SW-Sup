<?php
// File: codepage.php
require_once 'session_helper.php';
require_once 'db.php';

startSecureSession();

// Verificar que el usuario esté logueado y sea estudiante
if (!isUserLoggedIn()) {
    header('Location: auth.php?mode=evaluation');
    exit();
}

$user_role = getUserRole();
$user_class = getUserClass();

// Solo estudiantes de tercer año pueden acceder
if ($user_role !== 'student' || $user_class !== 'third') {
    header('Location: homepage.php');
    exit();
}

$user_fullname = getUserFullName();
$user_id = getUserId(); // Obtener ID usando la nueva función

// Procesar código si se envió
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_code'])) {
    $access_code = trim($_POST['access_code']);
    
    // Conectar a practice_writing database
    require_once './writing/practice_on_my_own/db_connection.php';
    
    // Buscar el test por código de acceso
    $query = "SELECT id, test_number, part, title, is_full_test 
              FROM writing_tests 
              WHERE access_code = ? AND is_visible_to_students = 1";
    $stmt = $practice_conn->prepare($query);
    $stmt->bind_param("s", $access_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Registrar acceso (solo si la tabla existe, sino omitir)
        if ($user_id) {
            // Verificar si la tabla existe antes de insertar
            $table_check = $practice_conn->query("SHOW TABLES LIKE 'test_access_logs'");
            if ($table_check && $table_check->num_rows > 0) {
                $log_query = "INSERT INTO test_access_logs (test_id, user_id, access_type, access_code) 
                              VALUES (?, ?, 'code', ?)";
                $log_stmt = $practice_conn->prepare($log_query);
                if ($log_stmt) {
                    $log_stmt->bind_param("iis", $row['id'], $user_id, $access_code);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            }
        }
        
        // Redirigir al test
        if ($row['is_full_test']) {
            header("Location: ./writing/practice_on_my_own/practice-writing-test.php?test_number={$row['test_number']}&mode=full&code={$access_code}");
        } else {
            header("Location: ./writing/practice_on_my_own/practice-writing-test.php?test_number={$row['test_number']}&part={$row['part']}&code={$access_code}");
        }
        exit();
    } else {
        $message = 'Invalid or expired access code. Please check the code and try again.';
        $message_type = 'error';
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Evaluation Test Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafd 0%, #e8f0fe 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .main-header {
            width: 100%;
            background-color: #003B95;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 59, 149, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
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
        
        .role-badge.student {
            background-color: #17a2b8;
            color: white;
        }
        
        .code-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 43, 111, 0.15);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            margin-top: 80px;
            border: 2px solid #e8f0fe;
        }
        
        .code-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .code-icon {
            font-size: 3.5rem;
            color: #0056D2;
            margin-bottom: 20px;
        }
        
        .code-title {
            color: #003B95;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .code-subtitle {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .code-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            color: #003B95;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .code-input {
            padding: 15px 20px;
            border: 2px solid #d3dff8;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 2px;
            text-align: center;
            text-transform: uppercase;
            color: #003B95;
            transition: all 0.3s ease;
        }
        
        .code-input:focus {
            outline: none;
            border-color: #0056D2;
            box-shadow: 0 0 0 3px rgba(0, 86, 210, 0.2);
        }
        
        .code-input::placeholder {
            color: #a0b4d9;
            letter-spacing: normal;
            font-weight: normal;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #0056D2 0%, #003B95 100%);
            color: white;
            border: none;
            padding: 16px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 86, 210, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #0056D2;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #003B95;
            transform: translateX(-5px);
        }
        
        .instructions {
            background-color: #f0f7ff;
            border-left: 4px solid #0056D2;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .instructions h4 {
            color: #003B95;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .instructions ul {
            padding-left: 20px;
            color: #555;
            line-height: 1.6;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-header {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .code-container {
                padding: 30px 20px;
                margin-top: 120px;
            }
            
            .code-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .code-container {
                padding: 25px 15px;
                margin-top: 140px;
            }
            
            .code-input {
                padding: 12px 15px;
                font-size: 1rem;
            }
            
            .submit-btn {
                padding: 14px 20px;
                font-size: 1rem;
            }
            
            .user-info {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .user-profile-btn,
            .logout-btn {
                width: 100%;
                max-width: 200px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER CON LOGOUT -->
    <header class="main-header">
        <h1>TOEIC Evaluation Practice</h1>
        <div class="user-info">
            <button class="user-profile-btn" onclick="showUserInfo()">
                <i class="bi bi-person-circle"></i>
                <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
                <span class="role-badge student">
                    Student (<?php echo $user_class; ?> year)
                </span>
            </button>
            <button class="logout-btn" onclick="handleLogout()">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
    </header>

    <div class="code-container">
        <div class="code-header">
            <div class="code-icon">
                <i class="bi bi-key-fill"></i>
            </div>
            <h1 class="code-title">Enter Test Access Code</h1>
            <p class="code-subtitle">
                Enter the access code provided by your teacher to start an evaluation test. 
                Your responses will be saved for teacher review.
            </p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="code-form">
            <div class="form-group">
                <label for="access_code" class="form-label">Access Code</label>
                <input 
                    type="text" 
                    id="access_code" 
                    name="access_code" 
                    class="code-input" 
                    placeholder="Enter 8-digit code"
                    maxlength="8"
                    pattern="[A-Z0-9]{8}"
                    title="Please enter an 8-character code (letters and numbers)"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="submit-btn">
                <i class="bi bi-unlock-fill"></i> Start Evaluation Test
            </button>
        </form>

        <div class="instructions">
            <h4><i class="bi bi-info-circle"></i> How to use:</h4>
            <ul>
                <li>Get the 8-digit access code from your teacher</li>
                <li>Enter the code exactly as provided (case-sensitive)</li>
                <li>Click "Start Evaluation Test" to begin</li>
                <li>Complete all parts of the test in one session</li>
                <li>Your responses will be automatically saved</li>
            </ul>
        </div>

        <a href="homepage.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Homepage
        </a>
    </div>

    <script>
        // Mostrar información del usuario
        function showUserInfo() {
            const userInfo = `Student Information:\n\nName: <?php echo addslashes($user_fullname); ?>\nClass: <?php echo $user_class; ?> year\nRole: Student`;
            alert(userInfo);
        }
        
        // Manejar logout
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'welcome.php?logout=1';
            }
        }
        
        // Formato automático del código (agregar guiones)
        document.getElementById('access_code').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Insertar guión después de 4 caracteres
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4, 8);
            }
            
            e.target.value = value;
        });
        
        // Validar formato antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const codeInput = document.getElementById('access_code');
            const code = codeInput.value.replace(/-/g, '');
            
            if (code.length !== 8) {
                e.preventDefault();
                alert('Please enter a valid 8-character access code.');
                codeInput.focus();
            }
        });
    </script>
</body>
</html>