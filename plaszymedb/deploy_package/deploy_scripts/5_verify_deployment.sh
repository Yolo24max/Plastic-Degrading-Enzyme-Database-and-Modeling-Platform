#!/bin/bash
# PlaszymeDB部署验证脚本
# 验证所有服务和功能是否正常

set -e

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# 配置
PROJECT_DIR="/var/www/html/plaszymedb"
PUBLIC_IP=$(curl -s http://checkip.amazonaws.com)

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}  PlaszymeDB 部署验证${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

TESTS_PASSED=0
TESTS_FAILED=0

# 辅助函数
check_service() {
    local service=$1
    echo -n "检查 $service 服务... "
    if sudo systemctl is-active --quiet $service; then
        echo -e "${GREEN}✓ 运行中${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ 未运行${NC}"
        ((TESTS_FAILED++))
    fi
}

check_file() {
    local file=$1
    echo -n "检查文件 $file... "
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ 存在${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ 不存在${NC}"
        ((TESTS_FAILED++))
    fi
}

check_dir() {
    local dir=$1
    echo -n "检查目录 $dir... "
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✓ 存在${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ 不存在${NC}"
        ((TESTS_FAILED++))
    fi
}

# ================================================
# 1. 检查系统服务
# ================================================
echo -e "${YELLOW}[1/6] 检查系统服务...${NC}"
check_service "httpd"
check_service "mysqld"
check_service "php-fpm"
echo ""

# ================================================
# 2. 检查PHP安装
# ================================================
echo -e "${YELLOW}[2/6] 检查PHP配置...${NC}"
echo -n "PHP版本: "
php -v | head -n 1
echo -n "检查PHP扩展 (mysqli)... "
if php -m | grep -q mysqli; then
    echo -e "${GREEN}✓ 已安装${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ 未安装${NC}"
    ((TESTS_FAILED++))
fi
echo -n "检查PHP扩展 (pdo_mysql)... "
if php -m | grep -q pdo_mysql; then
    echo -e "${GREEN}✓ 已安装${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ 未安装${NC}"
    ((TESTS_FAILED++))
fi
echo ""

# ================================================
# 3. 检查项目文件
# ================================================
echo -e "${YELLOW}[3/6] 检查项目文件...${NC}"
check_file "$PROJECT_DIR/V9.html"
check_file "$PROJECT_DIR/config.php"
check_file "$PROJECT_DIR/db_config.php"
check_file "$PROJECT_DIR/search.php"
check_file "$PROJECT_DIR/detail.php"
check_file "$PROJECT_DIR/stats.php"
check_dir "$PROJECT_DIR/images"
check_dir "$PROJECT_DIR/pdb_predicted"
check_dir "$PROJECT_DIR/structure_data"
echo ""

# ================================================
# 4. 检查数据库
# ================================================
echo -e "${YELLOW}[4/6] 检查数据库...${NC}"
echo -n "检查数据库连接... "
if sudo mysql -u plaszymedb_user -p'PlaszymeDB@2025!' -e "USE plaszymedb;" 2>/dev/null; then
    echo -e "${GREEN}✓ 成功${NC}"
    ((TESTS_PASSED++))
    
    # 检查表
    echo -n "检查数据表... "
    TABLE_COUNT=$(sudo mysql -u plaszymedb_user -p'PlaszymeDB@2025!' plaszymedb -e "SHOW TABLES;" -s | wc -l)
    if [ "$TABLE_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓ 找到 $TABLE_COUNT 个表${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ 未找到表${NC}"
        ((TESTS_FAILED++))
    fi
    
    # 检查数据
    echo -n "检查数据记录... "
    RECORD_COUNT=$(sudo mysql -u plaszymedb_user -p'PlaszymeDB@2025!' plaszymedb -e "SELECT COUNT(*) FROM PlaszymeDB;" -s 2>/dev/null || echo "0")
    if [ "$RECORD_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓ 找到 $RECORD_COUNT 条记录${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ 未找到数据${NC}"
        ((TESTS_FAILED++))
    fi
else
    echo -e "${RED}✗ 失败${NC}"
    ((TESTS_FAILED++))
fi
echo ""

# ================================================
# 5. 检查文件权限
# ================================================
echo -e "${YELLOW}[5/6] 检查文件权限...${NC}"
echo -n "检查项目目录所有者... "
OWNER=$(stat -c '%U:%G' $PROJECT_DIR)
if [ "$OWNER" = "apache:apache" ]; then
    echo -e "${GREEN}✓ $OWNER${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${YELLOW}⚠ $OWNER (期望: apache:apache)${NC}"
fi
echo ""

# ================================================
# 6. HTTP端点测试
# ================================================
echo -e "${YELLOW}[6/6] 测试HTTP端点...${NC}"
echo -n "测试主页 (V9.html)... "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/plaszymedb/V9.html")
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ HTTP $HTTP_CODE${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ HTTP $HTTP_CODE${NC}"
    ((TESTS_FAILED++))
fi

echo -n "测试搜索API (search.php)... "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/plaszymedb/search.php")
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ HTTP $HTTP_CODE${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ HTTP $HTTP_CODE${NC}"
    ((TESTS_FAILED++))
fi

echo -n "测试统计API (stats.php)... "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/plaszymedb/stats.php")
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ HTTP $HTTP_CODE${NC}"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ HTTP $HTTP_CODE${NC}"
    ((TESTS_FAILED++))
fi
echo ""

# ================================================
# 测试总结
# ================================================
TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))
echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}  测试总结${NC}"
echo -e "${CYAN}================================================${NC}"
echo -e "总测试数: $TOTAL_TESTS"
echo -e "${GREEN}通过: $TESTS_PASSED${NC}"
if [ $TESTS_FAILED -gt 0 ]; then
    echo -e "${RED}失败: $TESTS_FAILED${NC}"
fi
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ 所有测试通过！部署成功！${NC}"
    echo ""
    echo -e "${YELLOW}访问网站:${NC}"
    echo -e "  本地测试: ${CYAN}http://localhost/plaszymedb/V9.html${NC}"
    echo -e "  公网访问: ${CYAN}http://$PUBLIC_IP/plaszymedb/V9.html${NC}"
    echo -e "  域名访问: ${CYAN}http://plaszyme.org/plaszymedb${NC}"
    echo ""
    exit 0
else
    echo -e "${RED}✗ 部分测试失败，请检查错误日志${NC}"
    echo ""
    echo -e "${YELLOW}查看错误日志:${NC}"
    echo -e "  Apache错误: ${CYAN}sudo tail -f /var/log/httpd/error_log${NC}"
    echo -e "  PlaszymeDB错误: ${CYAN}sudo tail -f $PROJECT_DIR/logs/tree_access.log${NC}"
    echo ""
    exit 1
fi

