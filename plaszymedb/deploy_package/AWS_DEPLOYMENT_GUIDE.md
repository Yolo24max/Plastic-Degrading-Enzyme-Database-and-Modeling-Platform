# PlaszymeDB AWS EC2 部署完整指南

## 📋 项目概况
- **技术栈**: PHP 7.4+ / MySQL 8.0+ / Apache (或 Nginx)
- **EC2实例**: ec2-44-192-47-171.compute-1.amazonaws.com
- **目标URL**: http://plaszyme.org/plaszymedb
- **操作系统**: Amazon Linux 2023

---

## 🚀 完整部署步骤

### 阶段 1: 准备本地文件

#### 1.1 导出MySQL数据库
```bash
# 在本地Windows环境执行
cd C:\xampp\mysql\bin
.\mysqldump.exe -u root -pyoloShang2025 --port=3307 plaszymedb > C:\xampp\htdocs\plaszymedb\plaszymedb_backup.sql
```

#### 1.2 打包项目文件（排除不必要的文件）
在本地PowerShell执行：
```powershell
cd C:\xampp\htdocs\plaszymedb

# 创建部署目录
New-Item -ItemType Directory -Force -Path deploy_package

# 复制必要文件（排除测试文件和文档）
$excludePatterns = @(
    'test_*.php',
    'test_*.html',
    '*.md',
    'deploy_scripts',
    'logs',
    '.git',
    'viewer-docs-master'
)

# 手动压缩或使用7-Zip
# 推荐使用WinRAR或7-Zip压缩整个文件夹为 plaszymedb.zip
```

**重要文件清单**：
- ✅ V9.html（主页）
- ✅ 所有 *.php 文件
- ✅ images/ 目录
- ✅ pdb_predicted/ 目录（749个PDB文件）
- ✅ pdb_files/ 目录
- ✅ structure_data/ 目录
- ✅ phylogeny_data/ 目录
- ✅ plastic_smiles_cleaned.csv
- ✅ PlaszymeDB_v1.1.csv
- ✅ protein_viewer_optimized.js
- ✅ protein_3d_viewer.js
- ✅ plaszymedb_backup.sql（数据库备份）

---

### 阶段 2: 配置EC2实例（LAMP环境）

#### 2.1 连接到EC2并更新系统
```bash
ssh -i "D:\wangshang.pem" ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com

# 更新系统
sudo dnf update -y
```

#### 2.2 安装Apache Web服务器
```bash
# 安装Apache
sudo dnf install httpd -y

# 启动Apache
sudo systemctl start httpd
sudo systemctl enable httpd

# 检查状态
sudo systemctl status httpd
```

#### 2.3 安装PHP 8.x
```bash
# Amazon Linux 2023 默认PHP版本检查
php -v

# 如果没有，安装PHP及扩展
sudo dnf install php php-mysqlnd php-pdo php-mbstring php-json php-xml -y

# 验证安装
php -v

# 重启Apache以加载PHP
sudo systemctl restart httpd
```

#### 2.4 安装MySQL 8.0
```bash
# 安装MySQL服务器
sudo dnf install mysql-server -y

# 启动MySQL
sudo systemctl start mysqld
sudo systemctl enable mysqld

# 检查状态
sudo systemctl status mysqld

# 安全配置MySQL
sudo mysql_secure_installation
```

**MySQL安全配置提示**：
- Root密码：**设置一个强密码**（建议：PlaszymeDB@2025）
- 删除匿名用户：Y
- 禁止root远程登录：Y
- 删除测试数据库：Y
- 重新加载权限表：Y

---

### 阶段 3: 上传项目文件

#### 3.1 使用SCP上传文件（在本地Windows PowerShell执行）

**方法1：使用WinSCP（推荐）**
1. 下载安装WinSCP: https://winscp.net/
2. 连接配置：
   - 主机名: ec2-44-192-47-171.compute-1.amazonaws.com
   - 用户名: ec2-user
   - 私钥: D:\wangshang.pem（需要转换为PuTTY格式.ppk）
3. 上传整个项目文件夹到 `/home/ec2-user/plaszymedb`

**方法2：使用SCP命令**
```powershell
# 在本地PowerShell执行
cd C:\xampp\htdocs

# 上传压缩文件
scp -i "D:\wangshang.pem" plaszymedb.zip ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com:~/
```

#### 3.2 在EC2上解压并移动文件
```bash
# SSH连接到EC2
ssh -i "D:\wangshang.pem" ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com

# 安装unzip（如果需要）
sudo dnf install unzip -y

# 解压文件
cd ~
unzip plaszymedb.zip

# 移动到Apache网站根目录
sudo mkdir -p /var/www/html/plaszymedb
sudo cp -r plaszymedb/* /var/www/html/plaszymedb/

# 设置正确的权限
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb

# 为日志目录设置写权限
sudo mkdir -p /var/www/html/plaszymedb/logs
sudo chmod -R 777 /var/www/html/plaszymedb/logs
```

---

### 阶段 4: 配置数据库

#### 4.1 创建数据库和用户
```bash
# 登录MySQL
sudo mysql -u root -p

# 输入之前设置的root密码
```

