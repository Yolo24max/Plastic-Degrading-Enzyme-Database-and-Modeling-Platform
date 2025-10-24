# 🎯 PLZ_ID 分号问题 - 最终修复方案

## ❌ 原来的问题

### 根本原因分析

1. **数据库存储**: PLZ_ID 包含分号（如 `98b7748823;e79726b180`）
2. **映射文件错误**: `pred_metadata_XID.csv` 只包含第一个 PLZ_ID（`98b7748823`）
3. **完整数据**: `PLZ_XID.csv` 包含 `original_PLZ_IDs` 列，有完整的 PLZ_ID

### 为什么之前的修复不够

之前只修复了 **URL 编码**问题（JavaScript 中的 `encodeURIComponent`），但即使 URL 正确传递了分号，API 仍然找不到文件，因为：

```php
// pred_metadata_XID.csv 中的数据：
PLZ_ID,protein_id
98b7748823,X0002    // ❌ 只有第一部分

// 但数据库中实际是：
98b7748823;e79726b180 -> X0002  // ✅ 完整的 PLZ_ID
```

---

## ✅ 最终解决方案

### 修改 1: 使用正确的 CSV 文件

**文件**: `api_protein_structure.php`

```php
// 旧代码：
$metadata_csv = $base_path . '/predicted_xid/pred_metadata_XID.csv';

// 新代码：
$metadata_csv = $base_path . '/predicted_xid/PLZ_XID.csv';
```

**原因**: `PLZ_XID.csv` 包含 `original_PLZ_IDs` 列，其中存储了完整的带分号的 PLZ_ID。

---

### 修改 2: 读取 original_PLZ_IDs 列

**文件**: `api_protein_structure.php` - `loadPlzToXidMapping()` 函数

```php
// 查找列索引 - 优先使用 original_PLZ_IDs
$originalPlzIdIndex = array_search('original_PLZ_IDs', $header);
$plzIdIndex = array_search('PLZ_ID', $header);
$proteinIdIndex = array_search('protein_id', $header);

// 选择可用的PLZ_ID列（优先 original_PLZ_IDs）
$usePlzIdIndex = ($originalPlzIdIndex !== false) ? $originalPlzIdIndex : $plzIdIndex;
```

**工作原理**:
1. 首先尝试找 `original_PLZ_IDs` 列
2. 如果没有，回退到 `PLZ_ID` 列（向后兼容）
3. 使用找到的列来构建映射

---

### 修改 3: JavaScript URL 编码（已完成）

**文件**: `protein_viewer_optimized.js`, `protein_3d_viewer.js`

```javascript
// 旧代码：
const url = `api_protein_structure.php?plz_id=${plzId}&type=${type}`;

// 新代码：
const url = `api_protein_structure.php?plz_id=${encodeURIComponent(plzId)}&type=${type}`;
```

---

## 📊 数据流程图

```
前端 (V9.html)
  ↓ PLZ_ID: "98b7748823;e79726b180"
  
JavaScript (protein_viewer_optimized.js)
  ↓ encodeURIComponent() → "98b7748823%3Be79726b180"
  
API (api_protein_structure.php)
  ↓ URL decode → "98b7748823;e79726b180"
  ↓ 读取 PLZ_XID.csv 的 original_PLZ_IDs 列
  ↓ 分号分割 → ["98b7748823", "e79726b180"]
  ↓ 映射查找: 
      mapping["98b7748823"] = "X0002"
      mapping["e79726b180"] = "X0002"
  ↓ 找到: XID = "X0002"
  
文件系统
  ✅ 加载: structure_data/predicted_xid/pdb/X0002.pdb
```

---

## 🧪 测试结果

### 测试用例

| XID | PLZ_ID | 预期结果 | 状态 |
|-----|--------|----------|------|
| X0002 | 98b7748823;e79726b180 | 成功找到 X0002.pdb | ✅ |
| X0003 | 60ea077c8e;8992bea4a0 | 成功找到 X0003.pdb | ✅ |
| X0009 | bb22e38599;75d1d6dced;788e7e51f7 | 成功找到 X0009.pdb | ✅ |
| X0001 | 866554aa77 | 成功找到 X0001.pdb | ✅ |

### 本地测试

```bash
curl "http://localhost/plaszymedb/api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=info"
```

