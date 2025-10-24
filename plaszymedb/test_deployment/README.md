# PlaszymeDB 搜索功能安装说明

## 环境要求
- PHP 7.4 或更高版本
- MySQL 8.0 或更高版本
- Web服务器 (Apache/Nginx)

## 安装步骤

### 1. 数据库配置
1. 编辑 `db_config.php` 文件
2. 修改数据库连接参数：
   ```php
   define('DB_HOST', 'localhost');     // 数据库主机
   define('DB_NAME', 'plaszymedb');    // 数据库名称
   define('DB_USER', 'your_username'); // 数据库用户名
   define('DB_PASS', 'your_password'); // 数据库密码
   ```

### 2. 文件部署
确保以下文件在同一目录下：
- `V9.html` - 主页面文件
- `search.php` - 搜索接口
- `db_config.php` - 数据库配置

### 3. 数据库结构
确保您的 `PlaszymeDB` 表包含以下字段：
- PLZ_ID (varchar)
- enzyme_name (varchar)
- plastic (varchar) 
- ec_number (varchar)
- taxonomy (varchar)
- host_organism (varchar)
- gene_name (varchar)
- sequence (text)
- genbank_ids (varchar)
- uniprot_ids (varchar)
- pdb_ids (varchar)
- refseq_ids (varchar)
- mgnify_ids (varchar)
- reference (text)
- source_name (varchar)
- sequence_source (varchar)
- structure_source (varchar)
- original_index (varchar)
- Other_PLZ_ID (varchar)
- label (int)

### 4. 功能说明
- **全文搜索**: 在搜索框中输入关键词，将搜索所有字段
- **筛选器**: 可以使用塑料类型、EC分类、宿主生物进行筛选
- **组合搜索**: 可以同时使用搜索词和筛选器
- **结果显示**: 最多显示50条结果，显示总数统计

### 5. 使用方法
1. 在浏览器中打开 `V9.html`
2. 在搜索框中输入关键词（酶名、EC编号、序列、基因名等）
3. 可选择性地使用下拉筛选器
4. 点击"搜索"按钮或按回车键执行搜索
5. 查看搜索结果表格

## 故障排除
- 如果出现数据库连接错误，请检查 `db_config.php` 中的连接参数
- 如果搜索无结果，请确认数据库中有数据
- 如果页面无法加载，请确认Web服务器配置正确
