/**
 * PlaszymeDB 蛋白质3D结构查看器 - 性能优化版
 * 基于 Mol* 库实现
 * 版本: 2.1 (Performance Optimized)
 * 
 * 性能优化特性:
 * - 结构数据缓存
 * - 懒加载初始化
 * - 内存管理
 * - 错误恢复
 * - 预加载机制
 */

class OptimizedProteinViewer {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.molstarViewer = null;
        this.isInitialized = false;
        this.isInitializing = false;
        this.currentPlzId = null;
        this.currentDataType = 'predicted';
        this.isLoading = false;
        
        // 性能优化相关
        this.structureCache = new Map(); // 结构数据缓存
        this.maxCacheSize = 5; // 最大缓存数量
        this.preloadQueue = []; // 预加载队列
        this.loadTimeout = 30000; // 30秒超时
        this.retryAttempts = 3; // 重试次数
        
        // 配置
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
        this.onCacheUpdate = options.onCacheUpdate || (() => {});
        
        // 性能监控
        this.performanceMetrics = {
            initTime: 0,
            loadTimes: [],
            cacheHits: 0,
            cacheMisses: 0,
            errors: 0
        };
        
        // 延迟初始化
        this.lazyInit();
    }
    
    /**
     * 延迟初始化 - 只有在需要时才初始化
     */
    async lazyInit() {
        // 创建基础UI
        this.createUI();
        
        // 添加交集观察器，当容器可见时才初始化
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.isInitialized && !this.isInitializing) {
                        this.init();
                        observer.disconnect();
                    }
                });
            });
            observer.observe(this.container);
        } else {
            // 降级处理：直接初始化
            setTimeout(() => this.init(), 1000);
        }
    }
    
    /**
     * 初始化查看器
     */
    async init() {
        if (this.isInitialized || this.isInitializing) return;
        
        try {
            this.isInitializing = true;
            const startTime = performance.now();
            
            console.log('初始化优化版蛋白质3D结构查看器...');
            this.updateStatus('Initializing 3D structure viewer...', 'loading');
            
            // 等待Mol*库加载
            await this.waitForMolstar();
            
            // 初始化Mol*查看器
            await this.initMolstarViewer();
            
            // 记录初始化时间
            this.performanceMetrics.initTime = performance.now() - startTime;
            
            this.isInitialized = true;
            this.isInitializing = false;
            
            this.updateStatus('3D structure viewer ready', 'success');
            this.onInit(this);
            
            console.log(`优化版查看器初始化完成 (${this.performanceMetrics.initTime.toFixed(2)}ms)`);
            
        } catch (error) {
            this.isInitializing = false;
            this.performanceMetrics.errors++;
            console.error('初始化失败:', error);
            this.updateStatus('Initialization failed: ' + error.message, 'error');
            
            // 错误恢复：尝试重新初始化
            setTimeout(() => {
                if (!this.isInitialized) {
                    console.log('尝试重新初始化...');
                    this.init();
                }
            }, 5000);
        }
    }
    
    /**
     * 创建用户界面
     */
    createUI() {
        this.container.innerHTML = `
            <div class="protein-viewer-container">
                <!-- 控制面板 - 表示方式 -->
                <div class="viewer-controls">
                    <div class="control-group">
                        <label>Representation:</label>
                        <div class="representation-controls">
                            <button id="${this.containerId}-btn-cartoon" class="btn-representation active" data-repr="cartoon">
                                Cartoon
                            </button>
                            <button id="${this.containerId}-btn-surface" class="btn-representation" data-repr="surface">
                                Surface
                            </button>
                            <button id="${this.containerId}-btn-ball-stick" class="btn-representation" data-repr="ball-stick">
                                Ball & Stick
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 状态信息 -->
                <div class="viewer-status" id="${this.containerId}-status">
                    Ready to initialize...
                </div>
                
                <!-- Mol*查看器容器 -->
                <div class="molstar-container" id="${this.containerId}-molstar">
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>3D structure viewer will load automatically when needed...</p>
                        <button class="btn-init" onclick="window.forceInitViewer && window.forceInitViewer('${this.containerId}')">
                            Initialize Now
                        </button>
                    </div>
                </div>
                
                <!-- 数据类型控制 - 移至画布下方 -->
                <div class="viewer-controls viewer-controls-bottom">
                    <div class="control-group">
                        <label>Data Type:</label>
                        <div class="data-type-buttons">
                            <button id="${this.containerId}-btn-predicted" class="btn-data-type active" data-type="predicted">
                                Predicted Data
                            </button>
                            <button id="${this.containerId}-btn-experimental" class="btn-data-type" data-type="experimental">
                                Experimental Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // 添加样式（复用之前的样式，添加新的性能相关样式）
        this.addStyles();
        
        // 绑定事件
        this.bindEvents();
        
        // 设置强制初始化函数
        window.forceInitViewer = (containerId) => {
            if (containerId === this.containerId) {
                this.init();
            }
        };
    }
    
    /**
     * 添加样式（包含性能优化相关样式）
     */
    addStyles() {
        if (document.getElementById('optimized-protein-viewer-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'optimized-protein-viewer-styles';
        style.textContent = `
            .protein-viewer-container {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: visible;
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
            
            .viewer-controls-bottom {
                border-bottom: none;
                border-top: 1px solid #ddd;
                justify-content: center;
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
            
            .performance-info {
                font-family: 'Courier New', monospace;
                background: rgba(0,123,255,0.1);
                padding: 4px 8px;
                border-radius: 4px;
                border: 1px solid rgba(0,123,255,0.2);
            }
            
             .data-type-buttons, .view-controls, .download-controls {
                 display: flex;
                 gap: 5px;
             }
             
             /* 隐藏表示方式控制组 */
             .control-group:has(.representation-controls) {
                 display: none !important;
             }
            
            .btn-data-type, .btn-control, .btn-representation, .btn-download, .btn-init {
                padding: 6px 12px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85em;
                transition: all 0.2s;
            }
            
            .btn-init {
                background: #28a745;
                color: white;
                border-color: #28a745;
                margin-top: 15px;
            }
            
            .btn-init:hover {
                background: #218838;
                border-color: #1e7e34;
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
                height: 400px;
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
            
            /* 缓存状态指示器 */
            .cache-indicator {
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                margin-right: 5px;
            }
            
            .cache-hit {
                background-color: #28a745;
            }
            
            .cache-miss {
                background-color: #dc3545;
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
                // 使用 currentTarget 而不是 target，确保获取到按钮本身的 dataset
                const dataType = e.currentTarget.dataset.type;
                if (dataType) {
                    this.switchDataType(dataType);
                }
            });
        });
        
        // 表示方式切换
        document.querySelectorAll(`#${this.containerId} .btn-representation`).forEach(btn => {
            btn.addEventListener('click', (e) => {
                // 使用 currentTarget 而不是 target
                const repr = e.currentTarget.dataset.repr;
                if (repr) {
                    this.setRepresentation(repr);
                }
            });
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
        if (!molstarContainer) {
            throw new Error('Mol*容器元素未找到');
        }
        
        molstarContainer.innerHTML = '';
        
        // 确保容器有合适的尺寸
        if (!molstarContainer.style.width) {
            molstarContainer.style.width = '100%';
        }
        if (!molstarContainer.style.height) {
            molstarContainer.style.height = '500px';
        }
        
        // 添加延迟确保DOM完全渲染
        await new Promise(resolve => setTimeout(resolve, 100));
        
        try {
            this.molstarViewer = await window.molstar.Viewer.create(molstarContainer, {
                ...this.config,
                layoutIsExpanded: false,
                layoutShowControls: false,
                layoutShowRemoteState: false,
                layoutShowSequence: true,
                layoutShowLog: false,
                layoutShowLeftPanel: false,
                viewportShowExpand: false,
                viewportShowSelectionMode: false,
                viewportShowAnimation: false,
            });
            this.currentRepresentation = 'cartoon';
            console.log('Mol*查看器创建成功');
        } catch (error) {
            console.error('Mol*查看器创建失败:', error);
            throw error;
        }
    }
    
    /**
     * 使用正确的Mol*API加载PDB结构
     */
    async loadPdbFromUrl(url) {
        try {
            console.log('开始加载PDB数据:', url);
            
            // 首先获取PDB数据
            const response = await fetch(url);
            if (!response.ok) {
                // 尝试解析JSON错误消息
                let errorMessage = null;
                try {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        if (errorData && errorData.message) {
                            errorMessage = errorData.message;
                        }
                    }
                } catch (e) {
                    // JSON解析失败，忽略
                    console.log('无法解析错误响应为JSON:', e);
                }
                
                // 如果有JSON错误消息，使用它
                if (errorMessage) {
                    throw new Error(errorMessage);
                }
                
                // 否则使用默认消息
                if (response.status === 404) {
                    throw new Error('No structure data available for this protein');
                }
                throw new Error(`HTTP错误: ${response.status} - ${response.statusText}`);
            }
            
            const pdbText = await response.text();
            if (!pdbText || pdbText.trim().length === 0) {
                throw new Error('PDB file content is empty');
            }
            
            // 验证PDB格式
            if (!pdbText.includes('ATOM') && !pdbText.includes('HETATM')) {
                throw new Error('Invalid PDB format - missing ATOM or HETATM records');
            }
            
            console.log('PDB数据获取成功，大小:', pdbText.length, '字节');
            
            // 使用Mol*的正确API加载结构
            const plugin = this.molstarViewer.plugin;
            
            // 清除现有结构
            await plugin.clear();
            
            // 创建数据源
            const data = await plugin.builders.data.rawData({
                data: pdbText,
                label: url.split('/').pop() || 'structure'
            });
            
            // 解析PDB格式
            const trajectory = await plugin.builders.structure.parseTrajectory(data, 'pdb');
            
            // 创建模型
            const model = await plugin.builders.structure.createModel(trajectory);
            
            // 创建结构
            const structure = await plugin.builders.structure.createStructure(model);
            
            // 验证结构是否创建成功
            if (!structure || !structure.cell || !structure.cell.obj) {
                throw new Error('Structure creation failed');
            }
            
            // 添加默认表示方式
            await plugin.builders.structure.representation.addRepresentation(structure, {
                type: 'cartoon',
                colorTheme: { name: 'chain-id' },
                sizeTheme: { name: 'uniform' }
            });
            
            console.log('结构创建成功:', structure);
            return structure;
            
        } catch (error) {
            console.error('PDB加载错误:', error);
            throw error;
        }
    }

    /**
     * 生成缓存键
     */
    getCacheKey(plzId, dataType) {
        return `${plzId}_${dataType}`;
    }
    
    /**
     * 缓存管理 - 添加到缓存
     */
    addToCache(plzId, dataType, data) {
        const key = this.getCacheKey(plzId, dataType);
        
        // 如果缓存已满，删除最旧的条目
        if (this.structureCache.size >= this.maxCacheSize) {
            const firstKey = this.structureCache.keys().next().value;
            this.structureCache.delete(firstKey);
        }
        
        this.structureCache.set(key, {
            data: data,
            timestamp: Date.now(),
            plzId: plzId,
            dataType: dataType
        });
        
        this.updateCacheInfo();
        this.onCacheUpdate(this.structureCache.size, this.maxCacheSize);
    }
    
    /**
     * 从缓存获取数据
     */
    getFromCache(plzId, dataType) {
        const key = this.getCacheKey(plzId, dataType);
        const cached = this.structureCache.get(key);
        
        if (cached) {
            // 更新访问时间
            cached.timestamp = Date.now();
            this.structureCache.delete(key);
            this.structureCache.set(key, cached);
            this.performanceMetrics.cacheHits++;
            return cached.data;
        }
        
        this.performanceMetrics.cacheMisses++;
        return null;
    }
    
    /**
     * 更新缓存信息显示（已禁用前端显示）
     */
    updateCacheInfo() {
        // 缓存信息不再在前端显示，仅在控制台记录
        console.log(`缓存状态: ${this.structureCache.size}/${this.maxCacheSize}`);
    }
    
    /**
     * 更新性能信息显示（已禁用前端显示）
     */
    updatePerformanceInfo(loadTime) {
        // 性能信息不再在前端显示，仅在控制台记录
        const avgLoadTime = this.performanceMetrics.loadTimes.length > 0
            ? this.performanceMetrics.loadTimes.reduce((a, b) => a + b, 0) / this.performanceMetrics.loadTimes.length
            : 0;
        console.log(`性能: ${loadTime ? loadTime.toFixed(0) : avgLoadTime.toFixed(0)}ms`);
    }
    
    /**
     * 加载蛋白质结构 - 优化版本
     */
    async loadStructure(plzId, dataType = null) {
        // 确保查看器已初始化
        if (!this.isInitialized) {
            await this.init();
        }
        
        if (this.isLoading) {
            console.log('正在加载中，请稍候...');
            return;
        }
        
        const startTime = performance.now();
        let isFromCache = false;
        
        try {
            this.isLoading = true;
            this.currentPlzId = plzId;
            this.currentDataType = dataType || this.currentDataType;
            
            this.updateStatus(`Loading structure ${plzId}...`, 'loading');
            this.onLoadStart(plzId, this.currentDataType);
            
            // 检查缓存
            const cachedData = this.getFromCache(plzId, this.currentDataType);
            
            let data;
            if (cachedData) {
                console.log(`从缓存加载结构: ${plzId}`);
                isFromCache = true;
                
                // 清除现有结构并加载缓存数据
                await this.clearCurrentStructure();
                // 使用正确的Mol*API加载结构
                data = await this.loadPdbFromUrl(cachedData);
            } else {
                // 从API加载
                console.log(`从API加载结构: ${plzId}`);
                await this.clearCurrentStructure();
                
                const apiUrl = `api_protein_structure.php?plz_id=${plzId}&type=${this.currentDataType}&action=pdb`;
                
                // 添加超时控制
                const loadPromise = this.loadPdbFromUrl(apiUrl);
                
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Loading timeout')), this.loadTimeout);
                });
                
                data = await Promise.race([loadPromise, timeoutPromise]);
                
                // 添加到缓存
                if (data) {
                    this.addToCache(plzId, this.currentDataType, apiUrl);
                }
            }
            
            if (data) {
                // 设置默认表示方式
                await this.applyDefaultRepresentation();
                
                const loadTime = performance.now() - startTime;
                this.performanceMetrics.loadTimes.push(loadTime);
                
                // 保持最近10次的加载时间记录
                if (this.performanceMetrics.loadTimes.length > 10) {
                    this.performanceMetrics.loadTimes.shift();
                }
                
                this.updatePerformanceInfo(loadTime);
                
                const cacheStatus = isFromCache ? ' (cached)' : ' (network)';
                this.updateStatus(`Structure loaded: ${plzId}${cacheStatus}`, 'success');
                this.onLoadComplete(plzId, this.currentDataType, data);
                
                console.log(`结构加载成功: ${plzId} (${loadTime.toFixed(2)}ms)${cacheStatus}`);
                
                // 预加载相关结构
                this.schedulePreload(plzId);
                
            } else {
                throw new Error('Structure data is empty');
            }
            
        } catch (error) {
            this.performanceMetrics.errors++;
            console.error('加载结构失败:', error);
            this.updateStatus(`Loading failed: ${error.message}`, 'error');
            this.onLoadError(plzId, this.currentDataType, error);
            
            // 错误恢复：尝试重试
            if ((error.message.includes('超时') || error.message.includes('No suitable parent found') || error.message.includes('PDB数据加载失败')) && this.retryAttempts > 0) {
                console.log(`加载失败，将在3秒后重试 (剩余重试次数: ${this.retryAttempts})`);
                this.retryAttempts--;
                
                // 如果是Mol*内部错误，尝试重新初始化查看器
                if (error.message.includes('No suitable parent found')) {
                    console.log('检测到Mol*内部错误，尝试重新初始化查看器...');
                    this.isInitialized = false;
                    this.molstarViewer = null;
                }
                
                setTimeout(() => {
                    this.loadStructure(plzId, dataType);
                }, 3000);
                return;
            }
            
            throw error;
        } finally {
            this.isLoading = false;
            this.retryAttempts = 3; // 重置重试次数
        }
    }
    
    /**
     * 调度预加载
     */
    schedulePreload(currentPlzId) {
        // 这里可以根据业务逻辑预加载相关的结构
        // 例如：同一酶家族的其他结构
        // 目前简单实现：不进行预加载以避免不必要的网络请求
    }
    
    /**
     * 清除当前结构
     */
    async clearCurrentStructure() {
        if (!this.molstarViewer || !this.molstarViewer.plugin) return;
        
        try {
            const plugin = this.molstarViewer.plugin;
            
            // 使用Mol*推荐的清除方法
            await plugin.clear();
            
            console.log('结构已清除');
            
        } catch (error) {
            console.warn('清除结构时出现警告:', error);
            // 如果清除失败，尝试强制清除状态树
            try {
                const plugin = this.molstarViewer.plugin;
                const state = plugin.state.data;
                
                // 删除所有状态对象
                const updates = state.build();
                const rootChildren = Array.from(state.tree.root.children || []);
                for (const obj of rootChildren) {
                    updates.delete(obj.ref);
                }
                await updates.commit();
                
                console.log('使用备用方法清除结构');
            } catch (fallbackError) {
                console.warn('备用清除方法也失败:', fallbackError);
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
        console.log(`尝试切换数据类型: ${dataType}, 当前类型: ${this.currentDataType}`);
        
        if (dataType === this.currentDataType) {
            console.log('数据类型相同，无需切换');
            return;
        }
        
        const oldDataType = this.currentDataType;
        
        try {
            // 更新UI
            document.querySelectorAll(`#${this.containerId} .btn-data-type`).forEach(btn => {
                btn.classList.remove('active');
            });
            
            const targetBtn = document.getElementById(`${this.containerId}-btn-${dataType}`);
            if (!targetBtn) {
                console.error(`找不到按钮: ${this.containerId}-btn-${dataType}`);
                return;
            }
            targetBtn.classList.add('active');
            
            this.currentDataType = dataType;
            console.log(`数据类型已更新为: ${dataType}`);
            
            // 如果有当前加载的结构，重新加载
            if (this.currentPlzId) {
                await this.loadStructure(this.currentPlzId, dataType);
            }
        } catch (error) {
            // 只记录错误，不自动切换回去
            console.warn(`切换到${dataType}失败:`, error);
            // 用户可以手动点击其他按钮切换
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
            const targetBtn = document.getElementById(`${this.containerId}-btn-${representation}`);
            if (targetBtn) {
                targetBtn.classList.add('active');
            }
            
            this.currentRepresentation = representation;
            
            // 应用表示方式
            const plugin = this.molstarViewer.plugin;
            const state = plugin.state.data;
            
            // 使用正确的 Mol* API 查找结构和组件节点
            const structureRefs = [];
            const componentRefs = [];
            
            // 遍历状态树 - state.tree.root.children 是一个 Set 或类似结构
            const rootChildren = Array.from(state.tree.root.children || []);
            
            for (const child of rootChildren) {
                const cell = state.cells.get(child.ref);
                if (!cell) continue;
                
                // 查找结构对象
                if (cell.obj && cell.obj.type === 'structure') {
                    structureRefs.push(child.ref);
                }
                
                // 查找表示组件（representations）
                const childChildren = Array.from(child.children || []);
                for (const grandchild of childChildren) {
                    const compCell = state.cells.get(grandchild.ref);
                    if (compCell && compCell.transform && compCell.transform.transformer) {
                        const transformerName = compCell.transform.transformer.definition.name;
                        if (transformerName && transformerName.includes('representation')) {
                            componentRefs.push(grandchild.ref);
                        }
                    }
                }
            }
            
            if (structureRefs.length > 0) {
                // 清除所有现有的表示组件
                if (componentRefs.length > 0) {
                    const updates = state.build();
                    for (const ref of componentRefs) {
                        updates.delete(ref);
                    }
                    await updates.commit();
                }
                
                // 确定表示类型
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
                
                // 为第一个结构添加新表示
                const structureRef = structureRefs[0];
                const structureCell = state.cells.get(structureRef);
                
                if (structureCell && structureCell.obj) {
                    await plugin.builders.structure.representation.addRepresentation(structureCell, {
                        type: reprType,
                        color: 'chain-id'
                    });
                    
                    console.log(`表示方式已切换至: ${reprType}`);
                }
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
                this.updateStatus('View reset', 'success');
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
            const url = `api_protein_structure.php?plz_id=${this.currentPlzId}&type=${this.currentDataType}&action=pdb`;
            const link = document.createElement('a');
            link.href = url;
            link.download = `${this.currentPlzId}_${this.currentDataType}.pdb`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.updateStatus('PDB file download started', 'success');
        } catch (error) {
            console.error('下载PDB失败:', error);
            this.updateStatus('Download failed', 'error');
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
            const plugin = this.molstarViewer.plugin;
            
            // 方法1: 尝试使用canvas
            if (plugin.canvas3d && plugin.canvas3d.webgl && plugin.canvas3d.webgl.gl && plugin.canvas3d.webgl.gl.canvas) {
                const canvas = plugin.canvas3d.webgl.gl.canvas;
                canvas.toBlob((blob) => {
                    if (blob) {
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = `${this.currentPlzId || 'structure'}_${this.currentDataType}.png`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                        this.updateStatus('Image download started', 'success');
                    } else {
                        throw new Error('无法生成图片数据');
                    }
                });
                return;
            }
            
            // 方法2: 尝试使用toImageData方法
            if (plugin.helpers && plugin.helpers.viewportScreenshot && plugin.helpers.viewportScreenshot.toImageData) {
                const imageData = await plugin.helpers.viewportScreenshot.toImageData();
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
                return;
            }
            
            // 方法3: 如果上述方法都不可用，尝试直接访问canvas
            const canvasElements = document.querySelectorAll(`#${this.containerId} canvas`);
            if (canvasElements.length > 0) {
                const mainCanvas = canvasElements[canvasElements.length - 1]; // 取最后一个canvas（通常是主渲染canvas）
                mainCanvas.toBlob((blob) => {
                    if (blob) {
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = `${this.currentPlzId || 'structure'}_${this.currentDataType}.png`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                        this.updateStatus('Image download started', 'success');
                    } else {
                        throw new Error('无法生成图片数据');
                    }
                });
                return;
            }
            
            throw new Error('Unable to find available screenshot method');
            
        } catch (error) {
            console.error('下载图片失败:', error);
            this.updateStatus('Image download failed', 'error');
            alert('Image download failed: ' + error.message);
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
     * 获取性能指标
     */
    getPerformanceMetrics() {
        return {
            ...this.performanceMetrics,
            cacheSize: this.structureCache.size,
            maxCacheSize: this.maxCacheSize,
            cacheHitRate: this.performanceMetrics.cacheHits + this.performanceMetrics.cacheMisses > 0 
                ? this.performanceMetrics.cacheHits / (this.performanceMetrics.cacheHits + this.performanceMetrics.cacheMisses)
                : 0
        };
    }
    
    /**
     * 清除缓存
     */
    clearCache() {
        this.structureCache.clear();
        this.updateCacheInfo();
        console.log('结构缓存已清除');
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
            currentRepresentation: this.currentRepresentation,
            cacheSize: this.structureCache.size,
            performanceMetrics: this.getPerformanceMetrics()
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
        
        // 清理缓存
        this.clearCache();
        
        // 重置状态
        this.isInitialized = false;
        this.isInitializing = false;
        this.currentPlzId = null;
        
        console.log('优化版蛋白质查看器已销毁');
    }
}

// 全局导出
window.OptimizedProteinViewer = OptimizedProteinViewer;

// 向后兼容
window.ProteinStructureViewer = OptimizedProteinViewer;

// 便捷函数
window.createOptimizedProteinViewer = function(containerId, options = {}) {
    return new OptimizedProteinViewer(containerId, options);
};

console.log('优化版蛋白质3D结构查看器类已加载');