在MySQL中执行：
```sql
-- 创建数据库
CREATE DATABASE plaszymedb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建数据库用户
CREATE USER 'plaszymedb_user'@'localhost' IDENTIFIED BY 'PlaszymeDB@2025!';

-- 授予权限
GRANT ALL PRIVILEGES ON plaszymedb.* TO 'plaszymedb_user'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;

-- 退出MySQL
EXIT;
```

#### 4.2 导入数据库
```bash
# 导入SQL文件
sudo mysql -u root -p plaszymedb < /var/www/html/plaszymedb/plaszymedb_backup.sql

# 验证导入
sudo mysql -u root -p -e "USE plaszymedb; SHOW TABLES; SELECT COUNT(*) FROM PlaszymeDB;"
```

#### 4.3 更新数据库配置文件
```bash
# 编辑config.php
sudo nano /var/www/html/plaszymedb/config.php
```

修改为：
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'plaszymedb_user');
define('DB_PASS', 'PlaszymeDB@2025!');
define('DB_CHARSET', 'utf8mb4');
```

同样编辑 `db_config.php`：
```bash
sudo nano /var/www/html/plaszymedb/db_config.php
```

修改为：
```php
<?php
define('DB_HOST', 'localhost');  // 移除端口号
define('DB_NAME', 'plaszymedb');
define('DB_USER', 'plaszymedb_user');
define('DB_PASS', 'PlaszymeDB@2025!');
define('DB_CHARSET', 'utf8mb4');
```

---

### 阶段 5: 配置Apache虚拟主机

#### 5.1 创建虚拟主机配置
```bash
sudo nano /etc/httpd/conf.d/plaszymedb.conf
```

添加以下内容：
```apache
<VirtualHost *:80>
    ServerName plaszyme.org
    DocumentRoot /var/www/html
    
    # PlaszymeDB子目录
    <Directory /var/www/html/plaszymedb>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex V9.html index.php index.html
    </Directory>
    
    # 启用PHP
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost"
    </FilesMatch>
    
    # 日志配置
    ErrorLog /var/log/httpd/plaszymedb_error.log
    CustomLog /var/log/httpd/plaszymedb_access.log combined
</VirtualHost>
```

#### 5.2 配置PHP-FPM（如果使用）
```bash
# 检查是否安装php-fpm
sudo dnf list installed | grep php-fpm

# 如果没有，安装它
sudo dnf install php-fpm -y

# 启动php-fpm
sudo systemctl start php-fpm
sudo systemctl enable php-fpm
```

#### 5.3 调整PHP配置
```bash
# 编辑PHP配置
sudo nano /etc/php.ini
```

修改以下设置（用于处理大文件）：
```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

#### 5.4 重启Apache
```bash
sudo systemctl restart httpd
sudo systemctl restart php-fpm
```

---

### 阶段 6: 配置域名DNS

#### 6.1 获取EC2公网IP
```bash
# 查看公网IP
curl http://checkip.amazonaws.com
# 应该显示: 44.192.47.171
```

#### 6.2 配置DNS A记录
登录您的域名注册商（plaszyme.org的DNS管理面板）：

1. 添加/修改A记录：
   - **主机名**: `@` 或 `plaszyme.org`
   - **类型**: A
   - **值**: `44.192.47.171`
   - **TTL**: 3600

2. 确认DNS生效（可能需要几分钟到几小时）：
```bash
# 在本地测试
nslookup plaszyme.org
```

---

### 阶段 7: 配置AWS安全组

#### 7.1 在AWS控制台配置安全组
登录 AWS Console → EC2 → Security Groups

**入站规则（Inbound Rules）**：
| 类型 | 协议 | 端口范围 | 源 | 描述 |
|------|------|---------|-----|------|
| HTTP | TCP | 80 | 0.0.0.0/0 | 公开HTTP访问 |
| HTTPS | TCP | 443 | 0.0.0.0/0 | 公开HTTPS访问（未来） |
| SSH | TCP | 22 | Your IP | SSH访问（仅限您的IP） |
| MySQL/Aurora | TCP | 3306 | Security Group ID | 数据库访问（可选） |

---

### 阶段 8: 测试部署

#### 8.1 测试Apache和PHP
```bash
# 创建测试文件
sudo bash -c 'echo "<?php phpinfo(); ?>" > /var/www/html/info.php'

# 浏览器访问测试
# http://44.192.47.171/info.php
```

#### 8.2 测试数据库连接
浏览器访问：
```
http://44.192.47.171/plaszymedb/test_db_connection.php
```

#### 8.3 测试主页
```
http://44.192.47.171/plaszymedb/V9.html
```

#### 8.4 测试API端点
```
http://44.192.47.171/plaszymedb/search.php?query=PET
http://44.192.47.171/plaszymedb/stats.php
http://44.192.47.171/plaszymedb/get_enzyme_detail.php?plz_id=PLZ_0001
```

---

### 阶段 9: 安全加固

