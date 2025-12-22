// welcome.js - VERSIÓN ACTUALIZADA CON NUEVA LÓGICA PARA PRACTICE ON MY OWN

// Variables globales
let userData = {};

// ===== FUNCIONES PRINCIPALES DE NAVEGACIÓN =====

// Función para redirigir a Practice on my own (NUEVA LÓGICA)
function goToPractice() {
    console.log('goToPractice called');
    
    // Verificar si está logueado
    if (userData.isLoggedIn) {
        // Si está logueado (teacher o student), ir a homepage
        console.log('User is logged in, redirecting to homepage.php');
        window.location.href = "homepage.php";
    } else {
        // Si no está logueado, ir a auth con parámetro para redirigir a homepage después del login
        console.log('User not logged in, redirecting to auth.php for homepage access');
        
        // Guardar en localStorage el destino después del login
        localStorage.setItem('redirectAfterLogin', 'homepage');
        
        // Redirigir a auth.php con parámetro
        window.location.href = "auth.php?mode=homepage";
    }
}

// Función para redirigir a Evaluation Practice
function goToEvaluation() {
    console.log('goToEvaluation called');
    
    // Verificar si está logueado
    if (userData.isLoggedIn) {
        console.log('User is logged in, checking role...');
        
        // Verificar el rol del usuario
        if (userData.userRole === 'teacher') {
            // Si es teacher, ir al admin panel
            console.log('Teacher detected, redirecting to admin/admin.php');
            window.location.href = "admin/admin.php";
        } else if (userData.userRole === 'student') {
            // Si es student, verificar si es de tercer año
            if (userData.userClass === 'third') {
                // Si es de tercer año, ir a la página de código
                console.log('Third-year student detected, redirecting to codepage.php');
                window.location.href = "codepage.php";
            } else {
                // Si no es de tercer año, mostrar modal de acceso denegado
                console.log('Access denied: Only third-year students can access Evaluation Practice');
                const accessDeniedModal = new bootstrap.Modal(document.getElementById('accessDeniedModal'));
                accessDeniedModal.show();
            }
        } else {
            // Rol desconocido
            console.error('Unknown user role:', userData.userRole);
            showNotification('Error: Unknown user role', 'error');
        }
    } else {
        // Si no está logueado, ir a login con parámetro para evaluation practice
        console.log('User not logged in, redirecting to auth.php for evaluation');
        
        // Guardar en localStorage el destino después del login
        localStorage.setItem('redirectAfterLogin', 'evaluation');
        
        // Redirigir a auth.php con parámetro
        window.location.href = "auth.php?mode=evaluation";
    }
}

// Función para manejar logout
function handleLogout(e) {
    if (e) e.preventDefault();
    
    console.log('Logout requested - showing modal');
    
    // Mostrar el modal de logout
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
    
    // Limpiar localStorage relacionado con sesión
    localStorage.removeItem('redirectAfterLogin');
    localStorage.removeItem('practiceData');
    
    return false;
}

// ===== INICIALIZACIÓN =====

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - welcome.js');
    
    // Obtener variables de PHP del objeto window
    userData = window.userData || {};
    
    console.log('User Status:', {
        isLoggedIn: userData.isLoggedIn,
        role: userData.userRole,
        class: userData.userClass,
        name: userData.userFullname
    });
    
    // Configurar event listeners para las tarjetas
    setupCardListeners();
    
    // Configurar botones de usuario
    setupUserButtons();
    
    // Configurar teclas de acceso rápido
    setupKeyboardShortcuts();
    
    // Mostrar mensaje de bienvenida
    showWelcomeMessage();
    
    // Efecto de carga suave
    setupPageTransition();
    
    // Verificar parámetros de URL
    checkUrlParameters();
    
    // Añadir estilos para notificaciones
    addNotificationStyles();
    
    // Debug: Log para verificar que las funciones están disponibles
    console.log('Functions available: goToPractice =', typeof goToPractice, 'goToEvaluation =', typeof goToEvaluation);
});

// ===== FUNCIONES DE CONFIGURACIÓN =====

function setupCardListeners() {
    console.log('Setting up card listeners');
    
    const practiceCard = document.getElementById('practiceCard');
    const evaluationCard = document.getElementById('evaluationCard');
    
    if (practiceCard) {
        console.log('Practice card found, adding click listener');
        
        // Remover onclick del HTML y usar event listener
        practiceCard.onclick = null;
        practiceCard.addEventListener('click', goToPractice);
        
        // Efectos hover mejorados
        practiceCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.12)';
        });
        
        practiceCard.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Asegurarse de que el cursor sea pointer
        practiceCard.style.cursor = 'pointer';
        
        // Actualizar descripción según estado de login
        const cardDescription = practiceCard.querySelector('.card-description');
        if (cardDescription && !userData.isLoggedIn) {
            // Mantener la descripción original
            cardDescription.textContent = "Practice TOEIC Speaking and Writing without logging in. Your answers are saved temporarily in your browser.";
        } else if (cardDescription && userData.isLoggedIn) {
            // Actualizar descripción para usuarios logueados
            cardDescription.textContent = "Access your personalized practice dashboard. Your progress will be saved to your account.";
        }
    } else {
        console.error('Practice card not found!');
    }
    
    if (evaluationCard) {
        console.log('Evaluation card found, adding click listener');
        
        // Remover onclick del HTML y usar event listener
        evaluationCard.onclick = null;
        evaluationCard.addEventListener('click', goToEvaluation);
        
        // Efectos hover mejorados
        evaluationCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.12)';
        });
        
        evaluationCard.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Asegurarse de que el cursor sea pointer
        evaluationCard.style.cursor = 'pointer';
    } else {
        console.error('Evaluation card not found!');
    }
}

