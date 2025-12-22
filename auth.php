<?php
// auth.php
// Login / Sign Up page (now connected to MySQL)
//
// BEFORE USING: create allowed_teachers table (one-time):
/*
CREATE TABLE IF NOT EXISTS allowed_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/

require_once 'session_helper.php';
startSecureSession();

// Redirect if already logged in usando nuestro sistema mejorado
if (isUserLoggedIn()) {
    // Verificar el destino después del login
    $mode = $_GET['mode'] ?? $_GET['redirect'] ?? '';
    
    // También verificar localStorage vía JavaScript
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Redirecting...</title>
        <script>
            (function() {
                const redirectAfterLogin = localStorage.getItem('redirectAfterLogin');
                const userRole = '<?php echo $_SESSION['logged_in_user']['role']; ?>';
                const userClass = '<?php echo $_SESSION['logged_in_user']['class_of'] ?? ''; ?>';
                const urlMode = '<?php echo $mode; ?>';
                
                console.log('Auto-redirect check:', {
                    urlMode: urlMode,
                    localStorageRedirect: redirectAfterLogin,
                    userRole: userRole,
                    userClass: userClass
                });
                
                // Limpiar localStorage
                localStorage.removeItem('redirectAfterLogin');
                
                // Determinar a dónde redirigir
                let redirectUrl = '';
                
                // SIEMPRE redirigir a homepage cuando el modo es homepage
                if (redirectAfterLogin === 'homepage' || urlMode === 'homepage') {
                    redirectUrl = 'homepage.php';
                } else if (redirectAfterLogin === 'evaluation' || urlMode === 'evaluation') {
                    if (userRole === 'teacher') {
                        redirectUrl = 'admin/admin.php';
                    } else if (userRole === 'student' && userClass === 'third') {
                        redirectUrl = 'codepage.php';
                    } else {
                        redirectUrl = 'welcome.php';
                    }
                } else {
                    // Default redirect based on role
                    if (userRole === 'teacher') {
                        redirectUrl = 'admin/admin.php';
                    } else {
                        redirectUrl = 'welcome.php';
                    }
                }
                
                console.log('Redirecting to:', redirectUrl);
                window.location.href = redirectUrl;
            })();
        </script>
    </head>
    <body>
        <p>Redirecting... Please wait.</p>
    </body>
    </html>
    <?php
    exit();
}

require_once "db.php"; // Your real database connection (must provide $conn - mysqli)

// Check if there's a remember me cookie
if (!isUserLoggedIn() && isset($_COOKIE['remember_user'])) {
    $remember_token = $_COOKIE['remember_user'];
    
    // Find user with this remember token
    $stmt = $conn->prepare("SELECT id, fullname, lastnames, email, class_of, class_code, role FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $remember_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['logged_in_user'] = $user;
        $_SESSION['last_activity'] = time();
        
        // Redirect based on role
        if ($user['role'] === 'teacher') {
            header('Location: admin/admin.php');
        } else {
            header('Location: welcome.php');
        }
        exit();
    }
}

// Helper: check allowed email domain
function is_allowed_email($email) {
    $allowed_domain = '@adoc.superate.org.sv';
    return strtolower(substr($email, -strlen($allowed_domain))) === strtolower($allowed_domain);
}

// Helper: validate names (allow letters from any language, spaces, apostrophes, hyphens)
function is_valid_name($name) {
    return preg_match("/^[\p{L}\s'\-]+$/u", $name);
}

// Helper: teacher email format validation (name.surname@adoc.superate.org.sv)
function is_teacher_email_format($email) {
    return (bool)preg_match('/^[a-zA-ZÀ-ÿ]+(?:[.\-][a-zA-ZÀ-ÿ]+)+@adoc\.superate\.org\.sv$/i', $email);
}

// ========================================
// PROCESS POST (login / signup normal flow) - AJAX RESPONSE
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // If this is an AJAX request to check teacher email existence/availability:
    if ($action === 'check_teacher_email') {
        header('Content-Type: application/json; charset=utf-8');
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            echo json_encode(['ok' => false, 'reason' => 'empty', 'message' => 'Email vacío.']);
            exit;
        }
        if (!is_allowed_email($email)) {
            echo json_encode(['ok' => false, 'reason' => 'domain', 'message' => 'Only @adoc.superate.org.sv emails are allowed.']);
            exit;
        }
        if (!is_teacher_email_format($email)) {
            echo json_encode(['ok' => false, 'reason' => 'format', 'message' => 'Teacher email must be in the format name.surname@adoc.superate.org.sv']);
            exit;
        }
        // check allowed_teachers
        $stmt = $conn->prepare("SELECT id, email FROM allowed_teachers WHERE email = ?");
        if (!$stmt) {
            echo json_encode(['ok'=>false,'reason'=>'dberror','message'=>'Database error (prepare).']);
            exit;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            echo json_encode(['ok' => false, 'reason' => 'not_allowed', 'message' => 'This email is not registered as a teacher in the platform.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // check not already registered as user
        $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt2) {
            echo json_encode(['ok'=>false,'reason'=>'dberror','message'=>'Database error (prepare 2).']);
            exit;
        }
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $stmt2->store_result();
        if ($stmt2->num_rows > 0) {
            echo json_encode(['ok' => false, 'reason' => 'already_registered', 'message' => 'This email already has an account.']);
            $stmt2->close();
            exit;
        }
        $stmt2->close();

        echo json_encode(['ok' => true, 'message' => 'Email is allowed and available.']);
        exit;
    }

    // -----------------------------------------------------------
    // LOGIN (AJAX Response)
    // -----------------------------------------------------------
    if ($action === 'login') {
        header('Content-Type: application/json; charset=utf-8');
        
        $email = trim($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        $remember = isset($_POST['remember']);
        $redirect = $_POST['redirect'] ?? ''; // Get redirect parameter

        if ($email === '' || $password === '') {
            echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
            exit;
        } elseif (!is_allowed_email($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Only @adoc.superate.org.sv emails are allowed.']);
            exit;
        } else {
            // Query DB for user
            $stmt = $conn->prepare("SELECT id, fullname, lastnames, email, class_of, class_code, password_hash, role, remember_token FROM users WHERE email = ?");
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Database error.']);
                exit;
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Credentials do not match our records.']);
                } else {
                    $user = $result->fetch_assoc();

                    if (!password_verify($password, $user['password_hash'])) {
                        echo json_encode(['status' => 'error', 'message' => 'Credentials do not match our records.']);
                    } else {
                        // Remove password_hash before storing in session
                        unset($user['password_hash']);
                        
                        // Generate remember token if remember me is checked
                        if ($remember) {
                            $remember_token = bin2hex(random_bytes(32));
                            // Update user with new remember token
                            $updateStmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $updateStmt->bind_param("si", $remember_token, $user['id']);
                            $updateStmt->execute();
                            $updateStmt->close();
                            
                            // Set cookie for 30 days
                            setcookie('remember_user', $remember_token, time() + 30 * 24 * 60 * 60, "/");
                            $user['remember_token'] = $remember_token;
                        } else {
                            // For session-only login, set temporary cookie that expires when browser closes
                            $session_token = bin2hex(random_bytes(16));
                            setcookie('session_user', $session_token, 0, "/");
                            // Store in session for validation
                            $_SESSION['session_token'] = $session_token;
                        }
                        
                        $_SESSION['logged_in_user'] = $user;
                        $_SESSION['last_activity'] = time();
                        
                        // Determine redirect URL - SIEMPRE homepage cuando el redirect es homepage
                        $redirectUrl = '';
                        
                        if ($redirect === 'homepage') {
                            // SIEMPRE redirigir a homepage cuando el parámetro es homepage
                            $redirectUrl = 'homepage.php';
                        } elseif ($redirect === 'evaluation') {
                            // For evaluation redirect
                            if ($user['role'] === 'teacher') {
                                $redirectUrl = 'admin/admin.php';
                            } else {
                                $redirectUrl = 'codepage.php';
                            }
                        } else {
                            // Default redirect based on role
                            if ($user['role'] === 'teacher') {
                                $redirectUrl = 'admin/admin.php';
                            } else {
                                $redirectUrl = 'welcome.php';
                            }
                        }
                        
                        echo json_encode([
                            'status' => 'success', 
                            'message' => 'Logged in successfully!',
                            'user_role' => $user['role'],
                            'user_class' => $user['class_of'] ?? '',
                            'redirect' => $redirectUrl
                        ]);
                    }
                }
                $stmt->close();
            }
        }
        exit;
    }

    // -----------------------------------------------------------
    // SIGNUP (AJAX Response)
    // -----------------------------------------------------------
    if ($action === 'signup') {
        header('Content-Type: application/json; charset=utf-8');
        
        $fullname = trim($_POST['fullname'] ?? '');
        $lastnames = trim($_POST['lastnames'] ?? '');
        $email = trim($_POST['signup_email'] ?? '');
        $class_of = $_POST['class_of'] ?? 'first';
        $class_code = trim($_POST['class_code'] ?? '');
        $password = $_POST['signup_password'] ?? '';
        $password_confirm = $_POST['signup_password_confirm'] ?? '';
        $redirect = $_POST['redirect'] ?? ''; // Get redirect parameter

        // basic validations
        if ($fullname === '' || $lastnames === '' || $email === '' || $password === '' || $password_confirm === '') {
            echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
            exit;
        } elseif (!is_valid_name($fullname) || !is_valid_name($lastnames)) {
            echo json_encode(['status' => 'error', 'message' => 'Names must not contain numbers or symbols (accented letters and spaces are allowed).']);
            exit;
        } elseif (!is_allowed_email($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Only @adoc.superate.org.sv emails are allowed.']);
            exit;
        } elseif ($password !== $password_confirm) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        } elseif ($class_of === 'third' && $class_code === '') {
            echo json_encode(['status' => 'error', 'message' => 'Class code is required for third-year students.']);
            exit;
        } else {
            // Additional checks for teacher role
            if ($class_of === 'teacher') {
                // validate teacher email format
                if (!is_teacher_email_format($email)) {
                    echo json_encode(['status' => 'error', 'message' => 'Teacher email must be in the format name.surname@adoc.superate.org.sv']);
                    exit;
                }

                // check allowed_teachers table
                $stmtT = $conn->prepare("SELECT id, email FROM allowed_teachers WHERE email = ?");
                if (!$stmtT) {
                    echo json_encode(['status' => 'error', 'message' => 'Database error (allowed_teachers check).']);
                    exit;
                }
                $stmtT->bind_param("s", $email);
                $stmtT->execute();
                $stmtT->store_result();
                if ($stmtT->num_rows === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This email is not registered as a teacher in the platform.']);
                    $stmtT->close();
                    exit;
                }
                $stmtT->close();

                // check not already registered as user
                $stmtU = $conn->prepare("SELECT id FROM users WHERE email = ?");
                if (!$stmtU) {
                    echo json_encode(['status' => 'error', 'message' => 'Database error (user check).']);
                    exit;
                }
                $stmtU->bind_param("s", $email);
                $stmtU->execute();
                $stmtU->store_result();
                if ($stmtU->num_rows > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This teacher email already has an account.']);
                    $stmtU->close();
                    exit;
                }
                $stmtU->close();
                $role = 'teacher';
            } else {
                // For non-teachers, we still ensure email unique
                $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'The email is already registered.']);
                    $check->close();
                    exit;
                }
                $check->close();
                $role = 'student';
            }

            // Insert user into users table
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $remember_token = null; // No remember me on signup by default

            $insert = $conn->prepare("
                INSERT INTO users (fullname, lastnames, email, class_of, class_code, password_hash, role, remember_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$insert) {
                echo json_encode(['status' => 'error', 'message' => 'Database error (insert prepare).']);
                exit;
            }
            
            $class_code_insert = ($class_code === '') ? null : $class_code;
            $insert->bind_param("ssssssss", $fullname, $lastnames, $email, $class_of, $class_code_insert, $password_hash, $role, $remember_token);

            if ($insert->execute()) {
                $userId = $conn->insert_id;
                $_SESSION['logged_in_user'] = [
                    'id' => $userId,
                    'fullname' => $fullname,
                    'lastnames' => $lastnames,
                    'email' => $email,
                    'class_of' => $class_of,
                    'class_code' => $class_code_insert,
                    'role' => $role
                ];
                $_SESSION['last_activity'] = time();
                
                // Set session cookie (not remember cookie)
                $session_token = bin2hex(random_bytes(16));
                setcookie('session_user', $session_token, 0, "/");
                $_SESSION['session_token'] = $session_token;

                // Determine redirect URL - SIEMPRE homepage cuando el redirect es homepage
                $redirectUrl = '';
                
                if ($redirect === 'homepage') {
                    // SIEMPRE redirigir a homepage cuando el parámetro es homepage
                    $redirectUrl = 'homepage.php';
                } elseif ($redirect === 'evaluation') {
                    // For evaluation redirect
                    if ($role === 'teacher') {
                        $redirectUrl = 'admin/admin.php';
                    } else {
                        $redirectUrl = 'codepage.php';
                    }
                } else {
                    // Default redirect based on role
                    if ($role === 'teacher') {
                        $redirectUrl = 'admin/admin.php';
                    } else {
                        $redirectUrl = 'welcome.php';
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account created successfully!',
                    'user_role' => $role,
                    'user_class' => $class_of,
                    'redirect' => $redirectUrl
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error creating account.']);
            }
            $insert->close();
        }
        exit;
    }
}

// Check for logout request
if (isset($_GET['logout'])) {
    // Clear session using our helper function
    logoutUser();
    
    // Redirect to login
    header('Location: auth.php');
    exit();
}

// Get redirect parameter from URL
$redirectParam = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$modeParam = isset($_GET['mode']) ? $_GET['mode'] : '';

// For non-AJAX requests, preserve form data
$preserve = [
    'fullname' => $_POST['fullname'] ?? '',
    'lastnames' => $_POST['lastnames'] ?? '',
    'signup_email' => $_POST['signup_email'] ?? '',
    'login_email' => $_POST['login_email'] ?? '',
    'class_of' => $_POST['class_of'] ?? 'first',
    'class_code' => $_POST['class_code'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Sign Up - TOEIC Practice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E8F0FE;
        }
        
        .modal-title {
            font-size: 1.5rem;
            color: #003B95;
            margin: 0;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6B7280;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #003B95;
        }
        
        .modal-body {
            font-size: 1rem;
            color: #4B5563;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .modal-btn-primary {
            background: #0056D2;
            color: white;
        }
        
        .modal-btn-primary:hover {
            background: #003B95;
            transform: translateY(-1px);
        }
        
        .modal-btn-secondary {
            background: #E8F0FE;
            color: #003B95;
        }
        
        .modal-btn-secondary:hover {
            background: #D0E0FD;
        }
        
        .success-icon {
            color: #10B981;
            font-size: 3rem;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .error-icon {
            color: #DC2626;
            font-size: 3rem;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .modal-message {
            text-align: center;
            font-size: 1.1rem;
            color: #1F2937;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<header class="main-header">
    <img src="img/ets.png" alt="TOEIC Logo" class="toeic-logo">
</header>

<main class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-tabs" role="tablist" aria-label="Login and Sign up tabs">
            <button id="tab-login" class="tab active" data-target="login-form">Login</button>
            <button id="tab-signup" class="tab" data-target="signup-form">Sign Up</button>
        </div>

        <div class="forms">

            <!-- LOGIN FORM -->
            <form id="login-form" class="form active" method="POST" novalidate>
                <input type="hidden" name="action" value="login">
                <?php if ($modeParam === 'evaluation'): ?>
                <input type="hidden" name="redirect" value="evaluation">
                <?php endif; ?>
                <?php if ($modeParam === 'homepage'): ?>
                <input type="hidden" name="redirect" value="homepage">
                <?php endif; ?>
                <?php if ($redirectParam === 'homepage'): ?>
                <input type="hidden" name="redirect" value="homepage">
                <?php endif; ?>
                
                <label class="field">
                    <span class="label-text"><i class="bi bi-envelope"></i> Email</span>
                    <input type="email" name="login_email" id="login_email"
                           placeholder="you@adoc.superate.org.sv"
                           required value="<?php echo htmlspecialchars($preserve['login_email']); ?>">
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-lock"></i> Password</span>
                    <div class="password-wrapper">
                        <input type="password" name="login_password" id="login_password" placeholder="Enter password" required>
                        <button type="button" class="eye-toggle" data-target="login_password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>

                <label class="checkbox">
                    <input type="checkbox" name="remember" id="remember" checked>
                    <span>Remember me</span>
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Login</button>
                    <a href="#" class="forgot-link" id="forgot-link">Forgot password?</a>
                </div>

                <p class="small-note">This data will help save your progress on the platform and is end-to-end encrypted.</p>
            </form>

            <!-- SIGNUP FORM -->
            <form id="signup-form" class="form" method="POST" novalidate>
                <input type="hidden" name="action" value="signup">
                <?php if ($modeParam === 'evaluation'): ?>
                <input type="hidden" name="redirect" value="evaluation">
                <?php endif; ?>
                <?php if ($modeParam === 'homepage'): ?>
                <input type="hidden" name="redirect" value="homepage">
                <?php endif; ?>
                <?php if ($redirectParam === 'homepage'): ?>
                <input type="hidden" name="redirect" value="homepage">
                <?php endif; ?>

                <label class="field">
                    <span class="label-text"><i class="bi bi-person"></i> First and Middle Names</span>
                    <input type="text" name="fullname" id="fullname" placeholder="First and middle names"
                           required value="<?php echo htmlspecialchars($preserve['fullname']); ?>">
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-person-badge"></i> Last names</span>
                    <input type="text" name="lastnames" id="lastnames" placeholder="Two last names" required
                           value="<?php echo htmlspecialchars($preserve['lastnames']); ?>">
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-envelope"></i> Email</span>
                    <input type="email" name="signup_email" id="signup_email" placeholder="you@adoc.superate.org.sv"
                           required value="<?php echo htmlspecialchars($preserve['signup_email']); ?>">
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-people"></i> Class of</span>
                    <select name="class_of" id="class_of">
                        <option value="first" <?php echo ($preserve['class_of'] === 'first') ? 'selected' : ''; ?>>First year</option>
                        <option value="second" <?php echo ($preserve['class_of'] === 'second') ? 'selected' : ''; ?>>Second year</option>
                        <option value="third" <?php echo ($preserve['class_of'] === 'third') ? 'selected' : ''; ?>>Third year</option>
                        <option value="teacher" <?php echo ($preserve['class_of'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    </select>
                </label>

                <label class="field class-code-field" id="class_code_label" style="display: <?php echo ($preserve['class_of'] === 'third') ? 'block' : 'none'; ?>;">
                    <span class="label-text"><i class="bi bi-key"></i> Class Code</span>
                    <input type="text" name="class_code" id="class_code" placeholder="Enter class code"
                           value="<?php echo htmlspecialchars($preserve['class_code']); ?>">
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-lock"></i> Password</span>
                    <div class="password-wrapper">
                        <input type="password" name="signup_password" id="signup_password" placeholder="Create password" required>
                        <button type="button" class="eye-toggle" data-target="signup_password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>

                <label class="field">
                    <span class="label-text"><i class="bi bi-lock-fill"></i> Confirm Password</span>
                    <div class="password-wrapper">
                        <input type="password" name="signup_password_confirm" id="signup_password_confirm" placeholder="Confirm password" required>
                        <button type="button" class="eye-toggle" data-target="signup_password_confirm" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Create Account</button>
                </div>

                <p class="small-note">This data will help save your progress on the platform and is end-to-end encrypted.</p>
            </form>

        </div>
    </div>
</main>

<!-- Modal for success/error messages -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Success</h3>
            <button class="modal-close" id="modalCloseSuccess">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="success-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <p class="modal-message" id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-primary" id="modalActionBtn">Continue</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Error</h3>
            <button class="modal-close" id="modalCloseError">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="error-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <p class="modal-message" id="errorModalMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" id="modalCloseBtn">Close</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay" hidden>
    <div class="spinner"></div>
    <p id="loading-text">Processing...</p>
</div>

<script src="auth.js"></script>
</body>
</html>