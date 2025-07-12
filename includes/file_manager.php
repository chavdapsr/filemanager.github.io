<?php
// --- CONFIG ---
$root = realpath('../'); // Set to your project root
$startDir = isset($_GET['dir']) ? $_GET['dir'] : $root;
$dir = realpath($startDir);
if (!$dir || strpos($dir, $root) !== 0) $dir = $root; // Prevent directory traversal

// --- Handle AJAX Actions ---
header('Access-Control-Allow-Origin: *');
if (isset($_POST['action'])) {
    $response = ['success' => false];
    switch ($_POST['action']) {
        case 'list':
            $path = realpath($_POST['path'] ?? $root);
            if (!$path || strpos($path, $root) !== 0) $path = $root;
            $items = scandir($path);
            $result = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $itemPath = $path . DIRECTORY_SEPARATOR . $item;
                $result[] = [
                    'text' => $item,
                    'type' => is_dir($itemPath) ? 'folder' : 'file',
                    'children' => is_dir($itemPath),
                    'id' => $itemPath
                ];
            }
            $response = $result;
            break;
        case 'create_folder':
            $parent = realpath($_POST['parent'] ?? $root);
            $name = trim($_POST['name']);
            if ($parent && $name && strpos($parent, $root) === 0) {
                $newPath = $parent . DIRECTORY_SEPARATOR . $name;
                $response['success'] = mkdir($newPath);
            }
            break;
        case 'rename':
            $from = realpath($_POST['from']);
            $to = dirname($from) . DIRECTORY_SEPARATOR . $_POST['to'];
            if ($from && strpos($from, $root) === 0) {
                $response['success'] = rename($from, $to);
            }
            break;
        case 'delete':
            $target = realpath($_POST['target']);
            if ($target && strpos($target, $root) === 0) {
                if (is_dir($target)) {
                    $response['success'] = rmdir($target);
                } else {
                    $response['success'] = unlink($target);
                }
            }
            break;
        case 'upload':
            $parent = realpath($_POST['parent'] ?? $root);
            if ($parent && isset($_FILES['file']) && strpos($parent, $root) === 0) {
                $target = $parent . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
                $response['success'] = move_uploaded_file($_FILES['file']['tmp_name'], $target);
            }
            break;
        case 'download':
            $file = realpath($_POST['file']);
            if ($file && is_file($file) && strpos($file, $root) === 0) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($file));
                readfile($file);
                exit;
            }
            break;
        case 'copy':
            $from = realpath($_POST['from']);
            $to = $_POST['to'];
            if ($from && strpos($from, $root) === 0 && $to && strpos(dirname($to), $root) === 0) {
                $response['success'] = copy($from, $to);
            }
            break;
        case 'move':
            $from = realpath($_POST['from']);
            $to = $_POST['to'];
            if ($from && strpos($from, $root) === 0 && $to && strpos(dirname($to), $root) === 0) {
                $response['success'] = rename($from, $to);
            }
            break;
    }
    echo json_encode($response);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jstree/dist/themes/default/style.min.css" />
    <style>
        body {
            min-height: 100vh;
            background: #e0e5ec;
            /* Neumorphism background */
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
        }
    </style>
</head>
<body>
<div class="container file-manager-panel p-4">
    <h3 class="mb-4">Project File Manager</h3>
    <div id="jstree"></div>
    <div class="mt-3">
        <button class="btn btn-primary" id="create-folder">Create Folder</button>
        <button class="btn btn-success" id="upload-file">Upload File</button>
        <input type="file" id="file-input" style="display:none;">
        <button class="btn btn-danger" id="delete">Delete</button>
        <button class="btn btn-warning" id="rename">Rename</button>
        <button class="btn btn-info" id="download">Download</button>
        <button class="btn btn-secondary" id="copy">Copy</button>
        <button class="btn btn-dark" id="move">Move</button>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jstree/dist/jstree.min.js"></script>
<script>
$(function(){
    var selected = null, clipboard = null, clipboardAction = null;
    $('#jstree').jstree({
        'core' : {
            'data' : function(obj, cb) {
                $.post('', {action:'list', path: obj.id || ''}, cb, 'json');
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
    });
    $('#create-folder').click(function(){
        if (!selected || selected.type !== 'folder') return alert('Select a folder');
        var name = prompt('Folder name:');
        if (name) $.post('', {action:'create_folder', parent:selected.id, name:name}, function(){ $('#jstree').jstree(true).refresh(); });
    });
    $('#upload-file').click(function(){
        if (!selected || selected.type !== 'folder') return alert('Select a folder');
        $('#file-input').click();
    });
    $('#file-input').change(function(){
        var file = this.files[0];
        if (!file) return;
        var form = new FormData();
        form.append('action','upload');
        form.append('parent',selected.id);
        form.append('file',file);
        $.ajax({url:'',type:'POST',data:form,processData:false,contentType:false,success:function(){ $('#jstree').jstree(true).refresh(); }});
    });
    $('#delete').click(function(){
        if (!selected) return alert('Select a file or folder');
        if (confirm('Delete '+selected.text+'?'))
            $.post('', {action:'delete', target:selected.id}, function(){ $('#jstree').jstree(true).refresh(); });
    });
    $('#rename').click(function(){
        if (!selected) return alert('Select a file or folder');
        var name = prompt('New name:', selected.text);
        if (name && name !== selected.text)
            $.post('', {action:'rename', from:selected.id, to:name}, function(){ $('#jstree').jstree(true).refresh(); });
    });
    $('#download').click(function(){
        if (!selected || selected.type !== 'file') return alert('Select a file');
        var form = $('<form method="post"><input type="hidden" name="action" value="download"><input type="hidden" name="file" value="'+selected.id+'"></form>');
        $('body').append(form); form.submit();
    });
    $('#copy').click(function(){
        if (!selected) return alert('Select a file or folder');
        clipboard = selected; clipboardAction = 'copy';
        alert('Copied! Now select destination folder and click Move or Copy.');
    });
    $('#move').click(function(){
        if (!selected || !clipboard) return alert('Select a destination and something to move/copy.');
        var to = selected.id + '/' + clipboard.text;
        if (clipboardAction === 'copy')
            $.post('', {action:'copy', from:clipboard.id, to:to}, function(){ $('#jstree').jstree(true).refresh(); });
        else
            $.post('', {action:'move', from:clipboard.id, to:to}, function(){ $('#jstree').jstree(true).refresh(); });
        clipboard = null; clipboardAction = null;
    });
});
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</body>
</html>
