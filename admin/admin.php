<?php
// admin.php
// Administration panel (single-file) for managing users, teachers list, class codes and tests.
// Expects:
// - db.php       -> creates $conn (connection for superate_platform)
// - db_connection.php -> creates $conn (connection for practice_writing)
// We capture both into $superate_conn and $tests_conn.

session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['logged_in_user']) || $_SESSION['logged_in_user']['role'] !== 'teacher') {
    header('Location: ../auth.php?redirect=admin');
    exit();
}

// Check for logout request
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Clear the remember_token from database if it exists
    if (isset($_SESSION['logged_in_user']['id']) && isset($_COOKIE['remember_user'])) {
        require_once '../db.php';
        $remember_token = $_COOKIE['remember_user'];
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ? AND remember_token = ?");
        $stmt->bind_param("is", $_SESSION['logged_in_user']['id'], $remember_token);
        $stmt->execute();
        $stmt->close();
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear all authentication cookies
    setcookie('remember_user', '', time() - 3600, "/");
    setcookie('session_user', '', time() - 3600, "/");
    
    // Redirect to welcome page
    header('Location: ../welcome.php');
    exit();
}

// Get current teacher email
$teacher_email = $_SESSION['logged_in_user']['email'] ?? '';

// --- include connections and capture $conn variables ---
$superate_conn = null;
$tests_conn = null;
$errors = [];

// include superate_platform connection
if (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
    if (isset($conn) && $conn) {
        $superate_conn = $conn;
    } else {
        $errors[] = "Could not initialize connection from db.php (superate_platform).";
    }
} else {
    $errors[] = "db.php not found at ../db.php";
}

// include practice_writing connection
if (file_exists(__DIR__ . '/../writing/practice_on_my_own/db_connection.php')) {
    require_once __DIR__ . '/../writing/practice_on_my_own/db_connection.php';
    if (isset($conn) && $conn) {
        $tests_conn = $conn;
    } else {
        $errors[] = "Could not initialize connection from db_connection.php (practice_writing).";
    }
} else {
    $errors[] = "db_connection.php not found at ../writing/practice_on_my_own/db_connection.php";
}

// --- Create helper tables if superate_conn is available ---
if ($superate_conn) {
    // allowed_teachers: list of teacher emails that are allowed for teacher signup/verification
    $sql_create_allowed = "CREATE TABLE IF NOT EXISTS allowed_teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $superate_conn->query($sql_create_allowed);

    // class_codes: codes for classes (teacher creates)
    $sql_create_codes = "CREATE TABLE IF NOT EXISTS class_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(13) NOT NULL UNIQUE,
        name VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $superate_conn->query($sql_create_codes);
}

// --- Handle POST actions (CRUD for allowed_teachers and class_codes) ---
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $superate_conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_allowed_teacher') {
        $email = trim(strtolower($_POST['teacher_email'] ?? ''));
        if ($email === '') {
            $flash = ['type'=>'error','text'=>'Provide an email for teacher.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type'=>'error','text'=>'Invalid email format.'];
        } else {
            $stmt = $superate_conn->prepare("INSERT INTO allowed_teachers (email) VALUES (?)");
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','text'=>'Allowed teacher added successfully.'];
            } else {
                $flash = ['type'=>'error','text'=>'Could not add teacher (email may already exist).'];
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_allowed_teacher') {
        $id = intval($_POST['teacher_id'] ?? 0);
        if ($id > 0) {
            $stmt = $superate_conn->prepare("DELETE FROM allowed_teachers WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','text'=>'Allowed teacher removed.'];
            } else {
                $flash = ['type'=>'error','text'=>'Could not delete allowed teacher.'];
            }
            $stmt->close();
        }
    }

    if ($action === 'add_class_code') {
        $code = trim($_POST['code_value'] ?? '');
        $name = trim($_POST['code_name'] ?? '');
        
        if ($code === '') {
            $flash = ['type'=>'error','text'=>'Provide a class code value.'];
        } elseif (strlen($code) > 13) {
            $flash = ['type'=>'error','text'=>'Class code must be maximum 13 characters.'];
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
            $flash = ['type'=>'error','text'=>'Class code must contain only letters and numbers.'];
        } else {
            $stmt = $superate_conn->prepare("INSERT INTO class_codes (code, name) VALUES (?, ?)");
            $stmt->bind_param('ss', $code, $name);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','text'=>'Class code created successfully.'];
            } else {
                $flash = ['type'=>'error','text'=>'Could not create class code (code may already exist).'];
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_class_code') {
        $id = intval($_POST['code_id'] ?? 0);
        if ($id > 0) {
            $stmt = $superate_conn->prepare("DELETE FROM class_codes WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $flash = ['type'=>'success','text'=>'Class code removed.'];
            } else {
                $flash = ['type'=>'error','text'=>'Could not delete class code.'];
            }
            $stmt->close();
        }
    }
}

