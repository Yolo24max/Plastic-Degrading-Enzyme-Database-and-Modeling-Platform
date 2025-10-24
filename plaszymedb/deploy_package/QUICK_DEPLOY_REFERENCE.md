# PlaszymeDB AWS 快速部署参考卡

## 🎯 一键命令速查

### 本地Windows（PowerShell）

```powershell
# 1. 导出数据库
cd C:\xampp\htdocs\plaszymedb\deploy_scripts
.\1_export_database_local.ps1

# 2. 压缩项目（可选，使用WinRAR或7-Zip）
# 或直接使用WinSCP上传
```

---

### EC2实例（Linux Bash）

#### 一次性部署命令（推荐）
```bash
# SSH连接
ssh -i "D:\wangshang.pem" ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com

# 自动安装LAMP环境
cd ~/plaszymedb/deploy_scripts && chmod +x 3_ec2_setup.sh && ./3_ec2_setup.sh

# 配置MySQL安全
sudo mysql_secure_installation
# 密码: PlaszymeDB@2025

# 部署项目
sudo cp -r ~/plaszymedb/* /var/www/html/plaszymedb/
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
sudo chmod -R 777 /var/www/html/plaszymedb/logs

# 配置数据库
sudo mysql -u root -p < /var/www/html/plaszymedb/deploy_scripts/2_setup_database.sql
sudo mysql -u root -p plaszymedb < /var/www/html/plaszymedb/plaszymedb_backup.sql

# 更新配置文件
cd /var/www/html/plaszymedb/deploy_scripts
chmod +x 4_update_config.sh && sudo ./4_update_config.sh

# 验证部署
chmod +x 5_verify_deployment.sh && ./5_verify_deployment.sh
```

---

## 📋 关键信息速查

### EC2连接信息
```
主机: ec2-44-192-47-171.compute-1.amazonaws.com
IP: 44.192.47.171
用户: ec2-user
密钥: D:\wangshang.pem
```

### 数据库信息
```
数据库名: plaszymedb
用户名: plaszymedb_user
密码: PlaszymeDB@2025!
Root密码: PlaszymeDB@2025（在mysql_secure_installation时设置）
```

### 访问URL
```
主页: http://plaszyme.org/plaszymedb
或: http://44.192.47.171/plaszymedb/V9.html
```

### 关键路径
```
项目目录: /var/www/html/plaszymedb
Apache配置: /etc/httpd/conf.d/plaszymedb.conf
PHP配置: /etc/php.ini
Apache日志: /var/log/httpd/
项目日志: /var/www/html/plaszymedb/logs/
```

---

## 🔧 常用命令

### 服务管理
```bash
# 重启Apache
sudo systemctl restart httpd

# 重启MySQL
sudo systemctl restart mysqld

# 重启PHP-FPM
sudo systemctl restart php-fpm

# 查看服务状态
sudo systemctl status httpd
sudo systemctl status mysqld
sudo systemctl status php-fpm
```

### 日志查看
```bash
# Apache错误日志
sudo tail -f /var/log/httpd/error_log

# Apache访问日志
sudo tail -f /var/log/httpd/access_log

# PlaszymeDB日志
sudo tail -f /var/www/html/plaszymedb/logs/tree_access.log
```

### 权限修复
```bash
# 重置项目权限
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
sudo chmod -R 777 /var/www/html/plaszymedb/logs
```

### 数据库操作
```bash
# 登录MySQL
sudo mysql -u root -p

# 登录为应用用户
mysql -u plaszymedb_user -p plaszymedb

# 备份数据库
mysqldump -u plaszymedb_user -p plaszymedb > backup_$(date +%Y%m%d).sql

# 恢复数据库
mysql -u plaszymedb_user -p plaszymedb < backup_20241011.sql
```

---

## ⚡ 快速测试

### 测试Apache和PHP
```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
curl http://localhost/info.php
sudo rm /var/www/html/info.php
```

### 测试数据库连接
```bash
php -r "require '/var/www/html/plaszymedb/config.php'; getDbConnection(); echo 'OK\n';"
```

### 测试网站可访问性
```bash
curl -I http://localhost/plaszymedb/V9.html
curl -I http://localhost/plaszymedb/search.php
curl -I http://localhost/plaszymedb/stats.php
```

### 获取公网IP
```bash
curl http://checkip.amazonaws.com
```

---

## 🐛 快速故障排除

### Apache无法启动
```bash
sudo apachectl configtest
sudo tail -50 /var/log/httpd/error_log
```

### 数据库连接失败
```bash
# 检查MySQL运行状态
sudo systemctl status mysqld

# 测试连接
mysql -u plaszymedb_user -p'PlaszymeDB@2025!' -e "SHOW DATABASES;"
```

### 404错误
```bash
# 检查文件是否存在
ls -la /var/www/html/plaszymedb/V9.html

# 检查Apache配置
sudo cat /etc/httpd/conf.d/plaszymedb.conf
```

### 500错误
```bash
# 查看PHP错误
sudo tail -50 /var/log/httpd/error_log

# 检查文件权限
ls -la /var/www/html/plaszymedb/
```

---

## 📊 验证检查清单

- [ ] Apache运行中: `sudo systemctl status httpd`
- [ ] MySQL运行中: `sudo systemctl status mysqld`
- [ ] PHP已安装: `php -v`
- [ ] 项目文件已上传到 `/var/www/html/plaszymedb`
- [ ] 数据库已创建: `mysql -u plaszymedb_user -p -e "SHOW DATABASES;"`
- [ ] 数据已导入: `mysql -u plaszymedb_user -p plaszymedb -e "SELECT COUNT(*) FROM PlaszymeDB;"`
- [ ] 配置文件已更新: `cat /var/www/html/plaszymedb/config.php`
- [ ] 主页可访问: `curl http://localhost/plaszymedb/V9.html`
- [ ] API可访问: `curl http://localhost/plaszymedb/stats.php`
- [ ] DNS已配置: `nslookup plaszyme.org`（在本地执行）
- [ ] 安全组已配置HTTP(80)端口
- [ ] 防火墙已开放HTTP: `sudo firewall-cmd --list-services`

---

## 🔐 AWS安全组配置

### 在AWS控制台配置入站规则

| 类型 | 端口 | 源 | 描述 |
|------|------|-----|------|
| HTTP | 80 | 0.0.0.0/0 | 网站访问 |
| HTTPS | 443 | 0.0.0.0/0 | SSL访问（未来） |
| SSH | 22 | 您的IP | SSH管理 |

---

## 📞 支持资源

### 完整文档
- `AWS_DEPLOYMENT_GUIDE.md` - 详细部署指南
- `deploy_scripts/README.md` - 脚本使用说明

### 测试脚本
```bash
/var/www/html/plaszymedb/deploy_scripts/5_verify_deployment.sh
```

### 日志位置
- Apache错误: `/var/log/httpd/error_log`
- Apache访问: `/var/log/httpd/access_log`
- PlaszymeDB: `/var/www/html/plaszymedb/logs/`
- MySQL: `/var/log/mysqld.log`

---

**打印此页面并随时参考！** 📄

