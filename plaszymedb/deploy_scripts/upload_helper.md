# PlaszymeDB 文件上传指南

## 使用WinSCP上传文件（推荐）

### 步骤1: 下载和安装WinSCP

1. 访问 https://winscp.net/eng/download.php
2. 下载并安装WinSCP

### 步骤2: 准备SSH密钥

WinSCP需要PuTTY格式的密钥(.ppk)，而您的密钥是.pem格式。

**选项A: 使用PuTTYgen转换密钥**

1. 下载PuTTYgen: https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html
2. 运行PuTTYgen
3. 点击 `Load` 按钮
4. 选择您的 `D:\wangshang.pem` 文件（选择文件类型为"All Files"）
5. 点击 `Save private key` 
6. 保存为 `D:\wangshang.ppk`

**选项B: WinSCP自动转换（更简单）**

WinSCP可以直接使用.pem文件，在连接时会提示转换。

### 步骤3: 配置WinSCP连接

1. 打开WinSCP
2. 点击 `New Site`
3. 填写连接信息：
   ```
   文件协议: SFTP
   主机名: ec2-44-192-47-171.compute-1.amazonaws.com
   端口号: 22
   用户名: ec2-user
   密码: (留空)
   ```
4. 点击 `Advanced...` → `SSH` → `Authentication`
5. 在 `Private key file` 选择 `D:\wangshang.ppk` 或 `D:\wangshang.pem`
6. 点击 `OK`
7. 点击 `Save` 保存会话（命名为"PlaszymeDB AWS"）
8. 点击 `Login` 连接

### 步骤4: 上传文件

1. 左侧窗口：浏览到 `C:\xampp\htdocs\plaszymedb`
2. 右侧窗口：浏览到 `/home/ec2-user/`
3. 在右侧创建目录 `plaszymedb`（右键 → New → Directory）
4. 进入 `plaszymedb` 目录
5. 选择左侧的所有文件（Ctrl+A）
6. 拖拽到右侧窗口或点击 `Upload` 按钮
7. 等待上传完成（可能需要30分钟-1小时，取决于网速）

### 上传优化建议

**方案1: 分批上传（推荐）**

大文件夹可能上传失败，建议分批上传：

1. 先上传核心文件：
   - *.php
   - *.html
   - *.js
   - *.csv
   - config.php
   - db_config.php
   - plaszymedb_backup.sql

2. 再上传目录：
   - images/
   - deploy_scripts/
   - structure_data/
   - phylogeny_data/

3. 最后上传大目录：
   - pdb_predicted/ (包含1498个文件)
   - pdb_files/

**方案2: 压缩后上传**

```powershell
# 在本地PowerShell执行
cd C:\xampp\htdocs

# 使用7-Zip压缩（需要安装7-Zip）
& "C:\Program Files\7-Zip\7z.exe" a -tzip plaszymedb.zip plaszymedb\ -x!test_* -x!*.md -x!logs -x!.git

# 或使用PowerShell内置压缩
Compress-Archive -Path plaszymedb -DestinationPath plaszymedb.zip
```

然后使用WinSCP上传 `plaszymedb.zip`

在EC2上解压：
```bash
cd /home/ec2-user
unzip plaszymedb.zip
```

---

## 使用SCP命令上传（命令行）

### 方法1: 上传单个文件
```powershell
scp -i "D:\wangshang.pem" C:\xampp\htdocs\plaszymedb\V9.html ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com:~/plaszymedb/
```

### 方法2: 上传整个目录
```powershell
scp -i "D:\wangshang.pem" -r C:\xampp\htdocs\plaszymedb ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com:~/
```

### 方法3: 上传压缩文件
```powershell
# 先压缩
Compress-Archive -Path C:\xampp\htdocs\plaszymedb -DestinationPath C:\xampp\htdocs\plaszymedb.zip

# 上传
scp -i "D:\wangshang.pem" C:\xampp\htdocs\plaszymedb.zip ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com:~/
```

---

## 使用FileZilla上传（备选）

### 配置FileZilla

1. 下载FileZilla: https://filezilla-project.org/
2. 打开 `Edit` → `Settings` → `Connection` → `SFTP`
3. 点击 `Add key file...` 选择 `D:\wangshang.pem`
4. FileZilla会自动转换为兼容格式

### 连接配置

在FileZilla主界面输入：
```
主机: sftp://ec2-44-192-47-171.compute-1.amazonaws.com
用户名: ec2-user
密码: (留空)
端口: 22
```

点击 `Quickconnect` 连接，然后拖拽文件上传。

---

## 上传进度监控

### 使用rsync（最可靠，支持断点续传）

```powershell
# 在Windows上安装Git Bash或WSL后执行
rsync -avz --progress -e "ssh -i D:/wangshang.pem" \
  /c/xampp/htdocs/plaszymedb/ \
  ec2-user@ec2-44-192-47-171.compute-1.amazonaws.com:~/plaszymedb/
```

---

## 文件大小估算

```
预计总大小: ~500MB - 2GB（取决于PDB文件数量）
核心文件: ~50MB
PDB文件: ~400MB - 1.5GB
数据库备份: ~10-100MB
```

**预计上传时间**（取决于网速）：
- 100Mbps: 约10-30分钟
- 50Mbps: 约20-60分钟
- 10Mbps: 约1-3小时

---

## 上传后验证

在EC2上执行：
```bash
# 检查文件完整性
cd ~/plaszymedb
ls -lh

# 检查文件数量
find . -type f | wc -l

# 检查目录结构
tree -L 2 -d
# 如果没有tree命令: sudo dnf install tree -y
```

---

## 故障排除

### 连接被拒绝
- 检查AWS安全组是否开放22端口
- 检查密钥文件权限（不能太宽松）
- 确认使用正确的用户名（ec2-user）

### 上传中断
- 使用rsync支持断点续传
- 分批上传小文件
- 检查网络稳定性

### 权限错误
在EC2上执行：
```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

---

## 推荐方案总结

**最推荐**: WinSCP图形界面上传
- 优点：可视化、稳定、易用
- 缺点：首次配置需要转换密钥

**备选**: 压缩后SCP上传
- 优点：速度快、单个文件
- 缺点：需要在EC2上解压

**高级用户**: rsync命令
- 优点：支持断点续传、增量同步
- 缺点：需要Git Bash或WSL环境

---

**祝上传顺利！** 🚀