#### 9.1 配置防火墙
```bash
# 启用firewalld
sudo systemctl start firewalld
sudo systemctl enable firewalld

# 允许HTTP和HTTPS
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https

# 重新加载防火墙
sudo firewall-cmd --reload
```

#### 9.2 删除测试文件
```bash
sudo rm /var/www/html/info.php
```

#### 9.3 配置SELinux（如果启用）
```bash
# 检查SELinux状态
getenforce

# 如果是Enforcing，配置权限
sudo chcon -R -t httpd_sys_rw_content_t /var/www/html/plaszymedb/logs
sudo setsebool -P httpd_can_network_connect_db 1
```

#### 9.4 设置自动备份（可选）
```bash
# 创建备份脚本
sudo nano /usr/local/bin/backup_plaszymedb.sh
```

添加：
```bash
#!/bin/bash
BACKUP_DIR="/home/ec2-user/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u plaszymedb_user -p'PlaszymeDB@2025!' plaszymedb > $BACKUP_DIR/plaszymedb_$DATE.sql

# 保留最近7天的备份
find $BACKUP_DIR -name "plaszymedb_*.sql" -mtime +7 -delete
```

```bash
# 设置执行权限
sudo chmod +x /usr/local/bin/backup_plaszymedb.sh

# 添加到crontab（每天凌晨2点备份）
sudo crontab -e
# 添加: 0 2 * * * /usr/local/bin/backup_plaszymedb.sh
```

---

### 阶段 10: 性能优化（可选）

#### 10.1 启用Apache压缩
```bash
# 编辑Apache配置
sudo nano /etc/httpd/conf/httpd.conf
```

添加：
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

#### 10.2 启用浏览器缓存
在 `/etc/httpd/conf.d/plaszymedb.conf` 添加：
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

#### 10.3 MySQL性能调优
```bash
sudo nano /etc/my.cnf.d/mysql-server.cnf
```

添加：
```ini
[mysqld]
innodb_buffer_pool_size = 512M
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
```

重启MySQL：
```bash
sudo systemctl restart mysqld
```

---

## 🔍 故障排除

### 问题1: Apache无法启动
```bash
# 检查错误日志
sudo tail -f /var/log/httpd/error_log

# 检查配置语法
sudo apachectl configtest
```

### 问题2: PHP文件显示为纯文本
```bash
# 确认PHP已安装
php -v

# 重启Apache
sudo systemctl restart httpd
```

### 问题3: 数据库连接失败
```bash
# 测试MySQL连接
mysql -u plaszymedb_user -p plaszymedb

# 检查MySQL状态
sudo systemctl status mysqld
```

### 问题4: 权限被拒绝
```bash
# 重新设置权限
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
```

### 问题5: 大文件上传失败
```bash
# 检查PHP配置
php -i | grep upload_max_filesize
php -i | grep post_max_size

# 修改/etc/php.ini并重启Apache
```

---

## ✅ 部署检查清单

- [ ] EC2实例可以SSH访问
- [ ] Apache已安装并运行
- [ ] PHP已安装（版本7.4+）
- [ ] MySQL已安装并运行
- [ ] 数据库已创建并导入
- [ ] 项目文件已上传到 `/var/www/html/plaszymedb`
- [ ] 文件权限正确设置
- [ ] 数据库配置文件已更新
- [ ] Apache虚拟主机已配置
- [ ] DNS A记录已配置
- [ ] AWS安全组规则已配置
- [ ] http://44.192.47.171/plaszymedb/V9.html 可访问
- [ ] http://plaszyme.org/plaszymedb 可访问
- [ ] 搜索功能正常
- [ ] 3D蛋白质结构查看器正常
- [ ] BLAST搜索功能正常
- [ ] 删除了测试文件

---

## 📞 后续支持

**部署完成后的URL**：
- 主站: http://plaszyme.org/plaszymedb
- 直接IP访问: http://44.192.47.171/plaszymedb

**重要命令参考**：
```bash
# 重启Apache
sudo systemctl restart httpd

# 重启MySQL
sudo systemctl restart mysqld

# 查看Apache错误日志
sudo tail -f /var/log/httpd/error_log

# 查看项目日志
sudo tail -f /var/www/html/plaszymedb/logs/tree_access.log
```

---

## 🔒 安全提示

1. **定期更新系统**: `sudo dnf update -y`
2. **使用强密码**: 数据库和系统账户
3. **限制SSH访问**: 仅允许特定IP
4. **启用HTTPS**: 考虑使用Let's Encrypt免费SSL证书
5. **定期备份**: 数据库和文件
6. **监控日志**: 定期检查访问和错误日志

---

## 📝 未来改进建议

1. **配置HTTPS**: 使用Let's Encrypt获取免费SSL证书
2. **CDN加速**: 使用CloudFront加速静态资源
3. **数据库优化**: 添加索引以提高查询性能
4. **负载均衡**: 如流量增大，考虑使用ELB
5. **自动扩展**: 配置Auto Scaling应对流量高峰
6. **监控告警**: 配置CloudWatch监控和告警

---

**预计部署时间**: 2-3小时（取决于网络速度）

**祝部署顺利！** 🎉

