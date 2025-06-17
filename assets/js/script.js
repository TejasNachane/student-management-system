// Student Management System JavaScript Functions

// Notification System
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 10px;">&times;</button>
        </div>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Show notification if exists in PHP session
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.querySelector('[data-notification]');
    if (notification) {
        const message = notification.getAttribute('data-message');
        const type = notification.getAttribute('data-type');
        showNotification(message, type);
    }
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
            showNotification(`Please fill in the ${input.name} field`, 'error');
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Password validation
function validatePassword(password) {
    return password.length >= 6;
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Auto-generate student/faculty ID
function generateId(prefix) {
    const timestamp = Date.now().toString().slice(-6);
    return prefix + timestamp;
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    input.addEventListener('keyup', function() {
        const filter = input.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) { // Skip header row
            const cells = rows[i].getElementsByTagName('td');
            let match = false;
            
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().includes(filter)) {
                    match = true;
                    break;
                }
            }
            
            rows[i].style.display = match ? '' : 'none';
        }
    });
}

// Role selector for login page
function selectRole(role) {
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.role-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Add active class to selected button
    event.target.classList.add('active');
    
    // Set hidden input value
    const roleInput = document.getElementById('user_type');
    if (roleInput) {
        roleInput.value = role;
    }
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Print function
function printPage() {
    window.print();
}

// Loading state for buttons
function setLoading(buttonId, isLoading = true) {
    const button = document.getElementById(buttonId);
    if (button) {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = '<span class="loading"></span> Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
        }
    }
}

// Auto-fill functionality for course enrollment
function populateStudentInfo(studentId) {
    if (studentId) {
        fetch(`ajax/get_student_info.php?id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('student_name').value = data.student.full_name;
                    document.getElementById('student_email').value = data.student.email;
                } else {
                    showNotification('Student not found', 'error');
                }
            })
            .catch(error => {
                showNotification('Error fetching student information', 'error');
            });
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('Copied to clipboard!', 'success', 2000);
    }, function(err) {
        showNotification('Failed to copy to clipboard', 'error');
    });
}

// Check for session timeout
function checkSession() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                showNotification('Session expired. Please login again.', 'warning');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Session check failed:', error);
        });
}

// Check session every 5 minutes
setInterval(checkSession, 300000);

// Attendance marking functions
function markAttendance(studentId, courseCode, status) {
    const data = {
        student_id: studentId,
        course_code: courseCode,
        status: status
    };

    fetch('ajax/mark_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Attendance marked successfully', 'success');
            // Update the UI to reflect the change
            const button = document.querySelector(`[data-student="${studentId}"][data-status="${status}"]`);
            if (button) {
                button.classList.add('active');
                // Remove active class from other status buttons for this student
                const otherButtons = document.querySelectorAll(`[data-student="${studentId}"]:not([data-status="${status}"])`);
                otherButtons.forEach(btn => btn.classList.remove('active'));
            }
        } else {
            showNotification(data.message || 'Failed to mark attendance', 'error');
        }
    })
    .catch(error => {
        showNotification('Error marking attendance', 'error');
    });
}

// Bulk attendance operations
function markAllPresent() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    checkboxes.forEach(checkbox => {
        const studentId = checkbox.value;
        const courseCode = document.getElementById('course_code').value;
        markAttendance(studentId, courseCode, 'present');
    });
}

function markAllAbsent() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    checkboxes.forEach(checkbox => {
        const studentId = checkbox.value;
        const courseCode = document.getElementById('course_code').value;
        markAttendance(studentId, courseCode, 'absent');
    });
}

// Select all checkboxes
function toggleAllCheckboxes() {
    const masterCheckbox = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = masterCheckbox.checked;
    });
}

// Initialize page-specific functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search functionality if search input exists
    const searchInput = document.getElementById('searchInput');
    const dataTable = document.getElementById('dataTable');
    if (searchInput && dataTable) {
        searchTable('searchInput', 'dataTable');
    }

    // Initialize select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', toggleAllCheckboxes);
    }

    // Initialize role selector for login page
    const roleButtons = document.querySelectorAll('.role-btn');
    roleButtons.forEach(button => {
        button.addEventListener('click', function() {
            selectRole(this.getAttribute('data-role'));
        });
    });

    // Add form validation to all forms with class 'validate-form'
    const forms = document.querySelectorAll('.validate-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
            }
        });
    });
});
