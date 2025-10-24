-- PlaszymeDB AWS部署 - 数据库配置脚本
-- 在EC2实例上的MySQL中执行此脚本

-- ================================================
-- 第1步: 创建数据库
-- ================================================
CREATE DATABASE IF NOT EXISTS plaszymedb 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- ================================================
-- 第2步: 创建数据库用户
-- ================================================
-- 删除已存在的用户（如果有）
DROP USER IF EXISTS 'plaszymedb_user'@'localhost';

-- 创建新用户（修改密码为您想要的强密码）
CREATE USER 'plaszymedb_user'@'localhost' 
IDENTIFIED BY 'PlaszymeDB@2025!';

-- ================================================
-- 第3步: 授予权限
-- ================================================
GRANT ALL PRIVILEGES ON plaszymedb.* 
TO 'plaszymedb_user'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;

-- ================================================
-- 第4步: 验证配置
-- ================================================
-- 显示数据库
SHOW DATABASES LIKE 'plaszymedb';

-- 显示用户权限
SHOW GRANTS FOR 'plaszymedb_user'@'localhost';

-- ================================================
-- 第5步: 切换到数据库
-- ================================================
USE plaszymedb;

-- ================================================
-- 完成提示
-- ================================================
SELECT '数据库配置完成！' AS Status;
SELECT '下一步: 导入数据库备份文件' AS NextStep;
SELECT 'mysql -u plaszymedb_user -p plaszymedb < plaszymedb_backup.sql' AS Command;

