<?php
/**
 * PlaszymeDB 数据库配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'root');
define('DB_PASS', 'yoloShang2025');
define('DB_CHARSET', 'utf8mb4');

/**
 * 获取数据库连接
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * 错误处理函数
 */
function handleError($error) {
    error_log("PlaszymeDB Error: " . $error);
    return [
        'success' => false,
        'error' => $error
    ];
}

/**
 * 成功响应函数
 */
function successResponse($data) {
    return array_merge(['success' => true], $data);
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置错误报告级别（生产环境中应关闭）
ini_set('display_errors', 1);  // 临时启用用于调试
ini_set('log_errors', 1);
error_reporting(E_ALL);
?>
