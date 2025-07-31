/**
 * Enhanced File Manager JavaScript
 * Features: Drag & drop, progress indicators, keyboard shortcuts, accessibility
 */

// Global variables
let uploadProgress = 0;
let isUploading = false;
let currentView = 'grid';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFileManager();
    setupAccessibility();
    setupKeyboardShortcuts();
    setupDragAndDrop();
    setupPerformanceMonitoring();
});

/**
 * Initialize the file manager
 */
function initializeFileManager() {
    // Initialize tooltips
    initializeTooltips();
    
    // Setup view switching
    setupViewSwitching();
    
    // Setup search functionality
    setupSearch();
    
    // Setup file operations
    setupFileOperations();
    
    // Setup modals
    setupModals();
    
    console.log('File Manager initialized successfully');
}

/**
 * Setup accessibility features
 */
function setupAccessibility() {
    // Add ARIA labels to interactive elements
    document.querySelectorAll('button').forEach(button => {
        if (!button.getAttribute('aria-label')) {
            const text = button.textContent.trim();
            if (text) {
                button.setAttribute('aria-label', text);
            }
        }
    });
    
    // Add focus indicators
    document.addEventListener('focusin', function(e) {
        if (e.target.matches('button, input, a')) {
            e.target.style.outline = '2px solid #007bff';
            e.target.style.outlineOffset = '2px';
        }
    });
    
    document.addEventListener('focusout', function(e) {
        if (e.target.matches('button, input, a')) {
            e.target.style.outline = '';
            e.target.style.outlineOffset = '';
        }
    });
    
    // Announce dynamic content changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('alert')) {
                        announceToScreenReader(node.textContent);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Announce text to screen readers
 */
function announceToScreenReader(text) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = text;
    document.body.appendChild(announcement);
    
    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

/**
 * Setup keyboard shortcuts
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Only trigger shortcuts when not typing in input fields
        if (e.target.matches('input, textarea')) return;
        
        // Ctrl/Cmd + U: Upload file
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            e.preventDefault();
            triggerFileUpload();
        }
        
        // Ctrl/Cmd + N: New folder
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            showCreateFolder();
        }
        
        // Ctrl/Cmd + F: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape: Close modals
        if (e.key === 'Escape') {
            closeAllModals();
        }
        
        // Enter: Trigger primary action
        if (e.key === 'Enter' && e.target.matches('.action-btn')) {
            e.preventDefault();
            e.target.click();
        }
    });
}

/**
 * Setup drag and drop functionality
 */
function setupDragAndDrop() {
    const dropZone = document.querySelector('.dashboard-container');
    if (!dropZone) return;
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        dropZone.classList.add('drag-over');
    }
    
    function unhighlight(e) {
        dropZone.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            uploadMultipleFiles(files);
        }
    }
}

/**
 * Upload multiple files with progress
 */
function uploadMultipleFiles(files) {
    const totalFiles = files.length;
    let uploadedFiles = 0;
    
    showUploadProgress();
    
    Array.from(files).forEach((file, index) => {
        uploadFileWithProgress(file, (progress) => {
            const overallProgress = ((uploadedFiles + progress) / totalFiles) * 100;
            updateProgressBar(overallProgress);
            
            if (progress === 100) {
                uploadedFiles++;
                if (uploadedFiles === totalFiles) {
                    hideUploadProgress();
                    location.reload();
                }
            }
        });
    });
}

/**
 * Upload single file with progress
 */
function uploadFileWithProgress(file, callback) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', getCSRFToken());
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            callback(percentComplete);
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            callback(100);
        } else {
            showError('Upload failed');
            callback(100);
        }
    });
    
    xhr.addEventListener('error', function() {
        showError('Upload failed');
        callback(100);
    });
    
    xhr.open('POST', window.location.href);
    xhr.send(formData);
}

/**
 * Show upload progress UI
 */
function showUploadProgress() {
    const progressContainer = document.createElement('div');
    progressContainer.id = 'upload-progress';
    progressContainer.className = 'upload-progress';
    progressContainer.innerHTML = `
        <div class="progress-overlay">
            <div class="progress-content">
                <h4>📤 Uploading Files...</h4>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <p id="progress-text">0%</p>
            </div>
        </div>
    `;
    document.body.appendChild(progressContainer);
}

/**
 * Update progress bar
 */
function updateProgressBar(percent) {
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    
    if (progressFill && progressText) {
        progressFill.style.width = percent + '%';
        progressText.textContent = Math.round(percent) + '%';
    }
}

/**
 * Hide upload progress UI
 */
function hideUploadProgress() {
    const progressContainer = document.getElementById('upload-progress');
    if (progressContainer) {
        progressContainer.remove();
    }
}

/**
 * Setup view switching
 */
