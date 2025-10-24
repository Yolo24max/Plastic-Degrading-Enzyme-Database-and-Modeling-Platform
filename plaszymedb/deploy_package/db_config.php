<?php
// 数据库配置文件
// 请根据您的数据库设置修改以下参数

// 数据库服务器配置
define('DB_HOST', 'localhost:3307');  // 如果改了端口
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'root');
define('DB_PASS', 'yoloShang2025');  // 请输入您的数据库密码
define('DB_CHARSET', 'utf8mb4');

// 连接选项
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// 获取数据库连接
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        return new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}
?>
