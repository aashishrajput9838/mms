// MMS - Money Management System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Password visibility toggle
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.querySelector(this.getAttribute('toggle'));
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Initialize date pickers if any
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }

    // Dashboard charts initialization (if Chart.js is included)
    if (typeof Chart !== 'undefined') {
        initDashboardCharts();
    }
});

// Function to initialize dashboard charts
function initDashboardCharts() {
    // Income vs Expense Chart (if canvas element exists)
    const incomeExpenseCanvas = document.getElementById('incomeExpenseChart');
    if (incomeExpenseCanvas) {
        const ctx = incomeExpenseCanvas.getContext('2d');
        
        // This data should be replaced with actual data from the backend
        const data = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Income',
                    data: [12000, 19000, 15000, 18000, 14000, 20000],
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: [8000, 12000, 10000, 9000, 13000, 11000],
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        };
        
        new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Expense distribution pie chart (if canvas element exists)
    const expenseDistCanvas = document.getElementById('expenseDistChart');
    if (expenseDistCanvas) {
        const ctx = expenseDistCanvas.getContext('2d');
        
        // This data should be replaced with actual data from the backend
        const data = {
            labels: ['Housing', 'Food', 'Transportation', 'Entertainment', 'Utilities', 'Others'],
            datasets: [{
                data: [30, 20, 15, 10, 15, 10],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        };
        
        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
}

// Handle AJAX form submissions
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();
        
        xhr.open('POST', form.getAttribute('action'), true);
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response);
                    } else {
                        // Default success action
                        showAlert('success', response.message || 'Operation completed successfully!');
                        form.reset();
                    }
                } else {
                    showAlert('danger', response.message || 'An error occurred. Please try again.');
                }
            } else {
                showAlert('danger', 'Server error. Please try again later.');
            }
        };
        
        xhr.onerror = function() {
            showAlert('danger', 'Connection error. Please check your internet connection.');
        };
        
        xhr.send(formData);
    });
}

// Function to show bootstrap alerts
function showAlert(type, message) {
    const alertPlaceholder = document.getElementById('alert-placeholder');
    if (!alertPlaceholder) return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertPlaceholder.appendChild(wrapper);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(wrapper.firstChild);
        alert.close();
    }, 5000);
} 