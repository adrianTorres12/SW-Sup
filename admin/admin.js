// admin.js functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile Navigation
      const menuToggle = document.getElementById('menuToggle');
      const sidebar = document.getElementById('sidebar');
      const sidebarClose = document.getElementById('sidebarClose');
      const mobileNavOverlay = document.getElementById('mobileNavOverlay');
      
      if (menuToggle) {
        menuToggle.addEventListener('click', function() {
          sidebar.classList.add('show');
          mobileNavOverlay.classList.add('show');
          document.body.style.overflow = 'hidden';
        });
      }

      if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
          sidebar.classList.remove('show');
          mobileNavOverlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }

      if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', function() {
          sidebar.classList.remove('show');
          mobileNavOverlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }

      // Navigation
      const navItems = document.querySelectorAll('.nav-item');
      navItems.forEach(item => {
        item.addEventListener('click', function(e) {
          if (this.classList.contains('upload-link')) return;
          
          e.preventDefault();
          
          // Close mobile menu if open
          sidebar.classList.remove('show');
          mobileNavOverlay.classList.remove('show');
          document.body.style.overflow = '';
          
          // Update active nav item
          document.querySelectorAll('.nav-item').forEach(x => x.classList.remove('active'));
          this.classList.add('active');
          
          // Show corresponding panel
          const panelId = this.getAttribute('data-panel');
          document.querySelectorAll('.panel').forEach(p => p.classList.remove('active-panel'));
          const targetPanel = document.getElementById(panelId);
          if (targetPanel) {
            targetPanel.classList.add('active-panel');
          }
        });
      });

      // Users search and filter
      const usersSearch = document.getElementById('users-search');
      const usersFilter = document.getElementById('users-filter');
      const usersTable = document.getElementById('users-table');

      if (usersSearch && usersTable) {
        usersSearch.addEventListener('input', filterUsersTable);
        usersFilter.addEventListener('change', filterUsersTable);
      }

      function filterUsersTable() {
        const searchTerm = (usersSearch.value || '').toLowerCase();
        const filterValue = usersFilter.value;
        
        const rows = usersTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const name = row.getAttribute('data-name') || '';
          const email = row.getAttribute('data-email') || '';
          const userClass = row.getAttribute('data-class') || '';
          
          const matchesSearch = !searchTerm || 
            name.includes(searchTerm) || 
            email.includes(searchTerm);
          
          const matchesFilter = !filterValue || userClass === filterValue;
          
          row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
      }

      // Students search and filter
      const studentsSearch = document.getElementById('students-search');
      const studentsClassFilter = document.getElementById('students-class-filter');
      const studentsTable = document.getElementById('students-table');

      if (studentsSearch && studentsTable) {
        studentsSearch.addEventListener('input', filterStudentsTable);
        studentsClassFilter.addEventListener('change', filterStudentsTable);
      }

      function filterStudentsTable() {
        const searchTerm = (studentsSearch.value || '').toLowerCase();
        const classFilter = studentsClassFilter.value;
        
        const rows = studentsTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const name = row.getAttribute('data-name') || '';
          const studentClass = row.getAttribute('data-class') || '';
          
          const matchesSearch = !searchTerm || name.includes(searchTerm);
          const matchesClass = !classFilter || studentClass === classFilter;
          
          row.style.display = (matchesSearch && matchesClass) ? '' : 'none';
        });
      }

      // Add allowed teacher
      const addTeacherBtn = document.getElementById('add-teacher');
      if (addTeacherBtn) {
        addTeacherBtn.addEventListener('click', function() {
          const emailInput = document.getElementById('teacher-email');
          const email = emailInput.value.trim();
          
          if (!email) {
            showModal('Error', 'Please enter teacher email');
            return;
          }
          
          // Validate email format
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(email)) {
            showModal('Error', 'Invalid email format');
            return;
          }
          
          // Validate domain
          if (!email.endsWith('@adoc.superate.org.sv')) {
            showModal('Error', 'Teacher email must be from @adoc.superate.org.sv domain');
            return;
          }
          
          // Submit via hidden form
          document.getElementById('hp_action').value = 'add_allowed_teacher';
          document.getElementById('hp_teacher_email').value = email;
          document.getElementById('hidden-post-form').submit();
        });
      }

      // Add class code
      const addCodeBtn = document.getElementById('add-code');
      if (addCodeBtn) {
        addCodeBtn.addEventListener('click', function() {
          const codeInput = document.getElementById('code-value');
          const nameInput = document.getElementById('code-name');
          
          const code = codeInput.value.trim();
          const name = nameInput.value.trim();
          
          if (!code) {
            showModal('Error', 'Please enter class code');
            return;
          }
          
          // Validate format
          if (code.length > 13) {
            showModal('Error', 'Class code must be maximum 13 characters');
            return;
          }
          
          if (!/^[a-zA-Z0-9]+$/.test(code)) {
            showModal('Error', 'Class code must contain only letters and numbers');
            return;
          }
          
          // Submit via hidden form
          document.getElementById('hp_action').value = 'add_class_code';
          document.getElementById('hp_code_value').value = code;
          document.getElementById('hp_code_name').value = name;
          document.getElementById('hidden-post-form').submit();
        });
      }

      // Logout functionality
      const logoutBtn = document.getElementById('logoutBtn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
          showLogoutModal();
        });
      }

      // Logout confirmation modal
      function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        const overlay = document.getElementById('modalOverlay');
        const confirmBtn = document.getElementById('confirmLogoutBtn');
        
        modal.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        confirmBtn.onclick = function() {
          window.location.href = 'admin.php?logout=1';
        };
      }

      // Modal Functions
      function showModal(title, message) {
        const modal = document.getElementById('infoModal');
        const modalTitle = document.getElementById('infoModalTitle');
        const modalMessage = document.getElementById('infoModalMessage');
        const overlay = document.getElementById('modalOverlay');
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        modal.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
      }

      function closeModal() {
        const modals = document.querySelectorAll('.modal');
        const overlay = document.getElementById('modalOverlay');
        
        modals.forEach(modal => modal.classList.remove('show'));
        overlay.classList.remove('show');
        document.body.style.overflow = '';
      }

      // Delete confirmation modal
      let currentDeleteType = '';
      let currentDeleteId = '';

      window.confirmDelete = function(type, id, name) {
        currentDeleteType = type;
        currentDeleteId = id;
        
        const modal = document.getElementById('confirmationModal');
        const message = document.getElementById('confirmationMessage');
        const overlay = document.getElementById('modalOverlay');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        const itemName = type === 'teacher' ? `teacher with email: ${name}` : `class code: ${name}`;
        message.textContent = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
        
        confirmBtn.textContent = type === 'teacher' ? 'Delete Teacher' : 'Delete Code';
        confirmBtn.onclick = executeDelete;
        
        modal.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
      };

      function executeDelete() {
        if (currentDeleteType === 'teacher') {
          document.getElementById('hp_action').value = 'delete_allowed_teacher';
          document.getElementById('hp_teacher_id').value = currentDeleteId;
        } else if (currentDeleteType === 'code') {
          document.getElementById('hp_action').value = 'delete_class_code';
          document.getElementById('hp_code_id').value = currentDeleteId;
        }
        
        document.getElementById('hidden-post-form').submit();
        closeModal();
      }

      // Close modals when clicking overlay
      document.getElementById('modalOverlay').addEventListener('click', closeModal);

      // Close modals with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeModal();
        }
      });

      // Placeholder functions
      window.openStudentTests = function(userId) {
        showModal('Coming Soon', 'Student test review feature will be available soon.');
      };

      window.openFullTest = function(testNumber) {
        window.location.href = `../writing/practice_on_my_own/practice-writing-test.php?test_number=${testNumber}&mode=full`;
      };
    });