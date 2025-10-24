# 蛋白质3D结构可视化功能 - 使用指南

## 功能概述

基于 Mol* 库实现的蛋白质3D结构可视化功能，支持：
- 基于 PLZ_ID 自动加载对应 PDB 文件
- 预测数据和实验数据切换
- 多种表示方式（卡通、表面、球棍模式）
- 基础操作控制（旋转、缩放、重置视图）
- 下载功能（PDB文件、结构图片）

## 文件结构

```
plaszymedb/
├── api_protein_structure.php          # 3D结构API接口
├── V9.html                           # 主页面（已集成3D功能）
├── pdb_predicted/                    # 预测数据
│   ├── pdb/                         # PDB文件
│   └── json/                        # JSON元数据
└── pdb_experimental/                 # 实验数据（预留）
    ├── pdb/                         # PDB文件
    └── json/                        # JSON元数据
```

## API接口说明

### api_protein_structure.php

支持以下操作：

1. **获取结构信息**
   ```
   GET api_protein_structure.php?plz_id=00d4a4bfbe&type=predicted&action=info
   ```

2. **获取PDB文件**
   ```
   GET api_protein_structure.php?plz_id=00d4a4bfbe&type=predicted&action=pdb
   ```

3. **获取JSON元数据**
   ```
   GET api_protein_structure.php?plz_id=00d4a4bfbe&type=predicted&action=json
   ```

4. **列出可用结构**
   ```
   GET api_protein_structure.php?type=predicted&action=list_available
   ```

### 参数说明

- `plz_id`: PLZ数据库ID
- `type`: 数据类型 (`predicted` 或 `experimental`)
- `action`: 操作类型 (`info`, `pdb`, `json`, `list_available`)

## 前端使用方法

### 1. 基本使用

在浏览器控制台中测试：
```javascript
// 测试加载特定结构
testStructureLoad('00d4a4bfbe');

// 显示详情页面并加载结构
showDetailWithStructure('00d4a4bfbe');

// 直接加载结构
loadProteinStructure('00d4a4bfbe', 'predicted');
```

### 2. 界面操作

- **数据类型切换**: 点击"预测"或"实验"按钮
- **视图控制**: 
  - 重置视图
  - 全屏显示
- **表示方式**:
  - 卡通模式（默认）
  - 表面模式
  - 球棍模式
- **下载功能**:
  - 下载PDB文件
  - 下载结构图片

### 3. 程序化调用

```javascript
// 初始化查看器
await initMolstarViewer();

// 加载预测数据
await loadProteinStructure('PLZ_ID', 'predicted');

// 加载实验数据
await loadProteinStructure('PLZ_ID', 'experimental');

// 设置表示方式
await setRepresentationStyle('cartoon'); // 'surface', 'ball-stick'
```

## 集成到现有系统

### 1. 搜索结果集成

修改搜索结果的点击事件：
```javascript
onclick="showDetailWithStructure('PLZ_ID', enzymeData)"
```

### 2. 详情页面集成

3D结构查看器已嵌入到详情页面的"分子结构信息"部分，可与其他内容无缝集成。

## 添加实验数据

1. 将PDB文件放入 `pdb_experimental/pdb/` 目录
2. 将对应的JSON元数据放入 `pdb_experimental/json/` 目录
3. 确保文件名与PLZ_ID一致（例如：`00d4a4bfbe.pdb`）

## 性能优化

- Mol*库采用CDN加载，减少服务器负担
- 结构文件按需加载，避免一次性加载大量数据
- 支持错误处理和加载状态显示
- 嵌入式设计，不影响页面其他功能

## 故障排除

### 1. 结构无法加载
- 检查PDB文件是否存在
- 确认PLZ_ID格式正确
- 查看浏览器控制台错误信息

### 2. Mol*库加载失败
- 检查网络连接
- 确认CDN链接可访问
- 查看浏览器开发者工具Network标签

### 3. API错误
- 确认PHP服务器正常运行
- 检查文件路径权限
- 查看服务器错误日志

## 技术细节

- **前端库**: Mol* (最新版本)
- **后端**: PHP 7.x+
- **文件格式**: PDB (蛋白质结构), JSON (元数据)
- **浏览器支持**: Chrome, Firefox, Safari, Edge (现代浏览器)

## 扩展功能

可以进一步添加：
- 序列映射和高亮
- 结构比较功能
- 分子动力学轨迹播放
- 自定义样式和主题
- 结构注释和标记功能
