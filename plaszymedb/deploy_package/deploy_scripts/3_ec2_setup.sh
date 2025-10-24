#!/bin/bash
# PlaszymeDB EC2自动部署脚本
# 在EC2实例上执行此脚本

set -e  # 遇到错误立即退出

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 配置参数
DB_NAME="plaszymedb"
DB_USER="plaszymedb_user"
DB_PASS="PlaszymeDB@2025!"
MYSQL_ROOT_PASS=""  # 将在安装MySQL后设置

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}  PlaszymeDB EC2 自动部署脚本${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

# ================================================
# 阶段1: 系统更新
# ================================================
echo -e "${YELLOW}[1/10] 更新系统...${NC}"
sudo dnf update -y
echo -e "${GREEN}✓ 系统更新完成${NC}\n"

# ================================================
# 阶段2: 安装Apache
# ================================================
echo -e "${YELLOW}[2/10] 安装Apache Web服务器...${NC}"
sudo dnf install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd
echo -e "${GREEN}✓ Apache安装完成${NC}\n"

# ================================================
# 阶段3: 安装PHP
# ================================================
echo -e "${YELLOW}[3/10] 安装PHP及扩展...${NC}"
sudo dnf install php php-mysqlnd php-pdo php-mbstring php-json php-xml php-fpm -y
php -v
echo -e "${GREEN}✓ PHP安装完成${NC}\n"

# ================================================
# 阶段4: 配置PHP
# ================================================
echo -e "${YELLOW}[4/10] 配置PHP...${NC}"
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php.ini
echo -e "${GREEN}✓ PHP配置完成${NC}\n"

# ================================================
# 阶段5: 安装MySQL
# ================================================
echo -e "${YELLOW}[5/10] 安装MySQL服务器...${NC}"
sudo dnf install mysql-server -y
sudo systemctl start mysqld
sudo systemctl enable mysqld
echo -e "${GREEN}✓ MySQL安装完成${NC}\n"

# ================================================
# 阶段6: 配置防火墙
# ================================================
echo -e "${YELLOW}[6/10] 配置防火墙...${NC}"
sudo systemctl start firewalld
sudo systemctl enable firewalld
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
echo -e "${GREEN}✓ 防火墙配置完成${NC}\n"

# ================================================
# 阶段7: 创建项目目录
# ================================================
echo -e "${YELLOW}[7/10] 创建项目目录...${NC}"
sudo mkdir -p /var/www/html/plaszymedb
sudo mkdir -p /var/www/html/plaszymedb/logs
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
sudo chmod -R 777 /var/www/html/plaszymedb/logs
echo -e "${GREEN}✓ 项目目录创建完成${NC}\n"

# ================================================
# 阶段8: 配置Apache虚拟主机
# ================================================
echo -e "${YELLOW}[8/10] 配置Apache虚拟主机...${NC}"
sudo tee /etc/httpd/conf.d/plaszymedb.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName plaszyme.org
    DocumentRoot /var/www/html
    
    <Directory /var/www/html/plaszymedb>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex V9.html index.php index.html
    </Directory>
    
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog /var/log/httpd/plaszymedb_error.log
    CustomLog /var/log/httpd/plaszymedb_access.log combined
</VirtualHost>
EOF
echo -e "${GREEN}✓ Apache虚拟主机配置完成${NC}\n"

# ================================================
# 阶段9: 启动PHP-FPM
# ================================================
echo -e "${YELLOW}[9/10] 启动PHP-FPM...${NC}"
sudo systemctl start php-fpm
sudo systemctl enable php-fpm
echo -e "${GREEN}✓ PHP-FPM启动完成${NC}\n"

# ================================================
# 阶段10: 重启服务
# ================================================
echo -e "${YELLOW}[10/10] 重启Web服务...${NC}"
sudo systemctl restart httpd
sudo systemctl restart php-fpm
echo -e "${GREEN}✓ 服务重启完成${NC}\n"

# ================================================
# 完成信息
# ================================================
echo -e "${CYAN}================================================${NC}"
echo -e "${GREEN}  LAMP环境安装完成！${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""
echo -e "${YELLOW}下一步操作:${NC}"
echo -e "1. 设置MySQL root密码:"
echo -e "   ${CYAN}sudo mysql_secure_installation${NC}"
echo ""
echo -e "2. 上传项目文件到 /var/www/html/plaszymedb"
echo ""
echo -e "3. 创建数据库并导入数据:"
echo -e "   ${CYAN}sudo mysql -u root -p < /var/www/html/plaszymedb/deploy_scripts/2_setup_database.sql${NC}"
echo -e "   ${CYAN}sudo mysql -u root -p plaszymedb < /var/www/html/plaszymedb/plaszymedb_backup.sql${NC}"
echo ""
echo -e "4. 更新数据库配置文件:"
echo -e "   ${CYAN}sudo nano /var/www/html/plaszymedb/config.php${NC}"
echo -e "   ${CYAN}sudo nano /var/www/html/plaszymedb/db_config.php${NC}"
echo ""
echo -e "5. 测试网站:"
echo -e "   ${CYAN}http://$(curl -s http://checkip.amazonaws.com)/plaszymedb/V9.html${NC}"
echo ""
echo -e "${GREEN}祝部署顺利！${NC}"
echo ""

