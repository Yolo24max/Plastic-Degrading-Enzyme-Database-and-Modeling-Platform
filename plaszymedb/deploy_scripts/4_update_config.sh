#!/bin/bash
# 更新PlaszymeDB配置文件脚本
# 在EC2上执行，自动更新数据库配置

set -e

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# 配置参数
PROJECT_DIR="/var/www/html/plaszymedb"
DB_HOST="localhost"
DB_NAME="plaszymedb"
DB_USER="plaszymedb_user"
DB_PASS="PlaszymeDB@2025!"

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}  更新PlaszymeDB配置文件${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

# 检查项目目录是否存在
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}错误: 项目目录不存在: $PROJECT_DIR${NC}"
    exit 1
fi

# ================================================
# 更新config.php
# ================================================
echo -e "${YELLOW}[1/2] 更新config.php...${NC}"

sudo tee $PROJECT_DIR/config.php > /dev/null <<'EOF'
<?php
/**
 * PlaszymeDB 数据库配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'plaszymedb_user');
define('DB_PASS', 'PlaszymeDB@2025!');
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
date_default_timezone_set('UTC');

// 生产环境配置
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
?>
EOF

echo -e "${GREEN}✓ config.php更新完成${NC}\n"

# ================================================
# 更新db_config.php
# ================================================
echo -e "${YELLOW}[2/2] 更新db_config.php...${NC}"

sudo tee $PROJECT_DIR/db_config.php > /dev/null <<'EOF'
<?php
// 数据库配置文件
// AWS生产环境配置

// 数据库服务器配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'plaszymedb_user');
define('DB_PASS', 'PlaszymeDB@2025!');
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
EOF

echo -e "${GREEN}✓ db_config.php更新完成${NC}\n"

# ================================================
# 设置权限
# ================================================
echo -e "${YELLOW}设置文件权限...${NC}"
sudo chown apache:apache $PROJECT_DIR/config.php
sudo chown apache:apache $PROJECT_DIR/db_config.php
sudo chmod 644 $PROJECT_DIR/config.php
sudo chmod 644 $PROJECT_DIR/db_config.php
echo -e "${GREEN}✓ 权限设置完成${NC}\n"

# ================================================
# 完成
# ================================================
echo -e "${CYAN}================================================${NC}"
echo -e "${GREEN}  配置文件更新完成！${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""
echo -e "${YELLOW}数据库连接信息:${NC}"
echo -e "  主机: ${CYAN}$DB_HOST${NC}"
echo -e "  数据库: ${CYAN}$DB_NAME${NC}"
echo -e "  用户: ${CYAN}$DB_USER${NC}"
echo -e "  密码: ${CYAN}$DB_PASS${NC}"
echo ""
echo -e "${YELLOW}下一步: 测试数据库连接${NC}"
echo -e "${CYAN}php -r \"require '$PROJECT_DIR/config.php'; getDbConnection(); echo 'Database connection successful!\n';\"${NC}"
echo ""