function setupUserButtons() {
    console.log('Setting up user buttons');
    
    // Configurar botón de logout
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        console.log('Logout button found');
        
        // Remover onclick del HTML y usar event listener
        logoutBtn.onclick = null;
        logoutBtn.addEventListener('click', handleLogout);
        logoutBtn.title = "Logout from platform";
    }
    
    // Configurar tooltips
    const userProfileBtn = document.querySelector('.user-profile-btn');
    if (userProfileBtn) {
        userProfileBtn.title = "View account information";
        userProfileBtn.setAttribute('data-bs-toggle', 'tooltip');
    }
    
    // Inicializar tooltips de Bootstrap
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Alt + 1 para Practice on my own
        if (e.altKey && e.key === '1') {
            e.preventDefault();
            goToPractice();
        }
        // Alt + 2 para Evaluation Practice
        if (e.altKey && e.key === '2') {
            e.preventDefault();
            goToEvaluation();
        }
        // Alt + L para logout si está logueado
        if (e.altKey && e.key === 'l' && userData.isLoggedIn) {
            e.preventDefault();
            handleLogout();
        }
        // Escape para cerrar modales
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });
}

function showWelcomeMessage() {
    if (userData.isLoggedIn) {
        console.log(`Welcome back, ${userData.userFullname}! (${userData.userRole}, ${userData.userClass} year)`);
        
        // Mostrar mensaje específico según el rol
        if (userData.userRole === 'teacher') {
            console.log('Teacher account detected - full access available');
        } else if (userData.userRole === 'student') {
            console.log(`Student account detected - ${userData.userClass} year access`);
        }
    } else {
        console.log('Welcome to TOEIC Practice Platform. Please login to access all features.');
    }
}

function setupPageTransition() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    
    setTimeout(function() {
        document.body.style.opacity = '1';
    }, 100);
}

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Verificar si hay un parámetro de logout exitoso
    if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
        console.log('Logout successful - cleaning URL');
        // Limpiar la URL para quitar parámetros
        if (window.history.replaceState) {
            const cleanUrl = window.location.pathname;
            window.history.replaceState(null, '', cleanUrl);
        }
    }
    
    // Verificar si hay otros parámetros importantes
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        showNotification(`Error: ${error}`, 'error');
    }
    
    if (urlParams.has('message')) {
        const message = urlParams.get('message');
        showNotification(message, 'success');
    }
}

function addNotificationStyles() {
    if (!document.getElementById('welcome-animations')) {
        const style = document.createElement('style');
        style.id = 'welcome-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            }
        `;
        document.head.appendChild(style);
    }
}

// Función para mostrar notificación
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        border-left: 4px solid ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#0056D2'};
    `;
    
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// ===== FUNCIONES DE DIAGNÓSTICO =====

// Función para verificar estado de sesión (útil para debugging)
function checkSessionStatus() {
    console.group('Session Status Check');
    console.log('User Data from PHP:', userData);
    console.log('Local Storage Redirect:', localStorage.getItem('redirectAfterLogin'));
    console.log('Cookies:', document.cookie);
    console.groupEnd();
    
    return userData;
}

// Exportar funciones para debugging
window.welcomeDebug = {
    checkSessionStatus,
    goToPractice,
    goToEvaluation,
    userData
};

// Auto-check al cargar (debugging)
if (window.location.search.includes('debug')) {
    setTimeout(checkSessionStatus, 1000);
}

// Hacer las funciones disponibles globalmente
window.goToPractice = goToPractice;
window.goToEvaluation = goToEvaluation;
window.handleLogout = handleLogout;

// ===== NUEVA FUNCIÓN PARA VERIFICAR ACCESO =====

// Función auxiliar para verificar acceso a homepage
function checkHomepageAccess() {
    if (userData.isLoggedIn) {
        return {
            allowed: true,
            reason: 'user_logged_in',
            message: 'User is logged in, redirecting to homepage',
            action: function() {
                window.location.href = "homepage.php";
            }
        };
    } else {
        return {
            allowed: false,
            reason: 'user_not_logged_in',
            message: 'User needs to login first',
            action: function() {
                localStorage.setItem('redirectAfterLogin', 'homepage');
                window.location.href = "auth.php?mode=homepage";
            }
        };
    }
}

// Hacer la nueva función disponible globalmente
window.checkHomepageAccess = checkHomepageAccess;