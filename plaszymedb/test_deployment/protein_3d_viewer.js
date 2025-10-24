/**
 * PlaszymeDB 蛋白质3D结构查看器
 * 基于 Mol* 库实现
 * 作者: PlaszymeDB Team
 * 版本: 2.0
 */

class ProteinStructureViewer {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.molstarViewer = null;
        this.isInitialized = false;
        this.currentPlzId = null;
        this.currentDataType = 'predicted';
        this.isLoading = false;
        
        // 默认配置
        this.config = {
            layoutIsExpanded: false,
            layoutShowControls: true,
            layoutShowRemoteState: false,
            layoutShowSequence: true,
            layoutShowLog: false,
            layoutShowLeftPanel: false,
            viewportShowExpand: true,
            viewportShowSelectionMode: false,
            viewportShowAnimation: false,
            ...options
        };
        
        // 事件回调
        this.onLoadStart = options.onLoadStart || (() => {});
        this.onLoadComplete = options.onLoadComplete || (() => {});
        this.onLoadError = options.onLoadError || (() => {});
        this.onInit = options.onInit || (() => {});
        
        this.init();
    }
    
    /**
     * 初始化查看器
     */
    async init() {
        try {
            console.log('初始化蛋白质3D结构查看器...');
            
            // 创建UI容器
            this.createUI();
            
            // 等待Mol*库加载
            await this.waitForMolstar();
            
            // 初始化Mol*查看器
            await this.initMolstarViewer();
            
            this.isInitialized = true;
            this.onInit(this);
            console.log('蛋白质3D结构查看器初始化成功');
            
        } catch (error) {
            console.error('初始化失败:', error);
            this.showError('初始化失败: ' + error.message);
        }
    }
    
    /**
     * 创建用户界面
     */
    createUI() {
        this.container.innerHTML = `
            <div class="protein-viewer-container">
                <!-- 控制面板 -->
                <div class="viewer-controls">
                    <div class="control-group">
                        <label>数据类型:</label>
                        <div class="data-type-buttons">
                            <button id="${this.containerId}-btn-predicted" class="btn-data-type active" data-type="predicted">
                                预测数据
                            </button>
                            <button id="${this.containerId}-btn-experimental" class="btn-data-type" data-type="experimental">
                                实验数据
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>视图控制:</label>
                        <div class="view-controls">
                            <button id="${this.containerId}-btn-reset" class="btn-control" title="重置视图">
                                🔄 重置
                            </button>
                            <button id="${this.containerId}-btn-fullscreen" class="btn-control" title="全屏">
                                📺 全屏
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>表示方式:</label>
                        <div class="representation-controls">
                            <button id="${this.containerId}-btn-cartoon" class="btn-representation active" data-repr="cartoon">
                                卡通
                            </button>
                            <button id="${this.containerId}-btn-surface" class="btn-representation" data-repr="surface">
                                表面
                            </button>
                            <button id="${this.containerId}-btn-ball-stick" class="btn-representation" data-repr="ball-stick">
                                球棍
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>下载:</label>
                        <div class="download-controls">
                            <button id="${this.containerId}-btn-download-pdb" class="btn-download" title="下载PDB文件">
                                📁 PDB
                            </button>
                            <button id="${this.containerId}-btn-download-image" class="btn-download" title="下载结构图片">
                                🖼️ 图片
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 状态信息 -->
                <div class="viewer-status" id="${this.containerId}-status">
                    准备就绪
                </div>
                
                <!-- Mol*查看器容器 -->
                <div class="molstar-container" id="${this.containerId}-molstar">
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>正在初始化3D结构查看器...</p>
                    </div>
                </div>
            </div>
        `;
        
        // 添加样式
        this.addStyles();
        
        // 绑定事件
        this.bindEvents();
    }
    
    /**
     * 添加样式
     */
    addStyles() {
        if (document.getElementById('protein-viewer-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'protein-viewer-styles';
        style.textContent = `
            .protein-viewer-container {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .viewer-controls {
                padding: 15px;
                background: #f8f9fa;
                border-bottom: 1px solid #ddd;
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                align-items: center;
            }
            
            .control-group {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .control-group label {
                font-weight: bold;
                color: #495057;
                font-size: 0.9em;
                white-space: nowrap;
            }
            
             .data-type-buttons, .view-controls, .download-controls {
                 display: flex;
                 gap: 5px;
             }
             
             /* 隐藏表示方式控制组 */
             .control-group:has(.representation-controls) {
                 display: none !important;
             }
            
            .btn-data-type, .btn-control, .btn-representation, .btn-download {
                padding: 6px 12px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85em;
                transition: all 0.2s;
            }
            
            .btn-data-type:hover, .btn-control:hover, .btn-representation:hover, .btn-download:hover {
                background: #e9ecef;
                border-color: #adb5bd;
            }
            
            .btn-data-type.active, .btn-representation.active {
                background: #007bff;
                color: white;
                border-color: #007bff;
            }
            
            .btn-control {
                background: #6c757d;
                color: white;
                border-color: #6c757d;
            }
            
            .btn-control:hover {
                background: #5a6268;
                border-color: #545b62;
            }
            
            .btn-download {
                background: #28a745;
                color: white;
                border-color: #28a745;
            }
            
            .btn-download:hover {
                background: #218838;
                border-color: #1e7e34;
            }
            
            .viewer-status {
                padding: 8px 15px;
                background: #e9ecef;
                border-bottom: 1px solid #ddd;
                font-size: 0.9em;
                color: #495057;
            }
            
            .viewer-status.loading {
                background: #fff3cd;
                color: #856404;
            }
            
            .viewer-status.success {
                background: #d4edda;
                color: #155724;
            }
            
            .viewer-status.error {
                background: #f8d7da;
                color: #721c24;
            }
            
            .molstar-container {
                height: 500px;
                position: relative;
                background: #f8f9fa;
            }
            
            .loading-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #6c757d;
            }
            
            .loading-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #e9ecef;
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 15px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* 响应式设计 */
            @media (max-width: 768px) {
                .viewer-controls {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }
                
                .control-group {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                    width: 100%;
                }
                
                .data-type-buttons, .view-controls, .representation-controls, .download-controls {
                    width: 100%;
                    justify-content: flex-start;
                }
                
                .molstar-container {
                    height: 400px;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * 绑定事件
     */
    bindEvents() {
        // 数据类型切换
        document.querySelectorAll(`#${this.containerId} .btn-data-type`).forEach(btn => {
            btn.addEventListener('click', (e) => {
                const dataType = e.target.dataset.type;
                this.switchDataType(dataType);
            });
        });
        
        // 视图控制
        document.getElementById(`${this.containerId}-btn-reset`).addEventListener('click', () => {
            this.resetView();
        });
        
        document.getElementById(`${this.containerId}-btn-fullscreen`).addEventListener('click', () => {
            this.toggleFullscreen();
        });
        
        // 表示方式切换
        document.querySelectorAll(`#${this.containerId} .btn-representation`).forEach(btn => {
            btn.addEventListener('click', (e) => {
                const repr = e.target.dataset.repr;
                this.setRepresentation(repr);
            });
        });
        
        // 下载功能
        document.getElementById(`${this.containerId}-btn-download-pdb`).addEventListener('click', () => {
            this.downloadPDB();
        });
        
        document.getElementById(`${this.containerId}-btn-download-image`).addEventListener('click', () => {
            this.downloadImage();
        });
    }
    
    /**
     * 等待Mol*库加载
     */
    async waitForMolstar() {
        return new Promise((resolve, reject) => {
            if (typeof window.molstar !== 'undefined') {
                resolve();
                return;
            }
            
            let attempts = 0;
            const maxAttempts = 100;
            
            const checkMolstar = () => {
                attempts++;
                if (typeof window.molstar !== 'undefined') {
                    resolve();
                } else if (attempts >= maxAttempts) {
                    reject(new Error('Mol*库加载超时'));
                } else {
                    setTimeout(checkMolstar, 100);
                }
            };
            
            checkMolstar();
        });
    }
    
    /**
     * 初始化Mol*查看器
     */
    async initMolstarViewer() {
        const molstarContainer = document.getElementById(`${this.containerId}-molstar`);
        molstarContainer.innerHTML = '';
        
        this.molstarViewer = await window.molstar.Viewer.create(molstarContainer, this.config);
        
        // 设置默认表示方式
        this.currentRepresentation = 'cartoon';
    }
    
    /**
     * 加载蛋白质结构
     */
    async loadStructure(plzId, dataType = null) {
        if (!this.isInitialized) {
            throw new Error('查看器尚未初始化');
        }
        
        if (this.isLoading) {
            console.log('正在加载中，请稍候...');
            return;
        }
        
        try {
            this.isLoading = true;
            this.currentPlzId = plzId;
            this.currentDataType = dataType || this.currentDataType;
            
            this.updateStatus(`正在加载结构 ${plzId}...`, 'loading');
            this.onLoadStart(plzId, this.currentDataType);
            
            // 清除现有结构
            await this.clearCurrentStructure();
            
            // 构建API URL，使用 encodeURIComponent 正确编码 PLZ_ID (可能包含分号)
            const apiUrl = `api_protein_structure.php?plz_id=${encodeURIComponent(plzId)}&type=${this.currentDataType}&action=pdb`;
            
            console.log(`加载PDB: ${apiUrl}`);
            
            // 加载结构
            const data = await this.molstarViewer.loadStructureFromUrl(apiUrl, 'pdb', {
                format: 'pdb',
                isBinary: false
            });
            
            if (data) {
                // 设置默认表示方式
                await this.applyDefaultRepresentation();
                
                this.updateStatus(`结构已加载: ${plzId} (${this.currentDataType === 'predicted' ? '预测' : '实验'})`, 'success');
                this.onLoadComplete(plzId, this.currentDataType, data);
                
                console.log(`结构加载成功: ${plzId}`);
            } else {
                throw new Error('结构数据为空');
            }
            
        } catch (error) {
            console.error('加载结构失败:', error);
            this.updateStatus(`加载失败: ${error.message}`, 'error');
            this.onLoadError(plzId, this.currentDataType, error);
            throw error;
        } finally {
            this.isLoading = false;
        }
    }
    
    /**
     * 清除当前结构
     */
    async clearCurrentStructure() {
        if (!this.molstarViewer || !this.molstarViewer.plugin) return;
        
        try {
            // 获取插件管理器
            const plugin = this.molstarViewer.plugin;
            
            // 清除所有结构
            const structures = plugin.managers.structure.hierarchy.current.structures;
            if (structures && structures.length > 0) {
                // 清除所有结构组件
                for (const structure of structures) {
                    await plugin.managers.structure.component.clear(structure);
                }
                
                // 移除结构
                await plugin.managers.structure.hierarchy.remove(structures);
            }
            
            // 重置相机视图
            await plugin.managers.camera.reset();
            
        } catch (error) {
            console.warn('清除结构时出现警告:', error);
            // 如果上述方法失败，尝试使用更简单的重置方法
            try {
                if (this.molstarViewer.reset) {
                    await this.molstarViewer.reset({ camera: true, theme: true });
                }
            } catch (resetError) {
                console.warn('重置查看器失败:', resetError);
            }
        }
    }

    /**
     * 应用默认表示方式
     */
    async applyDefaultRepresentation() {
        try {
            await this.setRepresentation(this.currentRepresentation);
        } catch (error) {
            console.warn('设置默认表示方式失败:', error);
        }
    }
    
    /**
     * 切换数据类型
     */
    async switchDataType(dataType) {
        if (dataType === this.currentDataType) return;
        
        // 更新UI
        document.querySelectorAll(`#${this.containerId} .btn-data-type`).forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(`${this.containerId}-btn-${dataType}`).classList.add('active');
        
        this.currentDataType = dataType;
        
        // 如果有当前加载的结构，重新加载
        if (this.currentPlzId) {
            await this.loadStructure(this.currentPlzId, dataType);
        }
    }
    
    /**
     * 设置表示方式
     */
    async setRepresentation(representation) {
        if (!this.molstarViewer) return;
        
        try {
            // 更新UI
            document.querySelectorAll(`#${this.containerId} .btn-representation`).forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`${this.containerId}-btn-${representation}`).classList.add('active');
            
            this.currentRepresentation = representation;
            
            // 应用表示方式
            const plugin = this.molstarViewer.plugin;
            const structure = plugin.managers.structure.hierarchy.current.structures[0];
            
            if (structure) {
                // 清除现有表示
                await plugin.managers.structure.component.clear(structure);
                
                // 应用新表示
                let reprType;
                switch (representation) {
                    case 'surface':
                        reprType = 'molecular-surface';
                        break;
                    case 'ball-stick':
                        reprType = 'ball-and-stick';
                        break;
                    case 'cartoon':
                    default:
                        reprType = 'cartoon';
                        break;
                }
                
                await plugin.managers.structure.component.add({
                    structure,
                    representation: reprType
                });
            }
            
        } catch (error) {
            console.error('设置表示方式失败:', error);
        }
    }
    
    /**
     * 重置视图
     */
    async resetView() {
        if (this.molstarViewer) {
            try {
                await this.molstarViewer.plugin.managers.camera.reset();
                this.updateStatus('视图已重置', 'success');
            } catch (error) {
                console.error('重置视图失败:', error);
            }
        }
    }
    
    /**
     * 切换全屏
     */
    toggleFullscreen() {
        const container = this.container;
        
        if (!document.fullscreenElement) {
            container.requestFullscreen().then(() => {
                container.style.height = '100vh';
                document.getElementById(`${this.containerId}-molstar`).style.height = 'calc(100vh - 140px)';
            }).catch(err => {
                console.error('进入全屏失败:', err);
            });
        } else {
            document.exitFullscreen().then(() => {
                container.style.height = '';
                document.getElementById(`${this.containerId}-molstar`).style.height = '500px';
            }).catch(err => {
                console.error('退出全屏失败:', err);
            });
        }
    }
    
    /**
     * 下载PDB文件
     */
    async downloadPDB() {
        if (!this.currentPlzId) {
            alert('请先加载一个结构');
            return;
        }
        
        try {
            // 使用 encodeURIComponent 正确编码 PLZ_ID (可能包含分号)
            const url = `api_protein_structure.php?plz_id=${encodeURIComponent(this.currentPlzId)}&type=${this.currentDataType}&action=pdb`;
            const link = document.createElement('a');
            link.href = url;
            link.download = `${this.currentPlzId}_${this.currentDataType}.pdb`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.updateStatus('PDB文件下载已开始', 'success');
        } catch (error) {
            console.error('下载PDB失败:', error);
            this.updateStatus('下载失败', 'error');
        }
    }
    
    /**
     * 下载结构图片
     */
    async downloadImage() {
        if (!this.molstarViewer) {
            alert('请先加载一个结构');
            return;
        }
        
        try {
            const imageData = await this.molstarViewer.plugin.helpers.viewportScreenshot.getImageData();
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = imageData.width;
            canvas.height = imageData.height;
            ctx.putImageData(imageData, 0, 0);
            
            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `${this.currentPlzId || 'structure'}_${this.currentDataType}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });
            
            this.updateStatus('图片下载已开始', 'success');
        } catch (error) {
            console.error('下载图片失败:', error);
            this.updateStatus('下载图片失败', 'error');
        }
    }
    
    /**
     * 更新状态信息
     */
    updateStatus(message, type = '') {
        const statusElement = document.getElementById(`${this.containerId}-status`);
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `viewer-status ${type}`;
        }
    }
    
    /**
     * 显示错误信息
     */
    showError(message) {
        this.updateStatus(message, 'error');
    }
    
    /**
     * 获取当前状态
     */
    getState() {
        return {
            isInitialized: this.isInitialized,
            isLoading: this.isLoading,
            currentPlzId: this.currentPlzId,
            currentDataType: this.currentDataType,
            currentRepresentation: this.currentRepresentation
        };
    }
    
    /**
     * 销毁查看器
     */
    destroy() {
        if (this.molstarViewer) {
            this.molstarViewer.plugin.dispose();
            this.molstarViewer = null;
        }
        this.isInitialized = false;
        this.currentPlzId = null;
    }
}

// 全局函数，用于向后兼容
window.ProteinStructureViewer = ProteinStructureViewer;

// 便捷函数
window.createProteinViewer = function(containerId, options = {}) {
    return new ProteinStructureViewer(containerId, options);
};

console.log('蛋白质3D结构查看器类已加载');
