<?php
class FileManager {
    private $root;

    public function __construct($root = null) {
        $this->root = $root ? realpath($root) : realpath('./');
        if (!$this->root) {
            $this->root = __DIR__;
        }
    }

    public function getFileStats($path) {
        if (!file_exists($path)) return null;
        return [
            'size' => filesize($path),
            'modified' => filemtime($path),
            'is_dir' => is_dir($path),
            'is_file' => is_file($path),
            'permissions' => fileperms($path),
            'readable' => is_readable($path),
            'writable' => is_writable($path)
        ];
    }

    private function isPathSafe($path) {
        $realPath = realpath($path);
        return $realPath && strpos($realPath, $this->root) === 0;
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    public function handleRequest() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        
        if (isset($_POST['action'])) {
            $response = ['success' => false, 'message' => ''];
            
            try {
                switch ($_POST['action']) {
    private $db;
                    case 'list':
                        $path = isset($_POST['path']) ? $_POST['path'] : $this->root;
                        if ($path === '' || $path === '#') $path = $this->root;
                        
                        $realPath = realpath($path);
                        if (!$realPath || !$this->isPathSafe($realPath)) {
        $this->db = function_exists('getDatabase') ? getDatabase() : null;
                            $realPath = $this->root;
                        }
                        
                        if (!is_dir($realPath)) {
                            $response['message'] = 'Directory not found';
                            break;
                        }
                        
                        $items = scandir($realPath);
                        $result = [];
                        
                        foreach ($items as $item) {
                            if ($item === '.' || $item === '..') continue;
                            
                            $itemPath = $realPath . DIRECTORY_SEPARATOR . $item;
                            $isDir = is_dir($itemPath);
                            
                            $result[] = [
                                'text' => $item,
                                'type' => $isDir ? 'folder' : 'file',
                                'children' => $isDir,
                                'id' => $itemPath,
                                'icon' => $isDir ? 'fas fa-folder' : 'fas fa-file'
                            ];
                        }
                        
                        $response = $result;
                        break;

                    case 'create_folder':
                        $parent = isset($_POST['parent']) ? realpath($_POST['parent']) : $this->root;
                        $name = trim($_POST['name'] ?? '');
                        
                        if ($response['success'] && $this->db) {
                            // Insert file info into DB
                            $this->db->insert('files', [
                                'filename' => $fileName,
                                'filepath' => $target,
                                'uploaded_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                        if (!$parent || !$this->isPathSafe($parent)) {
                            $response['message'] = 'Invalid parent directory';
                            break;
                        }
                        
                        if (empty($name)) {
                            $response['message'] = 'Folder name cannot be empty';
                            break;
                        }
                        
                        $newPath = $parent . DIRECTORY_SEPARATOR . $name;
                        
                        if (file_exists($newPath)) {
                            $response['message'] = 'Folder already exists';
                            break;
                            // Remove from DB if file
                            if ($response['success'] && $this->db) {
                                $this->db->delete('files', 'filepath = :filepath', ['filepath' => $target]);
                            }
                        }
                        
                        $response['success'] = mkdir($newPath);
                        $response['message'] = $response['success'] ? 'Folder created successfully' : 'Failed to create folder';
                        break;

                    case 'rename':
                        $from = isset($_POST['from']) ? realpath($_POST['from']) : false;
                        $newName = trim($_POST['to'] ?? '');
                        
                        if (!$from || !$this->isPathSafe($from)) {
                            $response['message'] = 'Invalid source path';
                            break;
                        }
                        
                        if (empty($newName)) {
                            $response['message'] = 'New name cannot be empty';
                            break;
                        }
                        
                        $to = dirname($from) . DIRECTORY_SEPARATOR . $newName;
                        
                        if (file_exists($to)) {
                            $response['message'] = 'File/folder with this name already exists';
                            break;
                        }
                        
                        $response['success'] = rename($from, $to);
                        $response['message'] = $response['success'] ? 'Renamed successfully' : 'Failed to rename';
                        break;

                    case 'delete':
                        $target = isset($_POST['target']) ? realpath($_POST['target']) : false;
                        
                        if (!$target || !$this->isPathSafe($target)) {
                            $response['message'] = 'Invalid target path';
                            break;
                        }
                        
                        if (is_dir($target)) {
                            $response['success'] = $this->deleteDirectory($target);
                        } else {
                            $response['success'] = unlink($target);
                        }
                        
                        $response['message'] = $response['success'] ? 'Deleted successfully' : 'Failed to delete';
                        break;

                    case 'upload':
                        $parent = isset($_POST['parent']) ? realpath($_POST['parent']) : $this->root;
                        
                        if (!$parent || !$this->isPathSafe($parent)) {
                            $response['message'] = 'Invalid upload directory';
                            break;
                        }
                        
                        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                            $response['message'] = 'No file uploaded or upload error';
                            break;
                        }
                        
                        $fileName = basename($_FILES['file']['name']);
                        $target = $parent . DIRECTORY_SEPARATOR . $fileName;
                        
                        if (file_exists($target)) {
                            $response['message'] = 'File already exists';
                            break;
                        }
                        
                        $response['success'] = move_uploaded_file($_FILES['file']['tmp_name'], $target);
                        $response['message'] = $response['success'] ? 'File uploaded successfully' : 'Failed to upload file';
                        break;

                    case 'download':
                        $file = isset($_POST['file']) ? realpath($_POST['file']) : false;
                        
                        if (!$file || !is_file($file) || !$this->isPathSafe($file)) {
                            $response['message'] = 'Invalid file path';
                            break;
                        }
                        
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file));
                        readfile($file);
                        exit;

                    case 'copy':
                        $from = isset($_POST['from']) ? realpath($_POST['from']) : false;
                        $to = isset($_POST['to']) ? $_POST['to'] : '';
                        
                        if (!$from || !$this->isPathSafe($from)) {
                            $response['message'] = 'Invalid source path';
                            break;
                        }
                        
                        if (!$this->isPathSafe(dirname($to))) {
                            $response['message'] = 'Invalid destination path';
                            break;
                        }
                        
                        if (file_exists($to)) {
                            $response['message'] = 'Destination already exists';
                            break;
                        }
                        
                        $response['success'] = copy($from, $to);
                        $response['message'] = $response['success'] ? 'Copied successfully' : 'Failed to copy';
                        break;

                    case 'move':
                        $from = isset($_POST['from']) ? realpath($_POST['from']) : false;
                        $to = isset($_POST['to']) ? $_POST['to'] : '';
                        
                        if (!$from || !$this->isPathSafe($from)) {
                            $response['message'] = 'Invalid source path';
                            break;
                        }
                        
                        if (!$this->isPathSafe(dirname($to))) {
                            $response['message'] = 'Invalid destination path';
                            break;
                        }
                        
                        if (file_exists($to)) {
                            $response['message'] = 'Destination already exists';
                            break;
                        }
                        
                        $response['success'] = rename($from, $to);
                        $response['message'] = $response['success'] ? 'Moved successfully' : 'Failed to move';
                        break;

                    default:
                        $response['message'] = 'Unknown action';
                }
            } catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $e->getMessage();
            }
            
