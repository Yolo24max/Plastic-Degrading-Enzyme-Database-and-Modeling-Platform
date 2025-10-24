# iTOL项目配置指南

## 问题说明

PHYLOGENY页面出现 "Unknown user or project key: aixuewang2024" 错误，这是因为iTOL项目ID配置不正确。

## 解决方案

### 1. 修复已完成
- ✅ 将无效的项目ID `'aixuewang2024'` 改为 `'demo_tree'`
- ✅ 添加了备用模式，当项目ID无效时显示iTOL主页
- ✅ 添加了用户友好的错误提示

### 2. 如何创建有效的iTOL项目

要使用专用的系统发育树，需要：

1. **注册iTOL账户**
   - 访问 https://itol.embl.de/
   - 注册免费账户

2. **上传系统发育树文件**
   - 准备各数据集的树文件（.newick, .tree格式）
   - 在iTOL中创建新项目
   - 上传对应的树文件

3. **设置项目为公开分享**
   - 在项目设置中启用"Public sharing"
   - 获取分享的项目ID

4. **更新配置**
   修改 `interactive_tree.php` 中的项目映射：
   ```php
   $itol_projects = [
       'comprehensive' => 'YOUR_COMPREHENSIVE_PROJECT_ID',
       'pet' => 'YOUR_PET_PROJECT_ID',
       'pe_pp' => 'YOUR_PE_PP_PROJECT_ID',
       'ec31' => 'YOUR_EC31_PROJECT_ID', 
       'bacterial' => 'YOUR_BACTERIAL_PROJECT_ID',
       'fungal' => 'YOUR_FUNGAL_PROJECT_ID'
   ];
   ```

### 3. 当前状态

- **临时解决方案**: 使用iTOL演示页面
- **用户体验**: 添加了说明提示，告知用户当前状态
- **功能完整性**: 数据库集成正常，序列统计正确显示

### 4. 下一步操作

要完全解决此问题，需要：

1. 从PlaszymeDB数据生成系统发育树
2. 在iTOL平台创建对应项目
3. 更新项目ID配置
4. 测试所有数据集的树显示

### 5. 备用方案

如果暂时无法创建iTOL项目，可以考虑：

- 使用本地系统发育树查看器
- 集成其他在线系统发育树工具
- 提供树文件下载功能

## 文件修改记录

- `interactive_tree.php`: 添加错误处理和备用模式
- 添加用户友好的错误提示
- 保持数据库集成功能正常