// --- Fetch datasets for display ---
$users = [];
$students_third = [];
$allowed_teachers = [];
$class_codes = [];
$writing_tests = [];
$full_tests = [];

if ($superate_conn) {
    // users (all)
    $res = $superate_conn->query("SELECT id, fullname, lastnames, email, class_of, class_code, role, created_at FROM users ORDER BY created_at DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $users[] = $r;
    }

    // third-year students
    $res2 = $superate_conn->query("SELECT id, fullname, lastnames, email, class_code, created_at FROM users WHERE class_of = 'third' ORDER BY fullname ASC");
    if ($res2) {
        while ($r = $res2->fetch_assoc()) $students_third[] = $r;
    }

    // allowed teachers
    $res3 = $superate_conn->query("SELECT id, email, created_at FROM allowed_teachers ORDER BY created_at DESC");
    if ($res3) {
        while ($r = $res3->fetch_assoc()) $allowed_teachers[] = $r;
    }

    // class codes
    $res4 = $superate_conn->query("SELECT id, code, name, created_at FROM class_codes ORDER BY created_at DESC");
    if ($res4) {
        while ($r = $res4->fetch_assoc()) $class_codes[] = $r;
    }
}

// writing tests from practice_writing.writing_tests
if ($tests_conn) {
    $sql = "SELECT id, test_number, part, title, created_at FROM writing_tests ORDER BY test_number ASC, part ASC";
    $resT = $tests_conn->query($sql);
    if ($resT) {
        while ($r = $resT->fetch_assoc()) $writing_tests[] = $r;
    }
    
    // full tests aggregation
    $sql = "SELECT test_number, GROUP_CONCAT(part ORDER BY part) as parts, MAX(title) as title FROM writing_tests GROUP BY test_number ORDER BY test_number DESC";
    $resF = $tests_conn->query($sql);
    if ($resF) {
        while ($r = $resF->fetch_assoc()) $full_tests[] = $r;
    }
}

// Get unique class codes for filter
$unique_class_codes = [];
foreach ($students_third as $student) {
    if ($student['class_code'] && !in_array($student['class_code'], $unique_class_codes)) {
        $unique_class_codes[] = $student['class_code'];
    }
}
sort($unique_class_codes);

