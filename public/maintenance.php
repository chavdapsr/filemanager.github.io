<?php
/**
 * File Manager Maintenance Script
 * 
 * Features:
 * - Clean up old files
 * - Rotate log files
 * - Check system health
 * - Generate maintenance reports
 * - Optimize database (if applicable)
 * - Security checks
 */

// Security: Only allow access from command line or with proper authentication
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_X_MAINTENANCE_KEY'])) {
    http_response_code(403);
    exit('Access denied');
}

// Configuration
class MaintenanceConfig {
    const LOG_DIR = __DIR__ . '/logs/';
    const UPLOAD_DIR = __DIR__ . '/uploads/';
    const MAX_LOG_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_LOG_AGE = 30; // days
    const MAX_FILE_AGE = 90; // days
    const CLEANUP_BATCH_SIZE = 100;
    const MAINTENANCE_KEY = 'your-secret-maintenance-key'; // Change this!
}

// Maintenance class
class Maintenance {
    private $logFile;
    private $report = [];
    
    public function __construct() {
        $this->logFile = MaintenanceConfig::LOG_DIR . 'maintenance.log';
        $this->ensureLogDir();
    }
    
    private function ensureLogDir() {
        if (!is_dir(MaintenanceConfig::LOG_DIR)) {
            mkdir(MaintenanceConfig::LOG_DIR, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        $this->report[] = "[$level] $message";
    }
    
    /**
     * Run all maintenance tasks
     */
    public function runMaintenance() {
        $this->log("Starting maintenance routine");
        
        try {
            $this->cleanupOldFiles();
            $this->rotateLogFiles();
            $this->checkSystemHealth();
            $this->securityCheck();
            $this->generateReport();
            
            $this->log("Maintenance completed successfully");
            return true;
        } catch (Exception $e) {
            $this->log("Maintenance failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Clean up old files
     */
    private function cleanupOldFiles() {
        $this->log("Starting file cleanup");
        
        $cutoff = time() - (MaintenanceConfig::MAX_FILE_AGE * 24 * 60 * 60);
        $deletedCount = 0;
        $deletedSize = 0;
        
        if (!is_dir(MaintenanceConfig::UPLOAD_DIR)) {
            $this->log("Upload directory does not exist");
            return;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(MaintenanceConfig::UPLOAD_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoff) {
                $size = $file->getSize();
                if (unlink($file->getPathname())) {
                    $deletedCount++;
                    $deletedSize += $size;
                    
                    if ($deletedCount % MaintenanceConfig::CLEANUP_BATCH_SIZE === 0) {
                        $this->log("Processed $deletedCount files");
                    }
                }
            }
        }
        
        $this->log("File cleanup completed: $deletedCount files deleted, " . $this->formatBytes($deletedSize) . " freed");
    }
    
    /**
     * Rotate log files
     */
    private function rotateLogFiles() {
        $this->log("Starting log rotation");
        
        $logFiles = glob(MaintenanceConfig::LOG_DIR . '*.log');
        $rotatedCount = 0;
        
        foreach ($logFiles as $logFile) {
            if (is_file($logFile) && filesize($logFile) > MaintenanceConfig::MAX_LOG_SIZE) {
                $backupFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
                if (rename($logFile, $backupFile)) {
                    $rotatedCount++;
                    $this->log("Rotated log file: " . basename($logFile));
                }
            }
        }
        
        // Clean up old backup files
        $backupFiles = glob(MaintenanceConfig::LOG_DIR . '*.bak');
        $cutoff = time() - (MaintenanceConfig::MAX_LOG_AGE * 24 * 60 * 60);
        
        foreach ($backupFiles as $backupFile) {
            if (filemtime($backupFile) < $cutoff) {
                unlink($backupFile);
                $this->log("Removed old backup: " . basename($backupFile));
            }
        }
        
        $this->log("Log rotation completed: $rotatedCount files rotated");
    }
    
    /**
     * Check system health
     */
    private function checkSystemHealth() {
        $this->log("Starting system health check");
        
        $health = [
            'disk_usage' => $this->checkDiskUsage(),
            'memory_usage' => $this->checkMemoryUsage(),
            'upload_dir_writable' => is_writable(MaintenanceConfig::UPLOAD_DIR),
            'log_dir_writable' => is_writable(MaintenanceConfig::LOG_DIR),
            'php_version' => PHP_VERSION,
            'extensions' => $this->checkRequiredExtensions()
        ];
        
        foreach ($health as $check => $result) {
            if ($result === true) {
                $this->log("Health check passed: $check");
            } elseif ($result === false) {
                $this->log("Health check failed: $check", 'WARNING');
            } else {
                $this->log("Health check result: $check = $result");
            }
        }
        
        $this->log("System health check completed");
    }
    
    /**
     * Check disk usage
     */
    private function checkDiskUsage() {
        $uploadDir = MaintenanceConfig::UPLOAD_DIR;
        if (!is_dir($uploadDir)) return 'Directory not found';
        
        $totalSpace = disk_total_space($uploadDir);
        $freeSpace = disk_free_space($uploadDir);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;
        
        return [
            'total' => $this->formatBytes($totalSpace),
            'free' => $this->formatBytes($freeSpace),
            'used' => $this->formatBytes($usedSpace),
            'percent' => round($usagePercent, 2)
        ];
    }
    
    /**
     * Check memory usage
     */
    private function checkMemoryUsage() {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        
        return [
            'limit' => $memoryLimit,
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes($peakUsage)
        ];
    }
    
    /**
     * Check required PHP extensions
     */
    private function checkRequiredExtensions() {
        $required = ['fileinfo', 'json', 'mbstring'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        return empty($missing) ? true : $missing;
    }
    
    /**
     * Security check
     */
    private function securityCheck() {
        $this->log("Starting security check");
        
        $issues = [];
        
        // Check file permissions
        $uploadDir = MaintenanceConfig::UPLOAD_DIR;
        if (is_dir($uploadDir)) {
            $perms = fileperms($uploadDir) & 0777;
            if ($perms > 0755) {
                $issues[] = "Upload directory permissions too open: " . decoct($perms);
            }
        }
        
        // Check for suspicious files
        $suspiciousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'cgi'];
        $files = glob(MaintenanceConfig::UPLOAD_DIR . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $suspiciousExtensions)) {
                    $issues[] = "Suspicious file found: " . basename($file);
                }
            }
        }
        
        // Check log file size
        if (is_file($this->logFile) && filesize($this->logFile) > 50 * 1024 * 1024) {
            $issues[] = "Log file too large: " . $this->formatBytes(filesize($this->logFile));
        }
        
        if (empty($issues)) {
            $this->log("Security check passed");
        } else {
            foreach ($issues as $issue) {
                $this->log("Security issue: $issue", 'WARNING');
            }
        }
    }
    
    /**
     * Generate maintenance report
     */
    private function generateReport() {
        $reportFile = MaintenanceConfig::LOG_DIR . 'maintenance_report_' . date('Y-m-d_H-i-s') . '.txt';
        
        $reportContent = "File Manager Maintenance Report\n";
        $reportContent .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $reportContent .= "================================\n\n";
        
        foreach ($this->report as $entry) {
            $reportContent .= $entry . "\n";
        }
        
        file_put_contents($reportFile, $reportContent);
        $this->log("Maintenance report generated: " . basename($reportFile));
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $maintenance = new Maintenance();
    $success = $maintenance->runMaintenance();
    exit($success ? 0 : 1);
}

// Web interface (with authentication)
if (isset($_SERVER['HTTP_X_MAINTENANCE_KEY']) && 
    $_SERVER['HTTP_X_MAINTENANCE_KEY'] === MaintenanceConfig::MAINTENANCE_KEY) {
    
    $maintenance = new Maintenance();
    $success = $maintenance->runMaintenance();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $success ? 'Maintenance completed successfully' : 'Maintenance failed'
    ]);
    exit;
}

// Default response
http_response_code(403);
echo 'Access denied';
?> 