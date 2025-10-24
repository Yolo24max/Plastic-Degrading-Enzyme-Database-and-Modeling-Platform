# 系统发育树构建工具指南

## 方案1：在线工具 Phylogeny.fr（推荐）
**优点：无需安装，操作简单，适合快速构建**

1. 访问：https://www.phylogeny.fr/
2. 选择 "One Click" 模式
3. 上传FASTA文件（建议先从小文件开始，如fungal_enzymes.fasta）
4. 等待处理完成
5. 下载Newick格式的树文件

## 方案2：MEGA软件（Windows图形界面）
**官方网站：** https://www.megasoftware.net/
**下载地址：** https://www.megasoftware.net/download

### 安装步骤：
1. 下载MEGA 11.0.13 for Windows
2. 安装软件
3. 打开MEGA，选择"Construct/Test Maximum Likelihood Tree"
4. 导入FASTA文件
5. 进行序列比对
6. 构建系统发育树

## 方案3：IQ-TREE（命令行，专业）
**下载地址：** http://www.iqtree.org/

### Windows安装：
1. 下载Windows版本的iqtree.exe
2. 将可执行文件放入系统PATH或项目目录
3. 运行命令构建树

## 方案4：使用conda安装IQ-TREE
```bash
# 如果已安装conda/miniconda
conda install -c bioconda iqtree

# 然后运行构建命令
iqtree -s comprehensive_enzymes.fasta -m MFP -bb 1000
```

## 文件说明
处理完成后，您将获得：
- .treefile：Newick格式的系统发育树文件（用于iTOL上传）
- .log：构建日志
- .iqtree：详细报告

## 下一步
1. 获得.treefile文件后
2. 注册iTOL账户：https://itol.embl.de/
3. 上传树文件创建项目
4. 获取项目ID
5. 更新PlaszymeDB配置