// helper to safely escape for HTML
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel — TOEIC Practice</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <style>
    /* Confirmation Modal for Logout */
    #logoutModal .modal-content {
      max-width: 400px;
    }
    
    .logout-confirm-text {
      text-align: center;
      font-size: 1.1rem;
      margin-bottom: 20px;
      color: #4B5563;
    }
    
    .logout-icon {
      font-size: 3rem;
      color: #DC2626;
      text-align: center;
      margin-bottom: 15px;
    }
    
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding-top: 20px;
      border-top: 1px solid #E5E7EB;
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
    
    .modal-btn-danger {
      background: #DC2626;
      color: white;
    }
    
    .modal-btn-danger:hover {
      background: #B91C1C;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>
  <header>
    <div class="header-left">
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
      </button>
      <img class="toeic-logo" src="../img/ets.webp" alt="TOEIC Logo">
      <div class="header-title">Admin Panel</div>
    </div>
    
    <div class="header-right">
      <div class="user-info">
        <i class="bi bi-person-circle"></i>
        <span class="user-email"><?php echo h($teacher_email); ?></span>
      </div>
      <button class="logout-btn" id="logoutBtn" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
        <span class="logout-text">Logout</span>
      </button>
    </div>
  </header>

  <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h3>Navigation</h3>
      <button class="sidebar-close" id="sidebarClose">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="nav-items">
      <div class="nav-item" onclick="window.location.href='../welcome.php'">
        <i class="bi bi-card-list"></i>
        <span>Welcome Page</span>
      </div>
      <div class="nav-item active" data-panel="panel-users">
        <i class="bi bi-people"></i>
        <span>Users</span>
      </div>
      <div class="nav-item" data-panel="panel-students">
        <i class="bi bi-mortarboard"></i>
        <span>Students (3rd year)</span>
      </div>
      <div class="nav-item" data-panel="panel-teachers">
        <i class="bi bi-person-badge"></i>
        <span>Allowed Teachers</span>
      </div>
      <div class="nav-item" data-panel="panel-codes">
        <i class="bi bi-key"></i>
        <span>Class Codes</span>
      </div>
      <div class="nav-item" data-panel="panel-writing">
        <i class="bi bi-pencil"></i>
        <span>Writing Tests</span>
      </div>
      <div class="nav-item" data-panel="panel-speaking">
        <i class="bi bi-megaphone"></i>
        <span>Speaking Tests</span>
      </div>
      <div class="nav-item" data-panel="panel-fulltests">
        <i class="bi bi-card-list"></i>
        <span>Full Tests</span>
      </div>
      <div class="nav-item upload-link" onclick="window.location.href='../writing/practice_on_my_own/upload-writing-test.php'">
        <i class="bi bi-upload"></i>
        <span>Upload Tests</span>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <section class="content" id="main-content">
        <?php if (!empty($errors)): ?>
          <div class="flash error"><?php echo h(implode(' • ',$errors)); ?></div>
        <?php endif; ?>

        <?php if ($flash): ?>
          <div class="flash <?php echo $flash['type']==='success' ? 'success':'error'; ?>"><?php echo h($flash['text']); ?></div>
        <?php endif; ?>

        <!-- PANEL: USERS -->
        <div id="panel-users" class="panel active-panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-people"></i> All Users</h2>
              <div class="panel-subtitle">Manage all registered users</div>
            </div>
            <div class="panel-actions">
              <div class="search">
                <input id="users-search" placeholder="Search users by name or email...">
                <i class="bi bi-search"></i>
              </div>
              <select id="users-filter" class="filter">
                <option value="">All years</option>
                <option value="first">First year</option>
                <option value="second">Second year</option>
                <option value="third">Third year</option>
                <option value="teacher">Teacher</option>
              </select>
            </div>
          </div>

          <div class="table-container">
            <table class="table" id="users-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Class</th>
                  <th>Role</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($users as $u): ?>
                  <tr data-name="<?php echo h(strtolower($u['fullname'].' '.$u['lastnames'])); ?>" data-email="<?php echo h(strtolower($u['email'])); ?>" data-class="<?php echo h($u['class_of']); ?>">
                    <td><?php echo h($u['fullname'].' '.$u['lastnames']); ?></td>
                    <td class="email-cell"><?php echo h($u['email']); ?></td>
                    <td>
                      <?php echo h(ucfirst($u['class_of'])); ?>
                      <?php if($u['class_code']): ?>
                        <span class="chip"><?php echo h($u['class_code']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td><span class="role-badge role-<?php echo h($u['role']); ?>"><?php echo h($u['role']); ?></span></td>
                    <td class="date-cell"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    <td class="actions-cell">
                      <?php if (($u['class_of'] ?? '') === 'third'): ?>
                        <button class="btn btn-view" onclick="openStudentTests(<?php echo (int)$u['id']; ?>)">View Tests</button>
                      <?php else: ?>
                        <span class="muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PANEL: STUDENTS (3rd year) -->
        <div id="panel-students" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-mortarboard"></i> Third Year Students</h2>
              <div class="panel-subtitle">Manage evaluation access for third year students</div>
            </div>
            <div class="panel-actions">
              <div class="search">
                <input id="students-search" placeholder="Search student by name...">
                <i class="bi bi-search"></i>
              </div>
              <select id="students-class-filter" class="filter">
                <option value="">All Classes</option>
                <?php foreach($unique_class_codes as $code): ?>
                  <option value="<?php echo h($code); ?>"><?php echo h($code); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="table-container">
            <table class="table" id="students-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Class Code</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($students_third as $s): ?>
                  <tr data-name="<?php echo h(strtolower($s['fullname'].' '.$s['lastnames'])); ?>" data-class="<?php echo h($s['class_code']); ?>">
                    <td><?php echo h($s['fullname'].' '.$s['lastnames']); ?></td>
                    <td class="email-cell"><?php echo h($s['email']); ?></td>
                    <td>
                      <?php if($s['class_code']): ?>
                        <span class="chip"><?php echo h($s['class_code']); ?></span>
                      <?php else: ?>
                        <span class="muted">Not assigned</span>
                      <?php endif; ?>
                    </td>
                    <td class="date-cell"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                    <td class="actions-cell">
                      <button class="btn btn-view" onclick="showModal('Coming Soon', 'Student test review feature will be available soon.')">Review Tests</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PANEL: ALLOWED TEACHERS -->
        <div id="panel-teachers" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-person-badge"></i> Allowed Teachers</h2>
              <div class="panel-subtitle">Manage teacher email addresses allowed to register</div>
            </div>
          </div>

          <div class="form-card">
            <h3><i class="bi bi-plus-circle"></i> Add New Teacher</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="teacher-email">Teacher Email</label>
                <input type="email" id="teacher-email" placeholder="teacher@adoc.superate.org.sv" class="form-input">
                <div class="form-hint">Must be @adoc.superate.org.sv domain</div>
              </div>
              <button class="btn btn-primary" id="add-teacher">
                <i class="bi bi-plus-lg"></i> Add Teacher
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="table" id="allowed-teachers-table">
              <thead>
                <tr>
                  <th>Email</th>
                  <th>Added On</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($allowed_teachers)): ?>
                  <tr>
                    <td colspan="3" class="empty-state">
                      <i class="bi bi-person-x"></i>
                      <div>No allowed teachers found</div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach($allowed_teachers as $t): ?>
                    <tr>
                      <td class="email-cell"><?php echo h($t['email']); ?></td>
                      <td class="date-cell"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                      <td class="actions-cell">
                        <button class="btn btn-danger btn-icon" data-id="<?php echo (int)$t['id']; ?>" onclick="confirmDelete('teacher', <?php echo (int)$t['id']; ?>, '<?php echo h($t['email']); ?>')" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PANEL: CLASS CODES -->
        <div id="panel-codes" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-key"></i> Class Codes</h2>
              <div class="panel-subtitle">Create and manage class codes for student grouping</div>
            </div>
          </div>

          <div class="form-card">
            <h3><i class="bi bi-plus-circle"></i> Create New Class Code</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="code-value">Class Code</label>
                <input type="text" id="code-value" placeholder="morning2025" class="form-input" maxlength="13">
                <div class="form-hint">Max 13 characters, letters and numbers only</div>
              </div>
              <div class="form-group">
                <label for="code-name">Class Name (Optional)</label>
                <input type="text" id="code-name" placeholder="Morning Shift 2025" class="form-input">
              </div>
              <button class="btn btn-primary" id="add-code">
                <i class="bi bi-plus-lg"></i> Create Code
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="table" id="codes-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Created On</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($class_codes)): ?>
                  <tr>
                    <td colspan="4" class="empty-state">
                      <i class="bi bi-key"></i>
                      <div>No class codes found</div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach($class_codes as $c): ?>
                    <tr>
                      <td><span class="code-badge"><?php echo h($c['code']); ?></span></td>
                      <td><?php echo h($c['name']); ?></td>
                      <td class="date-cell"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                      <td class="actions-cell">
                        <button class="btn btn-danger btn-icon" data-id="<?php echo (int)$c['id']; ?>" onclick="confirmDelete('code', <?php echo (int)$c['id']; ?>, '<?php echo h($c['code']); ?>')" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PANEL: WRITING TESTS -->
        <div id="panel-writing" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-pencil"></i> Writing Tests</h2>
              <div class="panel-subtitle">Manage individual writing tests</div>
            </div>
            <div class="panel-actions">
              <div class="search">
                <input id="writing-search" placeholder="Search tests...">
                <i class="bi bi-search"></i>
              </div>
            </div>
          </div>

          <div class="table-container">
            <table class="table" id="writing-table">
              <thead>
                <tr>
                  <th>Test #</th>
                  <th>Part</th>
                  <th>Title</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($writing_tests)): ?>
                  <tr>
                    <td colspan="5" class="empty-state">
                      <i class="bi bi-journal-x"></i>
                      <div>No writing tests found</div>
                      <a href="../writing/practice_on_my_own/upload-writing-test.php" class="btn btn-primary">Upload Tests</a>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach($writing_tests as $wt): ?>
                    <tr>
                      <td><span class="test-number"><?php echo h($wt['test_number']); ?></span></td>
                      <td><span class="part-badge">Part <?php echo h($wt['part']); ?></span></td>
                      <td><?php echo h($wt['title']); ?></td>
                      <td class="date-cell"><?php echo date('M d, Y', strtotime($wt['created_at'])); ?></td>
                      <td class="actions-cell">
                        <button class="btn btn-view" onclick="showModal('Test Viewer', 'Test viewer feature will be available soon.')">View</button>
                        <button class="btn btn-danger" onclick="showModal('Delete Test', 'Test deletion feature will be available soon.')">Delete</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PANEL: SPEAKING TESTS -->
        <div id="panel-speaking" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-megaphone"></i> Speaking Tests</h2>
              <div class="panel-subtitle">Coming soon - Speaking practice items</div>
            </div>
          </div>
          <div class="empty-state">
            <i class="bi bi-megaphone" style="font-size: 3rem; color: #E8F0FE;"></i>
            <h3>Speaking Tests Section</h3>
            <p>This section will be available in a future update.</p>
          </div>
        </div>

        <!-- PANEL: FULL TESTS -->
        <div id="panel-fulltests" class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <h2><i class="bi bi-card-list"></i> Full Tests</h2>
              <div class="panel-subtitle">Complete TOEIC Writing tests with all parts</div>
            </div>
            <div class="panel-actions">
              <button class="btn btn-primary" onclick="window.location.href='../writing/practice_on_my_own/upload-writing-test.php'">
                <i class="bi bi-upload"></i> Upload New Test
              </button>
            </div>
          </div>

          <?php if (!$tests_conn): ?>
            <div class="flash error">Tests database connection not available.</div>
          <?php else: ?>
            <?php if (empty($full_tests)): ?>
              <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <h3>No Full Tests Available</h3>
                <p>Upload writing tests to create full tests.</p>
                <a href="../writing/practice_on_my_own/upload-writing-test.php" class="btn btn-primary">Upload Tests</a>
              </div>
            <?php else: ?>
              <div class="card-grid" id="full-tests-grid">
                <?php foreach($full_tests as $ft): ?>
                  <?php
                    $parts = explode(',', $ft['parts']);
                    $title = 'Test ' . $ft['test_number'];
                  ?>
                  <div class="test-card" tabindex="0" role="button" onclick="openFullTest(<?php echo (int)$ft['test_number']; ?>)">
                    <div class="test-card-top">
                      <i class="bi bi-journal-text"></i>
                      <div class="test-parts">
                        <?php foreach($parts as $part): ?>
                          <span class="part-tag">P<?php echo h($part); ?></span>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="test-card-bottom">
                      <div class="test-title"><?php echo h($title); ?></div>
                      <div class="test-info"><?php echo count($parts); ?> parts available</div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      </section>
    </div>
  </main>

  <!-- Modals -->
  <div class="modal-overlay" id="modalOverlay"></div>
  
  <div class="modal" id="confirmationModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Deletion</h3>
        <button class="modal-close" onclick="closeModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="modal-body">
        <p id="confirmationMessage">Are you sure you want to delete this item?</p>
      </div>
      <div class="modal-footer">
        <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="modal-btn modal-btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>

  <div class="modal" id="infoModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="infoModalTitle">Information</h3>
        <button class="modal-close" onclick="closeModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="modal-body">
        <p id="infoModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button class="modal-btn modal-btn-primary" onclick="closeModal()">OK</button>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal" id="logoutModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Confirm Logout</h3>
        <button class="modal-close" onclick="closeModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="logout-icon">
          <i class="bi bi-box-arrow-right"></i>
        </div>
        <p class="logout-confirm-text">Are you sure you want to logout?<br>You will need to sign in again to access the admin panel.</p>
      </div>
      <div class="modal-footer">
        <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="modal-btn modal-btn-danger" id="confirmLogoutBtn">Yes, Logout</button>
      </div>
    </div>
  </div>

  <!-- Hidden Form for Submissions -->
  <form id="hidden-post-form" method="POST" style="display:none;">
    <input type="hidden" name="action" id="hp_action">
    <input type="hidden" name="teacher_id" id="hp_teacher_id">
    <input type="hidden" name="code_id" id="hp_code_id">
    <input type="hidden" name="teacher_email" id="hp_teacher_email">
    <input type="hidden" name="code_value" id="hp_code_value">
    <input type="hidden" name="code_name" id="hp_code_name">
  </form>

  <script src="admin.js"></script>
</body>
</html>