**响应**:
```json
{
  "plz_id": "98b7748823",
  "xid": "X0002",
  "data_type": "predicted",
  "files": {
    "pdb_exists": true,
    "json_exists": true,
    "pdb_path": "api_protein_structure.php?plz_id=98b7748823&type=predicted&action=pdb",
    "json_path": "api_protein_structure.php?plz_id=98b7748823&type=predicted&action=json",
    "pdb_file_path": "...\\structure_data\\predicted_xid\\pdb\\X0002.pdb",
    "json_file_path": "...\\structure_data\\predicted_xid\\json\\X0002.json"
  }
}
```

✅ **成功！**

---

## 📦 需要部署的文件

### 核心修复文件

1. **api_protein_structure.php** - 使用 PLZ_XID.csv 和 original_PLZ_IDs 列
2. **protein_viewer_optimized.js** - URL 编码支持
3. **protein_3d_viewer.js** - URL 编码支持
4. **test_semicolon_fix.html** - 测试页面

### 数据文件（必须存在）

EC2 服务器上必须有：
- `structure_data/predicted_xid/PLZ_XID.csv` - 包含 original_PLZ_IDs 列
- `structure_data/predicted_xid/pdb/*.pdb` - PDB 文件
- `structure_data/predicted_xid/json/*.json` - JSON 元数据

---

## 🚀 EC2 部署步骤

### 方法 1: 使用部署脚本（推荐）

```bash
bash deploy_semicolon_fix.sh YOUR_EC2_IP
```

### 方法 2: 手动上传

```bash
scp -i PlaszymeDB_AWS.pem \
    api_protein_structure.php \
    protein_viewer_optimized.js \
    protein_3d_viewer.js \
    test_semicolon_fix.html \
    ec2-user@YOUR_EC2_IP:/var/www/html/
```

### 验证部署

1. 打开: `http://YOUR_EC2_IP/test_semicolon_fix.html`
2. 点击所有 4 个测试按钮
3. 确保都显示 ✅ 成功

---

## 📝 关键学习点

### 1. URL 编码不够

只修复 JavaScript 的 URL 编码是不够的。如果服务器端映射数据错误，仍然无法找到文件。

### 2. 数据一致性很重要

- 数据库: `98b7748823;e79726b180`
- CSV 映射文件也必须: `98b7748823;e79726b180`
- 不能只存储: `98b7748823`

### 3. 完整的修复需要

- ✅ 前端: URL 编码
- ✅ 后端: 正确的数据源
- ✅ 数据: 完整的映射关系

---

## 🎓 技术细节

### PLZ_XID.csv 文件结构

```csv
protein_id,sequence,...,PLZ_ID,original_PLZ_IDs,selection_reason,...
X0001,...,866554aa77,866554aa77,一对一映射,...
X0002,...,98b7748823,98b7748823;e79726b180,高置信度(pLDDT≥90.0)+最高pTM,...
X0003,...,60ea077c8e,60ea077c8e;8992bea4a0,高置信度(pLDDT≥90.0)+最高pTM,...
```

### 为什么有两个 PLZ_ID 列？

- **PLZ_ID**: 被选中的代表性 PLZ_ID（用于主要标识）
- **original_PLZ_IDs**: 所有原始 PLZ_ID（用于兼容性和反向查找）

---

## ✅ 完成标准

修复被认为完成当：

- [x] API 能识别带分号的 PLZ_ID
- [x] JavaScript 正确编码 URL 参数
- [x] 本地测试通过（4/4 测试用例）
- [ ] EC2 部署成功
- [ ] EC2 测试通过（4/4 测试用例）
- [ ] 3D 结构查看器正常工作

---

## 📞 故障排除

### 如果 EC2 测试失败：

1. **检查文件是否上传**:
   ```bash
   ssh -i PlaszymeDB_AWS.pem ec2-user@YOUR_EC2_IP
   ls -lh /var/www/html/api_protein_structure.php
   ```

2. **检查 CSV 文件是否存在**:
   ```bash
   ls -lh /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
   ```

3. **检查 API 响应**:
   ```bash
   curl "http://YOUR_EC2_IP/api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=info"
   ```

4. **查看 PHP 错误日志**:
   ```bash
   sudo tail -f /var/log/httpd/error_log
   ```

---

**作者**: AI Assistant  
**日期**: 2025-10-11  
**版本**: 2.0 (最终修复)

