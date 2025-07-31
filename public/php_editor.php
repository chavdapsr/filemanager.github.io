<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'PHP Code Editor';
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../templates/navigation.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>PHP Code Editor</h1>
                <div class="btn-group">
                    <button class="btn btn-success" onclick="saveCode()">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-info" onclick="formatCode()">
                        <i class="fas fa-code"></i> Format
                    </button>
                    <button class="btn btn-warning" onclick="clearCode()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-code"></i> PHP Code Editor
                                <small class="text-muted">(Auto-complete enabled - Press Tab to accept suggestions)</small>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <textarea 
                                id="phpEditor" 
                                class="form-control php-editor" 
                                data-php-autocomplete="true"
                                style="height: 500px; font-family: 'Courier New', monospace; font-size: 14px; border: none; resize: none;"
                                placeholder="Start typing PHP code here... Press Tab for auto-complete suggestions."
                            ><?php echo htmlspecialchars(getDefaultPHPTemplate()); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb"></i> Auto-Complete Tips
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Tab/Enter:</strong> Accept suggestion
                                </li>
                                <li class="mb-2">
                                    <strong>Arrow Keys:</strong> Navigate suggestions
                                </li>
                                <li class="mb-2">
                                    <strong>Escape:</strong> Close suggestions
                                </li>
                                <li class="mb-2">
                                    <strong>Mouse:</strong> Click to select
                                </li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Quick Templates:</h6>
                            <div class="btn-group-vertical w-100">
                                <button class="btn btn-sm btn-outline-primary" onclick="insertTemplate('class')">
                                    Class Template
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="insertTemplate('function')">
                                    Function Template
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="insertTemplate('database')">
                                    Database Connection
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="insertTemplate('form')">
                                    Form Handler
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Code Statistics
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 id="lineCount">0</h4>
                                    <small class="text-muted">Lines</small>
                                </div>
                                <div class="col-6">
                                    <h4 id="charCount">0</h4>
                                    <small class="text-muted">Characters</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/autocomplete.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('phpEditor');
    
    // Update statistics
    function updateStats() {
        const text = editor.value;
        const lines = text.split('\n').length;
        const chars = text.length;
        
        document.getElementById('lineCount').textContent = lines;
        document.getElementById('charCount').textContent = chars;
    }
    
    editor.addEventListener('input', updateStats);
    updateStats();
    
    // Auto-save functionality
    let autoSaveTimer;
    editor.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            localStorage.setItem('phpEditorContent', editor.value);
        }, 2000);
    });
    
    // Load saved content
    const savedContent = localStorage.getItem('phpEditorContent');
    if (savedContent) {
        editor.value = savedContent;
        updateStats();
    }
});

function saveCode() {
    const editor = document.getElementById('phpEditor');
    const content = editor.value;
    
    // Create a blob and download
    const blob = new Blob([content], { type: 'text/php' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'code.php';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    // Show success message
    showAlert('Code saved successfully!', 'success');
}

function formatCode() {
    const editor = document.getElementById('phpEditor');
    let content = editor.value;
    
    // Basic formatting (you can enhance this)
    content = content.replace(/\s*{\s*/g, ' {\n    ');
    content = content.replace(/\s*}\s*/g, '\n}\n');
    content = content.replace(/;\s*/g, ';\n    ');
    
    editor.value = content;
    showAlert('Code formatted!', 'info');
}

function clearCode() {
    if (confirm('Are you sure you want to clear the editor?')) {
        document.getElementById('phpEditor').value = '';
        localStorage.removeItem('phpEditorContent');
        showAlert('Editor cleared!', 'warning');
    }
}

function insertTemplate(type) {
    const editor = document.getElementById('phpEditor');
    let template = '';
    
    switch(type) {
        case 'class':
            template = `<?php
class MyClass {
    private $property;
    
    public function __construct() {
        $this->property = null;
    }
    
    public function getProperty() {
        return $this->property;
    }
    
    public function setProperty($value) {
        $this->property = $value;
    }
}`;
            break;
            
        case 'function':
            template = `<?php
function myFunction($param1, $param2 = null) {
    // Function logic here
    $result = $param1;
    
    if ($param2 !== null) {
        $result .= $param2;
    }
    
    return $result;
}`;
            break;
            
        case 'database':
            template = `<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=your_database;charset=utf8mb4',
        'username',
        'password',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $stmt = $pdo->prepare('SELECT * FROM table_name WHERE id = ?');
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
} catch(PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}`;
            break;
            
        case 'form':
            template = `<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($name && $email) {
        // Process form data
        $success = true;
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<form method="post">
    <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>`;
            break;
    }
    
    editor.value = template;
    showAlert('Template inserted!', 'success');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<?php
function getDefaultPHPTemplate() {
    return `<?php
// PHP File Manager - Code Editor
// Start typing your PHP code here...

// Example: Basic PHP structure
class FileManager {
    private $uploadPath;
    private $allowedExtensions;
    
    public function __construct($uploadPath = 'uploads/') {
        $this->uploadPath = $uploadPath;
        $this->allowedExtensions = ['jpg', 'png', 'pdf', 'txt'];
    }
    
    public function uploadFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        $filename = uniqid() . '.' . $extension;
        $filepath = $this->uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Upload failed'];
    }
}

// Example: Database connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=file_manager;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database connected successfully!";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Example: Session handling
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Example: File operations
$files = scandir('uploads/');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "File: " . htmlspecialchars($file) . "\\n";
    }
}

// Example: Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($name && $email) {
        echo "Form submitted: $name ($email)";
    }
}
?>`;
}
?>

<?php include __DIR__ . '/../templates/footer.php'; ?> 