function setupViewSwitching() {
    const viewButtons = document.querySelectorAll('.view-btn');
    const filesContainer = document.getElementById('files-container');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active button
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update container class
            if (filesContainer) {
                filesContainer.className = view === 'grid' ? 'files-grid' : 'files-list';
            }
            
            currentView = view;
            
            // Save preference
            localStorage.setItem('fileManagerView', view);
            
            // Announce change to screen readers
            announceToScreenReader(`Switched to ${view} view`);
        });
    });
    
    // Restore saved view preference
    const savedView = localStorage.getItem('fileManagerView');
    if (savedView) {
        const savedButton = document.querySelector(`[data-view="${savedView}"]`);
        if (savedButton) {
            savedButton.click();
        }
    }
}

/**
 * Setup search functionality
 */
function setupSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(this.value);
        }, 300);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(this.value);
        }
    });
}

/**
 * Perform search
 */
function performSearch(query) {
    if (!query.trim()) {
        location.href = window.location.pathname;
        return;
    }
    
    const searchUrl = new URL(window.location);
    searchUrl.searchParams.set('search', query);
    location.href = searchUrl.toString();
}

/**
 * Setup file operations
 */
function setupFileOperations() {
    // File upload trigger
    const uploadBtn = document.querySelector('.upload-btn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', triggerFileUpload);
    }
    
    // Folder creation
    const folderBtn = document.querySelector('.folder-btn');
    if (folderBtn) {
        folderBtn.addEventListener('click', showCreateFolder);
    }
}

/**
 * Trigger file upload
 */
function triggerFileUpload() {
    const fileInput = document.getElementById('file-upload');
    if (fileInput) {
        fileInput.click();
    }
}

/**
 * Upload file
 */
function uploadFile(file) {
    if (!file) return;
    
    showUploadProgress();
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', getCSRFToken());
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            updateProgressBar(percentComplete);
        }
    });
    
    xhr.addEventListener('load', function() {
        hideUploadProgress();
        if (xhr.status === 200) {
            location.reload();
        } else {
            showError('Upload failed');
        }
    });
    
    xhr.addEventListener('error', function() {
        hideUploadProgress();
        showError('Upload failed');
    });
    
    xhr.open('POST', window.location.href);
    xhr.send(formData);
}

/**
 * Download file
 */
function downloadFile(filename) {
    // Track download in analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'file_download', {
            'file_name': filename
        });
    }
    
    window.location.href = '?download=' + encodeURIComponent(filename);
}

/**
 * Delete item
 */
function deleteItem(name, type) {
    if (confirm(`Are you sure you want to delete "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_name" value="${name}">
            <input type="hidden" name="delete_type" value="${type}">
            <input type="hidden" name="csrf_token" value="${getCSRFToken()}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Setup modals
 */
function setupModals() {
    // Create folder modal
    const createFolderBtn = document.querySelector('.folder-btn');
    if (createFolderBtn) {
        createFolderBtn.addEventListener('click', showCreateFolder);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                hideModal(modal);
            }
        });
    });
    
    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

/**
 * Show create folder modal
 */
function showCreateFolder() {
    const modal = document.getElementById('create-folder-modal');
    if (modal) {
        modal.style.display = 'flex';
        const input = modal.querySelector('input[name="folder_name"]');
        if (input) {
            input.focus();
        }
    }
}

/**
 * Hide create folder modal
 */
function hideCreateFolder() {
    const modal = document.getElementById('create-folder-modal');
    if (modal) {
        hideModal(modal);
    }
}

/**
 * Hide modal
 */
function hideModal(modal) {
    modal.style.display = 'none';
}

/**
 * Close all modals
 */
function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        hideModal(modal);
    });
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Add tooltips to action buttons
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this, this.getAttribute('title'));
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

/**
 * Show tooltip
 */
function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element.tooltip = tooltip;
}

/**
 * Hide tooltip
 */
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Show error message
 */
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <span class="alert-icon">⚠️</span>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Get CSRF token
 */
function getCSRFToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : '';
}

/**
 * Setup performance monitoring
 */
function setupPerformanceMonitoring() {
    // Monitor page load performance
    window.addEventListener('load', function() {
        if ('performance' in window) {
            const perfData = performance.getEntriesByType('navigation')[0];
            const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
            
            console.log('Page load time:', loadTime, 'ms');
            
            // Send to analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'timing_complete', {
                    name: 'load',
                    value: Math.round(loadTime)
                });
            }
            
            // Show warning if load time is too slow
            if (loadTime > 3000) {
                console.warn('Page load time is slow:', loadTime, 'ms');
            }
        }
    });
    
    // Monitor file operations
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const start = performance.now();
        return originalFetch.apply(this, args).then(response => {
            const duration = performance.now() - start;
            console.log('Fetch operation took:', duration, 'ms');
            return response;
        });
    };
}

/**
 * Lazy load images
 */
function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

/**
 * Debounce function for performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function for performance
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for global access
window.FileManager = {
    uploadFile,
    downloadFile,
    deleteItem,
    showCreateFolder,
    hideCreateFolder,
    showError,
    announceToScreenReader
};

// Add CSS for additional styles
const additionalStyles = `
.tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 1000;
    pointer-events: none;
}

.upload-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.progress-overlay {
    background: rgba(0, 0, 0, 0.5);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    min-width: 300px;
}

.drag-over {
    border: 2px dashed #007bff !important;
    background: rgba(0, 123, 255, 0.1) !important;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
