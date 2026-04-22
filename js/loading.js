// 全局加载状态管理
const Loading = {
    container: null,
    
    init() {
        this.container = document.querySelector('.loading-container');
    },
    
    show() {
        return new Promise((resolve) => {
            if (!this.container) {
                this.init();
            }
            
            this.container.classList.add('active');
            
            // 1.5秒后自动关闭加载动画
            setTimeout(() => {
                this.hide();
                resolve();
            }, 1500);
        });
    },
    
    hide() {
        if (!this.container) {
            this.init();
        }
        this.container.classList.remove('active');
    },

    // 显示按钮加载状态
    showButton(button, text = '加载中...') {
        if (!button) return;
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.classList.add('btn-loading');
    },

    // 恢复按钮状态
    hideButton(button) {
        if (!button) return;
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.classList.remove('btn-loading');
    },

    // 创建加载点
    createDots(container) {
        const dots = document.createElement('div');
        dots.className = 'loading-dots';
        dots.innerHTML = `
            <span class="loading-dot"></span>
            <span class="loading-dot"></span>
            <span class="loading-dot"></span>
        `;
        if (container) {
            container.appendChild(dots);
        }
        return dots;
    },

    // 创建进度条
    createProgressBar(container) {
        const bar = document.createElement('div');
        bar.className = 'loading-bar';
        if (container) {
            container.appendChild(bar);
        }
        return bar;
    }
}; 