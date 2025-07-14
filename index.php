<?php
// Start session and set page title
session_start();
$page_title = "Dashboard";

// Include all required modules
require_once 'config/database.php';
require_once 'classes/filemanager.php';
require_once 'data/mockdata.php';
require_once 'templates/conn.php';
require_once 'templates/delete.php';
require_once 'templates/files.php';
require_once 'templates/uploads.php';
require_once 'templates/viewfiles.php';

// Get file stats for dashboard widgets
$fileManager = new FileManager();
$stats = $fileManager->getFileStats($fileManager->root ?? __DIR__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | File Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fa; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #232946; color: #fff; min-height: 100vh; transition: width 0.2s; }
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .header { background: #fff; padding: 1.2rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; }
        .content-area { flex: 1; padding: 2rem; }
        .dashboard-widgets { display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 2rem; }
        .widget { background: #fff; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 2rem; flex: 1 1 220px; min-width: 220px; display: flex; align-items: center; gap: 1.2rem; }
        .widget .icon { font-size: 2.2rem; color: #232946; background: #eebbc3; border-radius: 50%; padding: 0.7rem; }
        .widget .info { }
        .widget .info .value { font-size: 1.5rem; font-weight: bold; }
        .widget .info .label { color: #888; font-size: 1rem; }
        .dashboard-section { background: #fff; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 2rem; margin-bottom: 2rem; }
        @media (max-width: 900px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; min-height: unset; }
            .dashboard-widgets { flex-direction: column; gap: 1rem; }
        }
        @media (max-width: 600px) {
            .content-area { padding: 1rem; }
            .header { padding: 1rem; }
            .dashboard-section, .widget { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <?php include 'includes/header.php'; ?>
            </header>
            <!-- Main Content Area -->
            <div class="content-area">
                <!-- Dashboard Widgets -->
                <div class="dashboard-widgets">
                    <div class="widget">
                        <div class="icon"><i class="fas fa-folder"></i></div>
                        <div class="info">
                            <div class="value"><?php echo isset($stats['size']) ? number_format($stats['size']/1024, 2) . ' KB' : '--'; ?></div>
                            <div class="label">Root Folder Size</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <div class="info">
                            <div class="value"><?php echo isset($stats['modified']) ? date('M d, Y', $stats['modified']) : '--'; ?></div>
                            <div class="label">Last Modified</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <div class="info">
                            <div class="value">Admin</div>
                            <div class="label">User Role</div>
                        </div>
                    </div>
                    <div class="widget">
                        <div class="icon"><i class="fas fa-database"></i></div>
                        <div class="info">
                            <div class="value">Connected</div>
                            <div class="label">Database</div>
                        </div>
                    </div>
                </div>
                <!-- Main Dashboard Section -->
                <div class="dashboard-section">
                    <?php include 'includes/maincontent.php'; ?>
                </div>
                <!-- File Manager Section -->
                <div class="dashboard-section">
                    <?php 
                        $fileManager = new FileManager();
                        $fileManager->handleRequest();
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="public/assets/js/script.js"></script>
</body>
</html>
