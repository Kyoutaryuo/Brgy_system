// ===== Document Ready =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize file uploads
    initFileUpload();
    
    // Initialize modals
    initModals();
    
    // Initialize alerts auto-dismiss
    initAlerts();
    
    // Initialize document selection
    initDocumentSelect();
    
    // Initialize confirmations
    initConfirmations();
    
    // Initialize sidebar toggle for mobile
    initSidebar();
}

// ===== File Upload =====
function initFileUpload() {
    const fileUploads = document.querySelectorAll('.file-upload');
    
    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const fileList = upload.closest('.form-group').querySelector('.file-list');
        
        if (!input) return;
        
        upload.addEventListener('click', () => input.click());
        
        upload.addEventListener('dragover', (e) => {
            e.preventDefault();
            upload.classList.add('dragover');
        });
        
        upload.addEventListener('dragleave', () => {
            upload.classList.remove('dragover');
        });
        
        upload.addEventListener('drop', (e) => {
            e.preventDefault();
            upload.classList.remove('dragover');
            input.files = e.dataTransfer.files;
            updateFileList(input, fileList);
        });
        
        input.addEventListener('change', () => {
            updateFileList(input, fileList);
        });
    });
}

function updateFileList(input, fileList) {
    if (!fileList) return;
    
    fileList.innerHTML = '';
    
    Array.from(input.files).forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'file-item';
        item.innerHTML = `
            <span>📄 ${file.name} (${formatFileSize(file.size)})</span>
            <span class="remove" data-index="${index}">✕</span>
        `;
        fileList.appendChild(item);
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ===== Modals =====
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modal;
            openModal(modalId);
        });
    });
    
    // Close modal buttons
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });
    
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeAllModals();
            }
        });
    });
    
    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = '';
}

// ===== Alerts =====
function initAlerts() {
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    
    alerts.forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, delay);
    });
}

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || document.body;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('data-auto-dismiss', '5000');
    alert.innerHTML = message;
    
    alertContainer.prepend(alert);
    
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// ===== Document Selection =====
function initDocumentSelect() {
    const docSelect = document.getElementById('document_id');
    const requirementsDiv = document.getElementById('requirements-display');
    
    if (docSelect && requirementsDiv) {
        docSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const requirements = selectedOption.dataset.requirements;
            const fee = selectedOption.dataset.fee;
            const days = selectedOption.dataset.days;
            
            if (requirements) {
                const reqList = requirements.split(',');
                let html = `
                    <div class="requirements-list">
                        <h4>📋 Requirements:</h4>
                        <ul>
                            ${reqList.map(req => `<li>${req.trim()}</li>`).join('')}
                        </ul>
                        <p class="mt-10"><strong>Processing Fee:</strong> ₱${parseFloat(fee).toFixed(2)}</p>
                        <p><strong>Processing Time:</strong> ${days} day(s)</p>
                    </div>
                `;
                requirementsDiv.innerHTML = html;
            } else {
                requirementsDiv.innerHTML = '';
            }
        });
    }
}

// ===== Confirmations =====
function initConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// ===== Sidebar Toggle (Mobile) =====
function initSidebar() {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
}

// ===== Form Validation =====
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// ===== AJAX Helper =====
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

// ===== Date/Time Helpers =====
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ===== Print Function =====
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; }
            </style>
        </head>
        <body>
            ${element.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
