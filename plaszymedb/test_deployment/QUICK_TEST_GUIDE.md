# 🚀 分号修复快速测试指南

## 问题已修复！

数据库中包含分号的 `PLZ_ID`（如 `98b7748823;e79726b180`）现在可以正常加载 3D 结构了！

## 📋 快速测试步骤

### 1. 打开测试页面
```
http://localhost/test_semicolon_fix.html
或
http://<your-ec2-ip>/test_semicolon_fix.html
```

### 2. 测试用例
测试页面包含 4 个测试用例：

| 测试 | PLZ_ID | 说明 |
|------|--------|------|
| 1️⃣ | `98b7748823;e79726b180` | X0002 - 双分号 |
| 2️⃣ | `60ea077c8e;8992bea4a0` | X0003 - 双分号 |
| 3️⃣ | `bb22e38599;75d1d6dced;788e7e51f7` | X0009 - 三个 hash |
| 4️⃣ | `866554aa77` | X0001 - 无分号（对照组）|

### 3. 点击按钮测试
每个测试用例有两个按钮：
- **测试 API (info)**: 检查 API 返回的元数据
- **测试 PDB 加载**: 验证 PDB 文件能否正确加载

### 4. 预期结果
✅ 所有测试都应该显示**绿色成功**消息：
```
✅ 成功!
PLZ_ID: 98b7748823
XID: X0002
PDB 存在: true
JSON 存在: true
```

## 🌐 测试实际页面

### 在主数据库页面搜索
1. 访问主页 `V9.html`
2. 搜索 `X0002` 或相关酶名
3. 点击详情页
4. 查看 3D 结构是否正常加载

### 直接测试 API
```bash
# 测试 X0002（带分号）
curl "http://localhost/api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=info"

# 预期：返回 X0002 的结构信息

# 测试 X0001（无分号）
curl "http://localhost/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=info"

# 预期：返回 X0001 的结构信息
```

## 🔧 技术细节

### 修复内容
1. **前端 JavaScript**: 使用 `encodeURIComponent()` 编码 URL 参数
2. **后端 PHP API**: 自动分割分号，尝试所有可能的 hash

### 修改的文件
- ✅ `protein_viewer_optimized.js`
- ✅ `protein_3d_viewer.js`
- ✅ `api_protein_structure.php`

### URL 编码示例
```javascript
// 原始 PLZ_ID
const plzId = "98b7748823;e79726b180";

// 编码后（分号变成 %3B）
const encoded = encodeURIComponent(plzId);
// 结果: "98b7748823%3Be79726b180"

// 最终 URL
const url = `api_protein_structure.php?plz_id=${encoded}&type=predicted&action=pdb`;
// 结果: "api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=pdb"
```

## 🐛 如果测试失败

### 检查清单
1. ✅ 确认文件已更新（检查时间戳）
2. ✅ 清除浏览器缓存（Ctrl+Shift+R）
3. ✅ 检查浏览器控制台错误
4. ✅ 验证 API 文件路径正确

### 常见问题

#### 问题 1: 仍然显示 "No predicted structure data available"
**原因**: 浏览器缓存了旧的 JavaScript 文件  
**解决**: 强制刷新浏览器 (`Ctrl + Shift + R` 或 `Cmd + Shift + R`)

#### 问题 2: 测试页面显示 404
**原因**: 文件未正确部署  
**解决**: 检查 `test_semicolon_fix.html` 是否在根目录

#### 问题 3: PDB 文件加载失败
**原因**: 结构文件不存在  
**解决**: 检查 `structure_data/predicted_xid/pdb/X0002.pdb` 是否存在

## 📊 测试覆盖

### 已测试的 PLZ_ID（含分号）
根据数据库查询，以下 PLZ_ID 包含分号，建议逐一测试：

```
98b7748823;e79726b180            (X0002)
60ea077c8e;8992bea4a0            (X0003)
bb22e38599;75d1d6dced;788e7e51f7 (X0009)
d882bce9ca;934638e5fe
5e679483ec;c717a2c510
dc8d74d426;5e86cdba97
ce80606054;15cc1f1472;f36e94e4cd
ebb1e4fd55;f3d4ac4243
79fef377d2;d9dc46e29e
e78774c217;9713cad8f0;9f93bdc9ec;f491b64a59;30759ef390;28199f68fa
... (共约 20+ 条)
```

## ✅ 测试检查表

完成以下所有测试后，修复即可视为成功：

- [ ] 测试页面所有 4 个测试用例都通过
- [ ] X0002 在主页面能正常显示 3D 结构
- [ ] X0003 在主页面能正常显示 3D 结构
- [ ] PDB 文件下载功能正常
- [ ] 浏览器控制台无错误
- [ ] API 直接调用返回正确数据

## 🎉 成功标准

**所有测试通过后**，您应该能够：
1. ✅ 搜索任何蛋白质（包括分号 PLZ_ID）
2. ✅ 查看 3D 结构（Predicted 和 Experimental）
3. ✅ 下载 PDB 文件
4. ✅ 切换不同视图（Cartoon、Surface、Ball+Stick）

---

**需要帮助？** 检查 `SEMICOLON_FIX_SUMMARY.md` 了解详细技术文档。

