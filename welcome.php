<?php
// welcome.php
require_once 'session_helper.php';
startSecureSession();

// Verificar si el usuario está logueado usando nuestra función helper
$is_logged_in = isUserLoggedIn();
$user_role = getUserRole();
$user_class = getUserClass();

// Manejar logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    if ($is_logged_in) {
        $logout_name = $_SESSION['logged_in_user']['fullname'] ?? '';
        logoutUser();
        header('Location: welcome.php?logout=success&name=' . urlencode($logout_name));
        exit();
    } else {
        header('Location: welcome.php');
        exit();
    }
}

// Datos del usuario para JS
$user_fullname = $is_logged_in ? htmlspecialchars(getUserFullName()) : '';
$user_email = $is_logged_in ? htmlspecialchars($_SESSION['logged_in_user']['email'] ?? '') : '';

// Verificar si hay un mensaje de logout exitoso
$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';
$logout_name = isset($_GET['name']) ? urldecode($_GET['name']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOEIC Welcome</title>
    <link rel="stylesheet" href="welcome.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Bootstrap 5 CSS para modales -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <!-- Modal de Confirmación de Logout -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-right text-primary me-2"></i>
                        Confirm Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout from the platform?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="welcome.php?logout=1" class="btn btn-primary">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Información del Usuario -->
    <div class="modal fade" id="userInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-circle text-primary me-2"></i>
                        Account Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($is_logged_in): ?>
                    <div class="user-info-details">
                        <div class="info-item mb-3">
                            <strong><i class="bi bi-person me-2"></i>Full Name:</strong>
                            <p class="mb-0"><?php echo $user_fullname; ?></p>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="bi bi-envelope me-2"></i>Email:</strong>
                            <p class="mb-0"><?php echo $user_email; ?></p>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="bi bi-person-badge me-2"></i>Role:</strong>
                            <p class="mb-0">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($user_role); ?></span>
                            </p>
                        </div>
                        <?php if ($user_class): ?>
                        <div class="info-item mb-3">
                            <strong><i class="bi bi-people me-2"></i>Class:</strong>
                            <p class="mb-0">
                                <span class="badge bg-info"><?php echo $user_class; ?> year</span>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center">You are not logged in.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Acceso Denegado -->
    <div class="modal fade" id="accessDeniedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Access Denied
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You don't have permission to access this feature.</p>
                    <p class="mb-0"><strong>Evaluation Practice</strong> is only available for third-year students with a valid class code.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Logout Exitoso (se muestra automáticamente si hay parámetro) -->
    <?php if ($logout_success && $logout_name): ?>
    <div class="modal fade" id="logoutSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>
                        Logout Successful
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-person-check display-4 text-success mb-3"></i>
                    <h5>Goodbye, <?php echo htmlspecialchars($logout_name); ?>!</h5>
                    <p class="mb-0">You have been successfully logged out.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="main-header">
        <img src="img/ets.png" alt="TOEIC Logo" class="toeic-logo">
        <?php if ($is_logged_in): ?>
        <div class="user-info">
            <button class="user-profile-btn" data-bs-toggle="modal" data-bs-target="#userInfoModal">
                <i class="bi bi-person-circle"></i>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['logged_in_user']['fullname'] ?? ''); ?></span>
            </button>
            <button class="logout-btn" onclick="handleLogout()">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
        <?php endif; ?>
    </header>

    <main class="content">

        <!-- TITLE -->
        <h1 class="welcome-title">Welcome to the TOEIC Speaking & Writing Practice Platform</h1>

        <!-- DESCRIPTION -->
        <p class="welcome-description">
            This platform was created so that all ¡Supérate! students can practice TOEIC Speaking and Writing 
            to prepare for their certification. It also serves as a tool that allows teachers to evaluate third-year 
            students and provide feedback based on their submitted responses.
        </p>

        <!-- CARDS CONTAINER -->
        <div class="cards-container">

            <!-- CARD 1 -->
            <div class="card practice-card" id="practiceCard">
                <i class="bi bi-person-workspace icon"></i>
                <h2 class="card-title">Practice on my own</h2>
                <p class="card-description">
                    Practice TOEIC Speaking and Writing without logging in. Your answers are saved temporarily in your browser.
                </p>
                <?php if ($is_logged_in): ?>
                <div class="card-hint">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>You're logged in as <?php echo htmlspecialchars($user_role); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- CARD 2 -->
            <div class="card evaluation-card" id="evaluationCard">
                <i class="bi bi-ui-checks-grid icon"></i>
                <h2 class="card-title">Evaluation Practice</h2>
                <p class="card-description">
                    Submit responses for teacher evaluation. Only available for third-year students with a class code.
                </p>
                <?php if ($is_logged_in): ?>
                <div class="card-hint">
                    <i class="bi bi-person-badge"></i>
                    <span><?php echo $user_role === 'teacher' ? 'Access Admin Panel' : 'Enter Class Code'; ?></span>
                </div>
                <?php else: ?>
                <div class="card-hint">
                    <i class="bi bi-lock-fill"></i>
                    <span>Login required</span>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- STATUS MESSAGE -->
        <?php if ($is_logged_in): ?>
        <div class="status-message success">
            <i class="bi bi-check-circle"></i>
            <span>You are logged in as <strong><?php echo htmlspecialchars(getUserFullName()); ?></strong> (<?php echo htmlspecialchars($user_role); ?>)</span>
        </div>
        <?php else: ?>
        <div class="status-message info">
            <i class="bi bi-info-circle"></i>
            <span>You are not logged in. Please login to access evaluation features.</span>
        </div>
        <?php endif; ?>

        <?php if ($logout_success && $logout_name): ?>
        <div class="status-message success">
            <i class="bi bi-check-circle"></i>
            <span>Successfully logged out. Goodbye, <strong><?php echo htmlspecialchars($logout_name); ?></strong>!</span>
        </div>
        <?php endif; ?>

        <!-- ACKNOWLEDGMENT -->
        <p class="acknowledgment">
            This website was made thanks to the collaboration of Adrián Torres and Justin Valladares from 
            the Class of 2025 as a way of giving back and showing gratitude to the ¡Supérate! Program.
        </p>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        © 2025 TOEIC Practice Platform — All rights reserved.
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="welcome.js"></script>
    
    <!-- Solo pasar las variables una vez -->
    <script>
        // Pasar datos de PHP a JavaScript - usando un objeto para evitar conflictos
        window.userData = {
            isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
            userRole: '<?php echo $user_role ?: ''; ?>',
            userClass: '<?php echo $user_class ?: ''; ?>',
            userFullname: '<?php echo $user_fullname ?: ''; ?>'
        };
        
        // Mostrar modal de logout exitoso si está presente
        window.addEventListener('DOMContentLoaded', function() {
            <?php if ($logout_success && $logout_name): ?>
            setTimeout(function() {
                const logoutSuccessModal = new bootstrap.Modal(document.getElementById('logoutSuccessModal'));
                logoutSuccessModal.show();
            }, 500);
            <?php endif; ?>
        });
    </script>
</body>
</html>