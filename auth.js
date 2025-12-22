// auth.js
document.addEventListener('DOMContentLoaded', function() {
    // ==========================
    // ELEMENT REFERENCES
    // ==========================
    const tabLogin = document.getElementById('tab-login');
    const tabSignup = document.getElementById('tab-signup');
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');
    const classSelect = document.getElementById('class_of');
    const classCodeLabel = document.getElementById('class_code_label');
    const loadingOverlay = document.getElementById('loading-overlay');
    const successModal = document.getElementById('successModal');
    const errorModal = document.getElementById('errorModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalActionBtn = document.getElementById('modalActionBtn');
    const errorModalMessage = document.getElementById('errorModalMessage');
    const modalCloseSuccess = document.getElementById('modalCloseSuccess');
    const modalCloseError = document.getElementById('modalCloseError');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    // ==========================
    // GLOBAL MODAL FUNCTIONS (accessible from onclick)
    // ==========================
    window.closeModal = function() {
        successModal.classList.remove('show');
        errorModal.classList.remove('show');
        document.body.style.overflow = '';
    };

    window.showModal = function(modalType, title, message, actionCallback = null, actionLabel = 'Continue') {
        const modal = modalType === 'success' ? successModal : errorModal;
        
        if (modalType === 'success') {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modalActionBtn.textContent = actionLabel;
            
            if (actionCallback) {
                modalActionBtn.onclick = function() {
                    window.closeModal();
                    actionCallback();
                };
            } else {
                modalActionBtn.onclick = window.closeModal;
            }
        } else {
            errorModalMessage.textContent = message;
        }
        
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    // ==========================
    // GET URL PARAMETERS
    // ==========================
    function getUrlParameter(name) {
        name = name.replace(/[\[\]]/g, '\\$&');
        const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
        const results = regex.exec(window.location.href);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    // Get redirect parameter from URL
    const redirectParam = getUrlParameter('redirect');
    const modeParam = getUrlParameter('mode');

    // ==========================
    // INITIALIZATION
    // ==========================
    // Hide preloader after 0.5s
    setTimeout(() => {
        loadingOverlay.style.opacity = '0';
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
        }, 300);
    }, 500);

    // ==========================
    // TAB SWITCHING
    // ==========================
    function setActiveTab(tab) {
        if (tab === 'login') {
            tabLogin.classList.add('active');
            tabSignup.classList.remove('active');
            loginForm.classList.add('active');
            signupForm.classList.remove('active');
        } else {
            tabLogin.classList.remove('active');
            tabSignup.classList.add('active');
            loginForm.classList.remove('active');
            signupForm.classList.add('active');
        }
    }

    tabLogin.addEventListener('click', () => setActiveTab('login'));
    tabSignup.addEventListener('click', () => setActiveTab('signup'));

    // ==========================
    // CLASS CODE FIELD TOGGLE
    // ==========================
    function updateClassCodeVisibility() {
        if (classSelect && classSelect.value === 'third') {
            classCodeLabel.style.display = 'block';
        } else {
            classCodeLabel.style.display = 'none';
        }
    }
    
    if (classSelect) {
        classSelect.addEventListener('change', updateClassCodeVisibility);
        updateClassCodeVisibility();
    }

    // ==========================
    // PASSWORD VISIBILITY TOGGLE
    // ==========================
    document.querySelectorAll('.eye-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    });

    // ==========================
    // PREVENT NUMBERS IN NAME FIELDS
    // ==========================
    function stripDigitsOnInput(e) {
        const original = e.target.value;
        const cleaned = original.replace(/\d+/g, '');
        if (cleaned !== original) {
            const pos = e.target.selectionStart - (original.length - cleaned.length);
            e.target.value = cleaned;
            try { e.target.setSelectionRange(pos, pos); } catch (err) { /* ignore */ }
        }
    }

    const fullnameInput = document.getElementById('fullname');
    const lastnamesInput = document.getElementById('lastnames');

    if (fullnameInput) {
        fullnameInput.addEventListener('input', stripDigitsOnInput);
        fullnameInput.addEventListener('keydown', (ev) => {
            if (/^\d$/.test(ev.key)) ev.preventDefault();
        });
        fullnameInput.addEventListener('paste', (ev) => {
            ev.preventDefault();
            const text = (ev.clipboardData || window.clipboardData).getData('text');
            const cleaned = text.replace(/\d+/g, '');
            document.execCommand('insertText', false, cleaned);
        });
    }

    if (lastnamesInput) {
        lastnamesInput.addEventListener('input', stripDigitsOnInput);
        lastnamesInput.addEventListener('keydown', (ev) => {
            if (/^\d$/.test(ev.key)) ev.preventDefault();
        });
        lastnamesInput.addEventListener('paste', (ev) => {
            ev.preventDefault();
            const text = (ev.clipboardData || window.clipboardData).getData('text');
            const cleaned = text.replace(/\d+/g, '');
            document.execCommand('insertText', false, cleaned);
        });
    }

    // ==========================
    // PASSWORD MATCHING FEEDBACK
    // ==========================
    const pwd = document.getElementById('signup_password');
    const pwdConfirm = document.getElementById('signup_password_confirm');

    if (pwd && pwdConfirm) {
        function checkPasswordMatch() {
            if (pwdConfirm.value.length === 0) {
                pwdConfirm.classList.remove('valid', 'invalid');
                return;
            }
            if (pwd.value === pwdConfirm.value) {
                pwdConfirm.classList.add('valid');
                pwdConfirm.classList.remove('invalid');
            } else {
                pwdConfirm.classList.add('invalid');
                pwdConfirm.classList.remove('valid');
            }
        }
        pwd.addEventListener('input', checkPasswordMatch);
        pwdConfirm.addEventListener('input', checkPasswordMatch);
    }

    // ==========================
    // MODAL EVENT LISTENERS
    // ==========================
    if (modalCloseSuccess) {
        modalCloseSuccess.addEventListener('click', window.closeModal);
    }
    
    if (modalCloseError) {
        modalCloseError.addEventListener('click', window.closeModal);
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', window.closeModal);
    }

    // Close modals when clicking outside
    [successModal, errorModal].forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) window.closeModal();
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.closeModal();
    });

    // ==========================
    // LOADING FUNCTIONS
    // ==========================
    function showLoading(text = 'Processing...') {
        document.getElementById('loading-text').textContent = text;
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.opacity = '1';
    }

    function hideLoading() {
        loadingOverlay.style.display = 'none';
        loadingOverlay.style.opacity = '0';
    }

    // ==========================
    // VALIDATION FUNCTIONS
    // ==========================
    function isAllowedEmail(email) {
        return email.toLowerCase().endsWith('@adoc.superate.org.sv');
    }

    function isTeacherEmailFormat(email) {
        return /^[a-zA-ZÀ-ÿ]+(?:[.\-][a-zA-ZÀ-ÿ]+)+@adoc\.superate\.org\.sv$/i.test(email);
    }

    // ==========================
    // TEACHER EMAIL CHECK (AJAX)
    // ==========================
    async function checkTeacherEmailOnServer(email) {
        try {
            const body = new URLSearchParams();
            body.append('action', 'check_teacher_email');
            body.append('email', email);
            const resp = await fetch('auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            return await resp.json();
        } catch (e) {
            console.error('Teacher email check error:', e);
            return { ok: false, reason: 'network', message: 'Network error while checking teacher email.' };
        }
    }

    // ==========================
    // FORM SUBMISSION HANDLERS
    // ==========================
    
    // LOGIN FORM
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('login_email').value.trim();
        const password = document.getElementById('login_password').value;
        const remember = document.getElementById('remember').checked;

        // Basic validation
        if (email === '' || password === '') {
            window.showModal('error', 'Error', 'Please complete both email and password.');
            return;
        }

        if (!isAllowedEmail(email)) {
            window.showModal('error', 'Error', 'Only @adoc.superate.org.sv emails are accepted.');
            return;
        }

        showLoading('Signing in...');
        
        // Submit via AJAX
        try {
            const formData = new FormData(this);
            
            console.log('Login form submitted for email:', email);
            
            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Login response:', result);
            hideLoading();
            
            if (result.status === 'success') {
                // Limpiar localStorage
                localStorage.removeItem('redirectAfterLogin');
                
                // SIEMPRE usar la URL de redirección del servidor si está disponible
                if (result.redirect) {
                    console.log('Redirecting to server-specified URL:', result.redirect);
                    window.location.href = result.redirect;
                } else {
                    // Si el servidor no especifica redirección, verificar localStorage
                    const storedRedirect = localStorage.getItem('redirectAfterLogin') || 
                                          (modeParam === 'homepage' ? 'homepage' : 
                                           modeParam === 'evaluation' ? 'evaluation' : '');
                    
                    if (storedRedirect === 'homepage') {
                        // SIEMPRE redirigir a homepage cuando el modo es homepage
                        window.location.href = 'homepage.php';
                    } else if (storedRedirect === 'evaluation') {
                        // Para evaluation, redirigir según el rol
                        if (result.user_role === 'teacher') {
                            window.showModal('success', 'Welcome!', result.message, function() {
                                window.location.href = 'admin/admin.php';
                            }, 'Go to Admin Panel');
                        } else {
                            window.showModal('success', 'Welcome!', result.message, function() {
                                window.location.href = 'welcome.php';
                            }, 'Go to Homepage');
                        }
                    } else {
                        // Default behavior
                        if (result.user_role === 'teacher') {
                            window.showModal('success', 'Welcome!', result.message, function() {
                                window.location.href = 'admin/admin.php';
                            }, 'Go to Admin Panel');
                        } else {
                            window.showModal('success', 'Welcome!', result.message, function() {
                                window.location.href = 'welcome.php';
                            }, 'Go to Homepage');
                        }
                    }
                }
            } else {
                window.showModal('error', 'Error', result.message);
            }
        } catch (error) {
            hideLoading();
            console.error('Login error:', error);
            window.showModal('error', 'Error', 'Network error. Please check your connection and try again.');
        }
    });

    // SIGNUP FORM
    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const fullname = document.getElementById('fullname').value.trim();
        const lastnames = document.getElementById('lastnames').value.trim();
        const email = document.getElementById('signup_email').value.trim();
        const pwdVal = document.getElementById('signup_password').value;
        const pwdConfirmVal = document.getElementById('signup_password_confirm').value;

        // Required fields
        if (fullname === '' || lastnames === '' || email === '' || pwdVal === '' || pwdConfirmVal === '') {
            window.showModal('error', 'Error', 'Please complete all required fields.');
            return;
        }

        // Names must not contain digits
        if (/\d/.test(fullname) || /\d/.test(lastnames)) {
            window.showModal('error', 'Error', 'Names cannot contain numbers. Spaces and accents are allowed.');
            return;
        }

        if (!isAllowedEmail(email)) {
            window.showModal('error', 'Error', 'Only @adoc.superate.org.sv emails are accepted.');
            return;
        }

        if (pwdVal !== pwdConfirmVal) {
            window.showModal('error', 'Error', 'Please ensure both passwords are the same.');
            return;
        }

        // If third year selected, ensure class code present
        if (classSelect && classSelect.value === 'third') {
            const codeVal = document.getElementById('class_code').value.trim();
            if (!codeVal) {
                window.showModal('error', 'Error', 'Third-year students must provide a class code given by a teacher.');
                return;
            }
        }

        // If teacher selected, do server-side pre-check
        if (classSelect && classSelect.value === 'teacher') {
            if (!isTeacherEmailFormat(email)) {
                window.showModal('error', 'Error', 'Teacher email must be in the format name.surname@adoc.superate.org.sv');
                return;
            }

            showLoading('Verifying teacher email...');
            const checkResp = await checkTeacherEmailOnServer(email);
            hideLoading();

            if (!checkResp || !checkResp.ok) {
                const message = checkResp?.message || 'Teacher email could not be verified.';
                window.showModal('error', 'Error', message);
                return;
            }
        }

        showLoading('Creating account...');
        
        // Submit via AJAX
        try {
            const formData = new FormData(this);
            
            console.log('Signup form submitted for email:', email);
            
            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Signup response:', result);
            hideLoading();
            
            if (result.status === 'success') {
                // Limpiar localStorage
                localStorage.removeItem('redirectAfterLogin');
                
                // SIEMPRE usar la URL de redirección del servidor si está disponible
                if (result.redirect) {
                    console.log('Redirecting to server-specified URL:', result.redirect);
                    window.location.href = result.redirect;
                } else {
                    // Si el servidor no especifica redirección, verificar localStorage
                    const storedRedirect = localStorage.getItem('redirectAfterLogin') || 
                                          (modeParam === 'homepage' ? 'homepage' : 
                                           modeParam === 'evaluation' ? 'evaluation' : '');
                    
                    if (storedRedirect === 'homepage') {
                        // SIEMPRE redirigir a homepage cuando el modo es homepage
                        window.location.href = 'homepage.php';
                    } else if (storedRedirect === 'evaluation') {
                        // Para evaluation, redirigir según el rol
                        if (result.user_role === 'teacher') {
                            window.showModal('success', 'Account Created!', result.message, function() {
                                window.location.href = 'admin/admin.php';
                            }, 'Go to Admin Panel');
                        } else {
                            window.showModal('success', 'Account Created!', result.message, function() {
                                window.location.href = 'welcome.php';
                            }, 'Go to Homepage');
                        }
                    } else {
                        // Default behavior
                        if (result.user_role === 'teacher') {
                            window.showModal('success', 'Account Created!', result.message, function() {
                                window.location.href = 'admin/admin.php';
                            }, 'Go to Admin Panel');
                        } else {
                            window.showModal('success', 'Account Created!', result.message, function() {
                                window.location.href = 'welcome.php';
                            }, 'Go to Homepage');
                        }
                    }
                }
            } else {
                window.showModal('error', 'Error', result.message);
            }
        } catch (error) {
            hideLoading();
            console.error('Signup error:', error);
            window.showModal('error', 'Error', 'Network error. Please check your connection and try again.');
        }
    });

    // ==========================
    // FORGOT PASSWORD
    // ==========================
    const forgotLink = document.getElementById('forgot-link');
    if (forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            window.showModal('error', 'Password Recovery', 'Password recovery will be available soon. For now, contact your teacher or admin.');
        });
    }

    // ==========================
    // REMEMBER ME PREFILL
    // ==========================
    const rememberCheckbox = document.getElementById('remember');

    function prefillRemember() {
        try {
            const match = document.cookie.match('(^|;)\\s*remember_user\\s*=\\s*([^;]+)');
            if (match) {
                rememberCheckbox.checked = true;
                document.getElementById('login_email').value = decodeURIComponent(match[2]);
            }
        } catch (e) {
            console.error('Remember me prefill error:', e);
        }
    }

    prefillRemember();

    // ==========================
    // SESSION CLEANUP ON PAGE UNLOAD
    // ==========================
    window.addEventListener('beforeunload', function() {
        // Check if remember me is NOT checked and there's a session cookie
        const rememberChecked = document.getElementById('remember')?.checked;
        if (!rememberChecked) {
            // Clear session-related cookies
            document.cookie = "session_user=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    });

    // ==========================
    // AUTO-REDIRECT IF REMEMBERED
    // ==========================
    function checkAutoRedirect() {
        try {
            const rememberMatch = document.cookie.match('(^|;)\\s*remember_user\\s*=\\s*([^;]+)');
            if (rememberMatch) {
                console.log('Remember me cookie found, auto-redirecting...');
                // Auto-redirect after a short delay
                setTimeout(() => {
                    // Preserve redirect parameter if present
                    const currentUrl = window.location.href;
                    const urlObj = new URL(currentUrl);
                    const redirectParam = urlObj.searchParams.get('redirect');
                    const modeParam = urlObj.searchParams.get('mode');
                    
                    let redirectUrl = 'auth.php?auto=1';
                    if (redirectParam) {
                        redirectUrl += '&redirect=' + encodeURIComponent(redirectParam);
                    }
                    if (modeParam) {
                        redirectUrl += '&mode=' + encodeURIComponent(modeParam);
                    }
                    
                    console.log('Auto-redirecting to:', redirectUrl);
                    window.location.href = redirectUrl;
                }, 500);
            }
        } catch (e) {
            console.error('Auto-redirect error:', e);
        }
    }

    // Run auto-redirect check on page load
    checkAutoRedirect();

    // ==========================
    // SHOW REDIRECT MESSAGE IF APPLICABLE
    // ==========================
    function showRedirectMessage() {
        if (redirectParam === 'homepage' || modeParam === 'homepage') {
            // Show message for homepage redirect
            const loginTab = document.getElementById('tab-login');
            if (loginTab) {
                loginTab.innerHTML = '<i class="bi bi-house-door"></i> Login for Homepage Access';
            }
        } else if (redirectParam === 'evaluation' || modeParam === 'evaluation') {
            // Show message for evaluation redirect
            const loginTab = document.getElementById('tab-login');
            if (loginTab) {
                loginTab.innerHTML = '<i class="bi bi-clipboard-check"></i> Login for Evaluation';
            }
        }
    }

    showRedirectMessage();
});