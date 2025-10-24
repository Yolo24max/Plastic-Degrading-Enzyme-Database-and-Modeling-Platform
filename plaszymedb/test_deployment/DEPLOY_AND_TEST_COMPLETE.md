# 🚀 EC2 完整部署和测试指南

## 📋 问题回顾

### 之前做了什么
- ✅ 修复了 JavaScript 的 URL 编码（`encodeURIComponent`）
- ✅ 修复了 PHP 接收分号的逻辑

### 为什么还是不工作？
**根本原因**: API 读取的 CSV 文件（`pred_metadata_XID.csv`）中只有**部分 PLZ_ID**！

```
数据库: 98b7748823;e79726b180  (完整的)
CSV文件: 98b7748823           (只有第一部分) ❌
```

### 这次的真正修复
修改 API 读取 **`PLZ_XID.csv`** 文件，它包含 `original_PLZ_IDs` 列：

```csv
protein_id,PLZ_ID,original_PLZ_IDs
X0002,98b7748823,98b7748823;e79726b180  ✅ 完整的！
```

---

## 🎯 部署前准备

### 必需文件清单

在本地检查这些文件是否存在：

```bash
# 核心文件（必须上传）
api_protein_structure.php           # 修改了 CSV 读取逻辑
protein_viewer_optimized.js        # URL 编码
protein_3d_viewer.js               # URL 编码  
test_semicolon_fix.html            # 测试页面

# 数据文件（必须确认 EC2 上存在）
structure_data/predicted_xid/PLZ_XID.csv  # 包含 original_PLZ_IDs 的文件
```

---

## 🚀 部署方案（3 选 1）

### 方案 A: 一键自动部署脚本 ⭐（推荐）

```bash
# 在本地 PowerShell 或 Git Bash 中：
cd C:\xampp\htdocs\plaszymedb
bash deploy_semicolon_fix.sh 18.237.158.100
```

**优点**: 自动上传所有文件，自动设置权限  
**注意**: 需要确保 EC2 上已有 `PLZ_XID.csv` 文件

---

### 方案 B: 手动 SCP 上传

#### Step 1: 上传核心文件

```bash
scp -i PlaszymeDB_AWS.pem \
    api_protein_structure.php \
    protein_viewer_optimized.js \
    protein_3d_viewer.js \
    test_semicolon_fix.html \
    ec2-user@18.237.158.100:/var/www/html/
```

#### Step 2: 检查 CSV 文件

```bash
bash check_and_upload_csv.sh 18.237.158.100
```

#### Step 3: 如果需要，上传 CSV

```bash
scp -i PlaszymeDB_AWS.pem \
    structure_data/predicted_xid/PLZ_XID.csv \
    ec2-user@18.237.158.100:/tmp/

ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100
sudo mv /tmp/PLZ_XID.csv /var/www/html/structure_data/predicted_xid/
sudo chown apache:apache /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
sudo chmod 644 /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
exit
```

---

### 方案 C: 在服务器上直接修改（不推荐）

如果您坚持在服务器上修改：

```bash
# 1. SSH 登录
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100

# 2. 备份原文件
cd /var/www/html
sudo cp api_protein_structure.php api_protein_structure.php.backup_$(date +%Y%m%d)

# 3. 编辑文件
sudo nano api_protein_structure.php

# 找到第 95 行左右：
#   $metadata_csv = $base_path . '/predicted_xid/pred_metadata_XID.csv';
# 改为：
#   $metadata_csv = $base_path . '/predicted_xid/PLZ_XID.csv';

# 找到第 37-49 行的 loadPlzToXidMapping 函数
# 在查找 PLZ_ID 列索引之前，添加：
#   $originalPlzIdIndex = array_search('original_PLZ_IDs', $header);
#   $plzIdIndex = array_search('PLZ_ID', $header);
#   $usePlzIdIndex = ($originalPlzIdIndex !== false) ? $originalPlzIdIndex : $plzIdIndex;
# 然后将所有使用 $plzIdIndex 的地方改为 $usePlzIdIndex

# 保存: Ctrl+O, 回车, 退出: Ctrl+X

# 4. 检查语法
php -l api_protein_structure.php
```

---

## 🧪 测试步骤

### 1. 快速 API 测试

在浏览器或 curl 中测试：

```bash
# 测试 X0002（带分号的 PLZ_ID）
curl "http://18.237.158.100/api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=info"
```

**期望响应**:
```json
{
  "plz_id": "98b7748823",
  "xid": "X0002",
  "data_type": "predicted",
  "files": {
    "pdb_exists": true,
    "json_exists": true
  }
}
```

### 2. 使用测试页面

打开浏览器：
```
http://18.237.158.100/test_semicolon_fix.html
```

**点击 4 个测试按钮**:

| 测试 | PLZ_ID | 预期结果 |
|------|--------|----------|
| Test 1 | 98b7748823;e79726b180 | ✅ XID: X0002 |
| Test 2 | 60ea077c8e;8992bea4a0 | ✅ XID: X0003 |
| Test 3 | bb22e38599;75d1d6dced;788e7e51f7 | ✅ XID: X0009 |
| Test 4 | 866554aa77 | ✅ XID: X0001 |

