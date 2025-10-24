# PlaszymeDB AWS部署脚本说明

本目录包含了PlaszymeDB在AWS EC2上部署所需的所有自动化脚本。

## 📁 脚本清单

### 1. `1_export_database_local.ps1`
**在本地Windows执行**
- 导出PlaszymeDB MySQL数据库
- 生成 `plaszymedb_backup.sql` 文件
- 验证导出文件的完整性

**使用方法：**
```powershell
cd C:\xampp\htdocs\plaszymedb\deploy_scripts
.\1_export_database_local.ps1
```

---

### 2. `2_setup_database.sql`
**在EC2的MySQL中执行**
- 创建 `plaszymedb` 数据库
- 创建 `plaszymedb_user` 用户
- 配置数据库权限

**使用方法：**
```bash
sudo mysql -u root -p < /var/www/html/plaszymedb/deploy_scripts/2_setup_database.sql
```

---

### 3. `3_ec2_setup.sh`
**在EC2上执行**
- 自动安装LAMP环境（Apache + PHP + MySQL）
- 配置Apache虚拟主机
- 创建项目目录和权限
- 配置防火墙

**使用方法：**
```bash
cd ~/plaszymedb/deploy_scripts
chmod +x 3_ec2_setup.sh
./3_ec2_setup.sh
```

---

### 4. `4_update_config.sh`
**在EC2上执行**
- 自动更新 `config.php` 和 `db_config.php`
- 配置生产环境数据库连接参数
- 设置正确的文件权限

**使用方法：**
```bash
cd /var/www/html/plaszymedb/deploy_scripts
chmod +x 4_update_config.sh
sudo ./4_update_config.sh
```

---

### 5. `5_verify_deployment.sh`
**在EC2上执行**
- 验证所有服务是否运行
- 检查项目文件完整性
- 测试数据库连接
- 测试HTTP端点
- 生成详细的测试报告

**使用方法：**
```bash
cd /var/www/html/plaszymedb/deploy_scripts
chmod +x 5_verify_deployment.sh
./5_verify_deployment.sh
```

---

## 🚀 快速部署流程

### 步骤1: 本地准备（Windows）
```powershell
# 1. 导出数据库
cd C:\xampp\htdocs\plaszymedb\deploy_scripts
.\1_export_database_local.ps1

# 2. 使用WinSCP上传整个项目文件夹到EC2
# 目标路径: /home/ec2-user/plaszymedb
```

### 步骤2: EC2环境配置
```bash
# SSH连接到EC2
ssh -i "D:\wangshang.pem" ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com

# 1. 运行LAMP安装脚本
cd ~/plaszymedb/deploy_scripts
chmod +x 3_ec2_setup.sh
./3_ec2_setup.sh

# 2. 配置MySQL安全设置
sudo mysql_secure_installation
# 设置root密码（建议：PlaszymeDB@2025）
```

### 步骤3: 部署项目文件
```bash
# 1. 移动项目文件到Web目录
sudo cp -r ~/plaszymedb/* /var/www/html/plaszymedb/

# 2. 设置权限
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
sudo chmod -R 777 /var/www/html/plaszymedb/logs

# 3. 配置数据库
sudo mysql -u root -p < /var/www/html/plaszymedb/deploy_scripts/2_setup_database.sql

# 4. 导入数据
sudo mysql -u root -p plaszymedb < /var/www/html/plaszymedb/plaszymedb_backup.sql

# 5. 更新配置文件
cd /var/www/html/plaszymedb/deploy_scripts
chmod +x 4_update_config.sh
sudo ./4_update_config.sh
```

### 步骤4: 验证部署
```bash
cd /var/www/html/plaszymedb/deploy_scripts
chmod +x 5_verify_deployment.sh
./5_verify_deployment.sh
```

---

## ⚙️ 配置说明

### 数据库配置
- **数据库名**: `plaszymedb`
- **用户名**: `plaszymedb_user`
- **密码**: `PlaszymeDB@2025!`（可在脚本中修改）
- **字符集**: `utf8mb4`

### 目录结构
```
/var/www/html/plaszymedb/
├── V9.html                 # 主页
├── config.php              # 数据库配置
├── db_config.php           # 数据库配置（备用）
├── search.php              # 搜索API
├── detail.php              # 详情API
├── stats.php               # 统计API
├── images/                 # 图片资源
├── pdb_predicted/          # 预测的PDB文件
├── structure_data/         # 结构数据
├── logs/                   # 日志目录
└── deploy_scripts/         # 部署脚本
```

### Apache虚拟主机配置
- **服务器名**: `plaszyme.org`
- **文档根目录**: `/var/www/html`
- **PlaszymeDB路径**: `/plaszymedb`
- **默认首页**: `V9.html`

---

## 🔧 故障排除

### Apache无法启动
```bash
# 检查配置语法
sudo apachectl configtest

# 查看错误日志
sudo tail -f /var/log/httpd/error_log
```

### 数据库连接失败
```bash
# 测试数据库连接
mysql -u plaszymedb_user -p plaszymedb

# 检查MySQL状态
sudo systemctl status mysqld
```

### 权限错误
```bash
# 重新设置权限
sudo chown -R apache:apache /var/www/html/plaszymedb
sudo chmod -R 755 /var/www/html/plaszymedb
sudo chmod -R 777 /var/www/html/plaszymedb/logs
```

### PHP文件显示源代码
```bash
# 确认PHP已安装
php -v

# 重启Apache和PHP-FPM
sudo systemctl restart httpd
sudo systemctl restart php-fpm
```

---

## 📊 测试端点

部署完成后，可以通过以下URL测试：

### 主页
```
http://44.192.47.171/plaszymedb/V9.html
http://plaszyme.org/plaszymedb
```

### API端点
```
http://44.192.47.171/plaszymedb/search.php?query=PET
http://44.192.47.171/plaszymedb/stats.php
http://44.192.47.171/plaszymedb/detail.php?plz_id=PLZ_0001
http://44.192.47.171/plaszymedb/get_enzyme_detail.php?plz_id=PLZ_0001
```

---

## 🔒 安全建议

1. **修改默认密码**: 在生产环境中修改数据库密码
2. **限制SSH访问**: 仅允许特定IP访问SSH（端口22）
3. **启用HTTPS**: 使用Let's Encrypt配置SSL证书
4. **定期备份**: 配置自动备份脚本
5. **监控日志**: 定期检查访问和错误日志

---

## 📞 支持

如遇到问题，请检查：
1. Apache错误日志: `/var/log/httpd/error_log`
2. PlaszymeDB日志: `/var/www/html/plaszymedb/logs/`
3. MySQL错误日志: `/var/log/mysqld.log`

---

**预计总部署时间**: 2-3小时

**祝部署顺利！** 🎉