            echo json_encode($response);
            exit;
        }
        
        // If no POST action, show the HTML interface
        $this->showInterface();
    }

    private function showInterface() {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jstree/dist/themes/default/style.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            min-height: 100vh;
            background: #e0e5ec;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .file-manager-panel {
            background: #e0e5ec;
            border-radius: 1.5em;
            box-shadow: 8px 8px 24px #b8bac0, -8px -8px 24px #ffffff;
            margin-top: 2em;
            padding: 2em;
        }
        .jstree-default .jstree-clicked {
            background: rgba(0,123,255,0.08) !important;
            border-radius: 0.5em;
        }
        button, .btn {
            box-shadow: 4px 4px 12px #b8bac0, -4px -4px 12px #ffffff;
            border: none !important;
            margin: 0.25em;
        }
        button:hover, .btn:hover {
            box-shadow: 2px 2px 8px #b8bac0, -2px -2px 8px #ffffff;
        }
        .alert {
            border-radius: 1em;
            box-shadow: 4px 4px 12px #b8bac0, -4px -4px 12px #ffffff;
        }
        #jstree {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0.5em;
            padding: 1em;
            background: rgba(255,255,255,0.1);
        }
        .status-bar {
            margin-top: 1em;
            padding: 0.5em;
            background: rgba(255,255,255,0.1);
            border-radius: 0.5em;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="file-manager-panel">
                <h3 class="mb-4"><i class="fas fa-folder-open"></i> File Manager</h3>
                
                <div id="alert-container"></div>
                
                <div id="jstree"></div>
                
                <div class="mt-3">
                    <button class="btn btn-primary" id="create-folder">
                        <i class="fas fa-folder-plus"></i> Create Folder
                    </button>
                    <button class="btn btn-success" id="upload-file">
                        <i class="fas fa-upload"></i> Upload File
                    </button>
                    <input type="file" id="file-input" style="display:none;">
                    <button class="btn btn-warning" id="rename">
                        <i class="fas fa-edit"></i> Rename
                    </button>
                    <button class="btn btn-info" id="download">
                        <i class="fas fa-download"></i> Download
                    </button>
                    <button class="btn btn-secondary" id="copy">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                    <button class="btn btn-dark" id="move">
                        <i class="fas fa-arrows-alt"></i> Move
                    </button>
                    <button class="btn btn-danger" id="delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                
                <div class="status-bar">
                    <strong>Root Directory:</strong> <?php echo htmlspecialchars($this->root); ?>
                    <span id="selected-item" class="ms-3"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jstree/dist/jstree.min.js"></script>
<script>
$(function(){
    var selected = null, clipboard = null, clipboardAction = null;
    
    function showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#alert-container').html(alertHtml);
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
    
    function updateSelectedItem() {
        if (selected) {
            $('#selected-item').html(`<strong>Selected:</strong> ${selected.text} (${selected.type})`);
        } else {
            $('#selected-item').html('');
        }
    }
    
    // Initialize jsTree
    $('#jstree').jstree({
        'core' : {
            'data' : function(obj, cb) {
                $.post('', {action:'list', path: obj.id || ''})
                    .done(function(data) {
                        cb(data);
                    })
                    .fail(function() {
                        showAlert('Failed to load directory', 'danger');
                        cb([]);
                    });
            },
            'check_callback': true
        },
        'types' : {
            'folder' : { 'icon' : 'fas fa-folder' },
            'file' : { 'icon' : 'fas fa-file' }
        },
        'plugins' : ['types']
    }).on('select_node.jstree', function(e, data){
        selected = data.node;
        updateSelectedItem();
    });
    
    // Create folder
    $('#create-folder').click(function(){
        if (!selected || selected.type !== 'folder') {
            showAlert('Please select a folder first', 'warning');
            return;
        }
        var name = prompt('Enter folder name:');
        if (name) {
            $.post('', {action:'create_folder', parent:selected.id, name:name})
                .done(function(response) {
                    if (response.success) {
                        $('#jstree').jstree(true).refresh();
                        showAlert('Folder created successfully', 'success');
                    } else {
                        showAlert(response.message || 'Failed to create folder', 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Request failed', 'danger');
                });
        }
    });
    
    // Upload file
    $('#upload-file').click(function(){
        if (!selected || selected.type !== 'folder') {
            showAlert('Please select a folder first', 'warning');
            return;
        }
        $('#file-input').click();
    });
    
    $('#file-input').change(function(){
        var file = this.files[0];
        if (!file) return;
        
        var formData = new FormData();
        formData.append('action', 'upload');
        formData.append('parent', selected.id);
        formData.append('file', file);
        
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#jstree').jstree(true).refresh();
                    showAlert('File uploaded successfully', 'success');
                } else {
                    showAlert(response.message || 'Failed to upload file', 'danger');
                }
            },
            error: function() {
                showAlert('Upload failed', 'danger');
            }
        });
        
        // Reset file input
        $(this).val('');
    });
    
    // Rename
    $('#rename').click(function(){
        if (!selected) {
            showAlert('Please select a file or folder first', 'warning');
            return;
        }
        var name = prompt('Enter new name:', selected.text);
        if (name && name !== selected.text) {
            $.post('', {action:'rename', from:selected.id, to:name})
                .done(function(response) {
                    if (response.success) {
                        $('#jstree').jstree(true).refresh();
                        showAlert('Renamed successfully', 'success');
                    } else {
                        showAlert(response.message || 'Failed to rename', 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Request failed', 'danger');
                });
        }
    });
    
    // Download
    $('#download').click(function(){
        if (!selected || selected.type !== 'file') {
            showAlert('Please select a file first', 'warning');
            return;
        }
        
        var form = $('<form method="post" style="display:none;">');
        form.append('<input type="hidden" name="action" value="download">');
        form.append('<input type="hidden" name="file" value="' + selected.id + '">');
        $('body').append(form);
        form.submit();
        form.remove();
    });
    
    // Copy
    $('#copy').click(function(){
        if (!selected) {
            showAlert('Please select a file or folder first', 'warning');
            return;
        }
        clipboard = selected;
        clipboardAction = 'copy';
        showAlert('Item copied to clipboard. Select destination folder and click Move/Paste.', 'info');
    });
    
    // Move/Paste
    $('#move').click(function(){
        if (!selected || !clipboard) {
            showAlert('Please select a destination folder and have an item in clipboard', 'warning');
            return;
        }
        
        if (selected.type !== 'folder') {
            showAlert('Please select a folder as destination', 'warning');
            return;
        }
        
        var to = selected.id + '/' + clipboard.text;
        var action = clipboardAction === 'copy' ? 'copy' : 'move';
        
        $.post('', {action: action, from: clipboard.id, to: to})
            .done(function(response) {
                if (response.success) {
                    $('#jstree').jstree(true).refresh();
                    showAlert(action === 'copy' ? 'Copied successfully' : 'Moved successfully', 'success');
                    clipboard = null;
                    clipboardAction = null;
                } else {
                    showAlert(response.message || 'Operation failed', 'danger');
                }
            })
            .fail(function() {
                showAlert('Request failed', 'danger');
            });
    });
    
    // Delete
    $('#delete').click(function(){
        if (!selected) {
            showAlert('Please select a file or folder first', 'warning');
            return;
        }
        
        if (confirm('Are you sure you want to delete "' + selected.text + '"?')) {
            $.post('', {action:'delete', target:selected.id})
                .done(function(response) {
                    if (response.success) {
                        $('#jstree').jstree(true).refresh();
                        showAlert('Deleted successfully', 'success');
                        selected = null;
                        updateSelectedItem();
                    } else {
                        showAlert(response.message || 'Failed to delete', 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Request failed', 'danger');
                });
        }
    });
});
</script>
</body>
</html>
        <?php
    }
}

// Initialize and run the file manager
if (php_sapi_name() !== 'cli') {
    $fileManager = new FileManager();
    $fileManager->handleRequest();
}
?>