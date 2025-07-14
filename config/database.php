<?php
/**
 * Database Configuration and Connection Class
 * This file handles database connection and common database operations
 */

class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;
    private $charset;
    
    public function __construct($config = null) {
        // Default configuration - modify these values according to your setup
        $this->host = $config['host'] ?? 'localhost';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';
        $this->database = $config['database'] ?? 'filemanager_db';
        $this->charset = $config['charset'] ?? 'utf8mb4';
    }
    
    /**
     * Create database connection
     */
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            return $this->connection;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Initialize database tables for file manager
     */
    public function initializeTables() {
        $connection = $this->getConnection();
        
        try {
            // Create users table
            $userTableSQL = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'user') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            // Create file_activities table for logging
            $activityTableSQL = "
                CREATE TABLE IF NOT EXISTS file_activities (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action VARCHAR(50) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    file_size BIGINT DEFAULT 0,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            
            // Create sessions table
            $sessionTableSQL = "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    user_id INT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            // Create file_shares table
            $shareTableSQL = "
                CREATE TABLE IF NOT EXISTS file_shares (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    file_path VARCHAR(500) NOT NULL,
                    share_token VARCHAR(64) UNIQUE NOT NULL,
                    expires_at TIMESTAMP NULL,
                    download_count INT DEFAULT 0,
                    max_downloads INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            
            $connection->exec($userTableSQL);
            $connection->exec($activityTableSQL);
            $connection->exec($sessionTableSQL);
            $connection->exec($shareTableSQL);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Failed to initialize tables: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $this->execute($sql, $data);
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $setClause = implode(', ', array_map(function($field) {
            return "{$field} = :{$field}";
        }, $fields));
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->execute($sql, $params);
    }
    
    /**
     * Delete record
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->execute($sql, $params);
    }
    
    /**
     * Select records
     */
    public function select($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Select single record
     */
    public function selectOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Log file activity
     */
    public function logActivity($userId, $action, $filePath, $fileName, $fileSize = 0) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        return $this->insert('file_activities', $data);
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username";
        return $this->selectOne($sql, ['username' => $username]);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        return $this->selectOne($sql, ['id' => $id]);
    }
    
    /**
     * Create new user
     */
    public function createUser($username, $email, $password, $role = 'user') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ];
        
        return $this->insert('users', $data);
    }
    
    /**
     * Get file activities
     */
    public function getFileActivities($userId = null, $limit = 50) {
        $sql = "SELECT fa.*, u.username 
                FROM file_activities fa 
                LEFT JOIN users u ON fa.user_id = u.id";
        
        $params = [];
        if ($userId) {
            $sql .= " WHERE fa.user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY fa.created_at DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        return $this->select($sql, $params);
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        return $this->execute($sql);
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }
}

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'filemanager_db',
    'charset' => 'utf8mb4'
];

// Create global database instance
$database = new Database($dbConfig);

// Initialize database tables on first run
try {
    $database->initializeTables();
} catch (Exception $e) {
    // Log error or handle as needed
    error_log("Database initialization error: " . $e->getMessage());
}

/**
 * Helper function to get database instance
 */
function getDatabase() {
    global $database;
    return $database;
}

/**
 * Helper function to get current user ID from session
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Helper function to check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Helper function to log file activity
 */
function logFileActivity($action, $filePath, $fileName, $fileSize = 0) {
    $database = getDatabase();
    $userId = getCurrentUserId();
    
    if ($userId) {
        $database->logActivity($userId, $action, $filePath, $fileName, $fileSize);
    }
}
?>