### 3. 3D 查看器测试

1. 打开主页: `http://18.237.158.100/V9.html`
2. 搜索 "X0002" 或任何带分号 PLZ_ID 的蛋白质
3. 点击 "View 3D Structure"
4. 确认 3D 结构能正常加载和显示

---

## 🔍 故障排除

### 问题 1: API 返回 404 "No structure data available"

**原因**: PLZ_XID.csv 文件不存在

**解决**:
```bash
bash check_and_upload_csv.sh 18.237.158.100
```

如果显示"❌ PLZ_XID.csv 不存在"，上传它：
```bash
scp -i PlaszymeDB_AWS.pem structure_data/predicted_xid/PLZ_XID.csv ec2-user@18.237.158.100:/tmp/
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100 "sudo mv /tmp/PLZ_XID.csv /var/www/html/structure_data/predicted_xid/ && sudo chown apache:apache /var/www/html/structure_data/predicted_xid/PLZ_XID.csv"
```

---

### 问题 2: API 仍然返回错误

**检查 PHP 错误日志**:
```bash
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100
sudo tail -f /var/log/httpd/error_log
```

**检查文件权限**:
```bash
ls -lh /var/www/html/api_protein_structure.php
ls -lh /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
```

应该显示:
```
-rw-r--r-- 1 apache apache ... api_protein_structure.php
-rw-r--r-- 1 apache apache ... PLZ_XID.csv
```

如果不对，修复权限：
```bash
sudo chown apache:apache /var/www/html/api_protein_structure.php
sudo chmod 644 /var/www/html/api_protein_structure.php
sudo chown -R apache:apache /var/www/html/structure_data/
```

---

### 问题 3: CSV 文件没有 original_PLZ_IDs 列

**检查 CSV 文件头**:
```bash
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100
head -n 1 /var/www/html/structure_data/predicted_xid/PLZ_XID.csv | grep "original_PLZ_IDs"
```

如果没有输出，说明文件错误。从本地重新上传：
```bash
scp -i PlaszymeDB_AWS.pem structure_data/predicted_xid/PLZ_XID.csv ec2-user@18.237.158.100:/tmp/
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100 "sudo mv /tmp/PLZ_XID.csv /var/www/html/structure_data/predicted_xid/"
```

---

### 问题 4: PDB 文件不存在

**检查 PDB 文件**:
```bash
ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100
ls /var/www/html/structure_data/predicted_xid/pdb/ | wc -l
```

应该显示 474（或接近这个数字）。

如果显示 0，需要上传整个 `structure_data` 文件夹（这会很大，请使用 `rsync`）:
```bash
rsync -avz -e "ssh -i PlaszymeDB_AWS.pem" \
    structure_data/ \
    ec2-user@18.237.158.100:/tmp/structure_data_upload/

ssh -i PlaszymeDB_AWS.pem ec2-user@18.237.158.100
sudo rsync -av /tmp/structure_data_upload/ /var/www/html/structure_data/
sudo chown -R apache:apache /var/www/html/structure_data/
exit
```

---

## ✅ 成功标准

部署被认为成功当：

- ✅ 所有 4 个测试用例通过（test_semicolon_fix.html）
- ✅ API 正确返回 XID（curl 测试）
- ✅ 3D 查看器能加载带分号 PLZ_ID 的结构
- ✅ 浏览器控制台没有 404 错误

---

## 📊 部署检查清单

### 部署前

- [ ] 本地文件已更新（api_protein_structure.php）
- [ ] 本地测试通过
- [ ] SSH 密钥文件存在（PlaszymeDB_AWS.pem）
- [ ] 知道 EC2 IP 地址

### 部署中

- [ ] 文件成功上传到 EC2
- [ ] 文件权限正确设置
- [ ] PLZ_XID.csv 文件存在于 EC2

### 部署后

- [ ] API 测试通过（curl）
- [ ] 测试页面 4 个按钮都成功
- [ ] 3D 查看器正常工作
- [ ] 无 PHP 错误日志

---

## 🎓 关键学习

### 这次修复教会我们什么？

1. **完整的问题需要完整的解决方案**
   - URL 编码只解决了传输问题
   - 数据映射才是根本问题

2. **数据一致性至关重要**
   - 数据库: `98b7748823;e79726b180`
   - CSV 也必须: `98b7748823;e79726b180`
   - 不能只存一半！

3. **多层次调试**
   - 前端: URL 参数
   - 后端: PHP 接收
   - 数据: CSV 映射
   - 文件系统: PDB 文件

---

## 📞 需要帮助？

如果您遇到问题，请提供：

1. 您使用的部署方案（A/B/C）
2. 错误消息（浏览器控制台 + PHP 日志）
3. `check_and_upload_csv.sh` 的输出
4. API 测试的响应

---

**祝您部署顺利！** 🎉

如果测试全部通过，恭喜您成功修复了分号 PLZ_ID 问题！

