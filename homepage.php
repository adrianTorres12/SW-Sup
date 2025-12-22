<?php
// homepage.php
require_once 'session_helper.php';
startSecureSession();

// Verificar que el usuario esté logueado
if (!isUserLoggedIn()) {
    header('Location: auth.php?mode=homepage');
    exit();
}

$user_role = getUserRole();
$user_class = getUserClass();
$user_fullname = getUserFullName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TOEIC Practice Homepage</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="homepage.css">
  <style>
    /* Estilos adicionales para el header con logout */
    .main-header {
      background-color: #003B95;
      padding: 15px 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 15px rgba(0, 59, 149, 0.1);
    }
    
    .main-header h1 {
      color: white;
      font-size: 1.5rem;
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
    
    .user-info i {
      font-size: 1.1rem;
    }
    
    .user-name {
      font-size: 0.95rem;
      font-weight: 500;
    }
    
    /* Badges para mostrar el rol */
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
    
    /* Mensaje de bienvenida */
    .welcome-message {
      text-align: center;
      padding: 20px;
      background-color: #E8F0FE;
      border-radius: 10px;
      margin: 20px auto;
      max-width: 800px;
    }
    
    /* Contenedor de botones de acción para teachers */
    .teacher-actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin: 30px 0;
      flex-wrap: wrap;
    }
    
    .action-btn {
      background: #0056D2;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    
    .action-btn:hover {
      background: #003B95;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .action-btn.secondary {
      background: #6c757d;
    }
    
    .action-btn.secondary:hover {
      background: #545b62;
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
      
      .teacher-actions {
        flex-direction: column;
        align-items: center;
      }
      
      .action-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
      }
    }
    
    @media (max-width: 480px) {
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
    <h1>TOEIC Speaking & Writing Practice</h1>
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

  <main>
    <!-- MENSAJE DE BIENVENIDA -->
    <div class="welcome-message">
      <h2>Welcome, <?php echo htmlspecialchars(explode(' ', $user_fullname)[0]); ?>!</h2>
      <p>You are logged in as a 
        <strong><?php echo ucfirst($user_role); ?></strong>
        <?php if ($user_class): ?>
          (<strong><?php echo $user_class; ?> year</strong>)
        <?php endif; ?>
      </p>
      
      <?php if ($user_role === 'teacher'): ?>
      <p>As a teacher, you have access to the admin panel where you can create evaluation tests, manage class codes, and review student submissions.</p>
      <?php elseif ($user_role === 'student' && $user_class === 'third'): ?>
      <p>As a third-year student, you have access to Evaluation Practice where you can submit responses for teacher evaluation.</p>
      <?php else: ?>
      <p>You can practice TOEIC Speaking and Writing on your own. Your progress will be saved to your account.</p>
      <?php endif; ?>
    </div>
    
    <!-- ACCIONES ESPECIALES PARA TEACHERS -->
    <?php if ($user_role === 'teacher'): ?>
    <div class="teacher-actions">
      <a href="admin/admin.php" class="action-btn">
        <i class="bi bi-speedometer2"></i> Go to Admin Panel
      </a>
    </div>
    <?php endif; ?>
    
    <!-- PARA ESTUDIANTES DE TERCER AÑO (EVALUATION PRACTICE) -->
    <?php if ($user_role === 'student' && $user_class === 'third'): ?>
    <div class="teacher-actions">
      <a href="codepage.php" class="action-btn">
        <i class="bi bi-clipboard-check"></i> Enter Evaluation Test Code
      </a>
      <a href="evaluation/tests.php" class="action-btn secondary">
        <i class="bi bi-list-check"></i> View My Evaluation Tests
      </a>
    </div>
    <?php endif; ?>

    <!-- ========================= SPEAKING ========================= -->
    <section class="category">
      <h2>Speaking Practice</h2>
      <div class="card-container">
        <a href="#" class="card">
          <div class="card-img"><i class="bi bi-mic"></i></div>
          <h3>Read a Text Aloud</h3>
        </a>
        <a href="#" class="card">
          <div class="card-img"><i class="bi bi-camera"></i></div>
          <h3>Describe a Picture</h3>
        </a>
        <a href="#" class="card">
          <div class="card-img"><i class="bi bi-chat"></i></div>
          <h3>Respond to Questions</h3>
        </a>
        <a href="#" class="card">
          <div class="card-img"><i class="bi bi-telephone"></i></div>
          <h3>Respond Using Information Provided</h3>
        </a>
        <a href="#" class="card">
          <div class="card-img"><i class="bi bi-pencil-square"></i></div>
          <h3>Express an Opinion</h3>
        </a>
      </div>
    </section>

    <!-- ========================= WRITING ========================= -->
    <section class="category">
      <h2>Writing Practice</h2>
      <div class="card-container">

        <!-- PART 1 -->
        <a href="./writing/practice_on_my_own/practice-writing-list.php?part=1" class="card">
          <div class="card-img"><i class="bi bi-image"></i></div>
          <h3>Write a Sentence Based on a Picture</h3>
        </a>

        <!-- PART 2 -->
        <a href="./writing/practice_on_my_own/practice-writing-list.php?part=2" class="card">
          <div class="card-img"><i class="bi bi-envelope-open"></i></div>
          <h3>Respond to a Written Request</h3>
        </a>

        <!-- PART 3 -->
        <a href="./writing/practice_on_my_own/practice-writing-list.php?part=3" class="card">
          <div class="card-img"><i class="bi bi-journal-text"></i></div>
          <h3>Write an Opinion Essay</h3>
        </a>

      </div>
    </section>

    <!-- ========================= FULL PRACTICES ========================= -->
    <section class="category">
      <h2>Full Practices</h2>
      <div class="card-container full-grid">
        <a href="./writing/practice_on_my_own/practice-writing-list.php?part=full" class="card">
          <div class="card-img"><i class="bi bi-file-earmark-text"></i></div>
          <h3>TOEIC Speaking and Writing</h3>
        </a>
      </div>
    </section>
  </main>

  <script>
    // Función para mostrar información del usuario
    function showUserInfo() {
      alert('User Information:\n\nName: <?php echo addslashes($user_fullname); ?>\nRole: <?php echo ucfirst($user_role); ?>\n<?php if ($user_class): ?>Class: <?php echo $user_class; ?> year<?php endif; ?>');
    }
    
    // Función para manejar logout
    function handleLogout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'welcome.php?logout=1';
      }
    }
    
    // Detectar si el usuario es teacher y mostrar mensaje especial
    <?php if ($user_role === 'teacher'): ?>
    console.log('Teacher account detected - showing admin options');
    <?php elseif ($user_role === 'student' && $user_class === 'third'): ?>
    console.log('Third-year student detected - showing evaluation options');
    <?php endif; ?>
  </script>
</body>
</html>