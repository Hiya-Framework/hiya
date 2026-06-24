<?php
/**
 * Hiya Web Log View Template - Debug Console with Persistent State
 * Features: All states (minimize, maximize, position, collapsed-side) saved to localStorage
 * 
 * Security: localStorage only stores UI state, no sensitive data
 * 
 * @var array $logs
 * @var array $appInfo
 * @var array $config
 */
?>
<style>
    :root {
        --hiya-primary: #3b82f6;
        --hiya-primary-dark: #2563eb;
        --hiya-success: #10b981;
        --hiya-warning: #f59e0b;
        --hiya-error: #ef4444;
        --hiya-bg: #ffffff;
        --hiya-bg-alt: #f8fafc;
        --hiya-border: #e2e8f0;
        --hiya-text: #1e293b;
        --hiya-text-muted: #64748b;
    }
    
    .hiya-debug-bar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--hiya-bg);
        color: var(--hiya-text);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, monospace;
        font-size: 12px;
        z-index: 99999;
        border-top: 3px solid var(--hiya-primary);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        height: 400px;
    }
    
    /* Position Top */
    .hiya-debug-bar.position-top {
        top: 0;
        bottom: auto;
        border-top: none;
        border-bottom: 3px solid var(--hiya-primary);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    /* Collapsed Side - Collapsed to left */
    .hiya-debug-bar.collapsed-side {
        left: 0;
        top: 50%;
        bottom: auto;
        right: auto;
        transform: translateY(-50%);
        width: auto;
        height: auto;
        min-width: 32px;
        max-width: 32px;
        border-radius: 0 8px 8px 0;
        border-top: none;
        border-right: 3px solid var(--hiya-primary);
        border-left: none;
        overflow: hidden;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        background: var(--hiya-primary);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .hiya-debug-bar.collapsed-side:hover {
        min-width: 36px;
        max-width: 36px;
        background: var(--hiya-primary-dark);
    }
    
    .hiya-debug-bar.collapsed-side .hiya-log-content,
    .hiya-debug-bar.collapsed-side .hiya-info-panel,
    .hiya-debug-bar.collapsed-side .hiya-filter-bar,
    .hiya-debug-bar.collapsed-side .hiya-debug-header {
        display: none !important;
    }
    
    /* Expand Button */
    .hiya-expand-btn {
        position: fixed;
        left: 0;
        bottom: 20px;
        background: linear-gradient(135deg, var(--hiya-primary) 0%, var(--hiya-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 0 8px 8px 0;
        width: auto;
        min-width: 32px;
        height: 40px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 99998;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
        padding: 0 8px;
        margin: 0;
    }

    .hiya-expand-btn:hover {
        min-width: 90px;
        padding-right: 12px;
        gap: 6px;
        background: linear-gradient(135deg, var(--hiya-primary-dark) 0%, var(--hiya-primary) 100%);
        box-shadow: 3px 0 12px rgba(59, 130, 246, 0.3);
    }

    .hiya-expand-btn .expand-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
        transition: all 0.25s ease;
        font-size: 11px;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .hiya-expand-btn:hover .expand-text {
        opacity: 1;
        width: auto;
        margin-left: 4px;
    }

    .hiya-expand-btn .expand-icon {
        font-size: 12px;
        transition: transform 0.2s ease;
        line-height: 1;
    }

    .hiya-expand-btn:hover .expand-icon {
        transform: translateX(3px);
    }
    
    /* Hide expand button when debug bar is normal */
    .hiya-expand-btn.hidden {
        display: none;
    }
    
    /* Maximized/Fullscreen state */
    .hiya-debug-bar.maximized {
        position: fixed;
        top: 0 !important;
        left: 0;
        right: 0;
        bottom: 0;
        height: 100vh !important;
        width: 100vw !important;
        z-index: 999999;
        border-top: none;
        border-bottom: none;
        border-radius: 0;
    }
    
    .hiya-debug-bar.maximized .hiya-log-content {
        max-height: calc(100vh - 200px);
    }
    
    /* Minimized state (bottom bar minimized) */
    .hiya-debug-bar.minimized {
        height: auto !important;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
    }
    
    .hiya-debug-bar.minimized .hiya-log-content,
    .hiya-debug-bar.minimized .hiya-info-panel,
    .hiya-debug-bar.minimized .hiya-filter-bar {
        display: none !important;
    }
    
    .hiya-debug-bar.minimized .hiya-debug-header {
        border-bottom: none;
        background: rgba(248, 250, 252, 0.9);
        padding: 6px 20px;
    }
    
    .hiya-debug-bar.minimized .hiya-debug-stats span {
        padding: 2px 8px;
        font-size: 10px;
    }
    
    .hiya-debug-header {
        background: linear-gradient(135deg, var(--hiya-bg-alt) 0%, #f1f5f9 100%);
        padding: 10px 20px;
        border-bottom: 1px solid var(--hiya-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.2s;
    }
    
    .hiya-debug-header:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }
    
    .hiya-debug-title {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .hiya-debug-title strong {
        color: var(--hiya-primary);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .hiya-debug-stats {
        display: flex;
        gap: 20px;
        font-size: 11px;
    }
    
    .hiya-debug-stats span {
        color: var(--hiya-text-muted);
        background: white;
        padding: 4px 10px;
        border-radius: 20px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-width: 70px;
        justify-content: center;
        white-space: nowrap;
    }
    
    .hiya-debug-actions {
        display: flex;
        gap: 10px;
    }
    
    .hiya-debug-btn {
        background: #ffffff;
        border: 1px solid var(--hiya-border);
        color: var(--hiya-text-muted);
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .hiya-debug-btn:hover {
        background: var(--hiya-primary);
        border-color: var(--hiya-primary);
        color: white;
        transform: translateY(-1px);
    }
    
    .hiya-debug-btn.primary {
        background: var(--hiya-primary);
        border-color: var(--hiya-primary);
        color: white;
    }
    
    /* Position Toggle Button Group */
    .hiya-pos-group {
        display: inline-flex;
        gap: 4px;
        margin-left: 8px;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 2px;
    }
    
    .hiya-pos-btn {
        background: transparent;
        border: none;
        color: var(--hiya-text-muted);
        padding: 4px 8px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .hiya-pos-btn:hover {
        background: #e2e8f0;
    }
    
    .hiya-pos-btn.active {
        background: var(--hiya-primary);
        color: white;
    }
    
    .hiya-filter-bar {
        padding: 12px 20px;
        background: var(--hiya-bg-alt);
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
        border-bottom: 1px solid var(--hiya-border);
        flex-shrink: 0;
    }
    
    .hiya-filter-search {
        background: #ffffff;
        border: 1px solid var(--hiya-border);
        color: var(--hiya-text);
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 12px;
        width: 260px;
        transition: all 0.2s;
    }
    
    .hiya-filter-search:focus {
        outline: none;
        border-color: var(--hiya-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .hiya-filter-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .hiya-filter-checkbox {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        cursor: pointer;
        padding: 4px 8px;
        background: white;
        border-radius: 20px;
        border: 1px solid var(--hiya-border);
        transition: all 0.2s;
    }
    
    .hiya-filter-checkbox:hover {
        background: var(--hiya-bg-alt);
    }
    
    .hiya-info-panel {
        padding: 10px 20px;
        background: linear-gradient(135deg, #eff6ff 0%, #fef3c7 100%);
        border-bottom: 1px solid var(--hiya-border);
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        font-size: 11px;
        color: var(--hiya-text-muted);
        flex-shrink: 0;
    }
    
    .hiya-info-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .hiya-info-label {
        color: #64748b;
        font-weight: 500;
    }
    
    .hiya-info-value {
        color: var(--hiya-primary);
        font-weight: 600;
        font-family: monospace;
    }
    
    .hiya-log-content {
        overflow: auto;
        flex: 1;
        background: var(--hiya-bg);
        max-height: calc(400px - 150px);
    }
    
    .hiya-log-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .hiya-log-table th {
        background: var(--hiya-bg-alt);
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        border-bottom: 2px solid var(--hiya-border);
        color: var(--hiya-text-muted);
    }
    
    .hiya-log-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--hiya-border);
        vertical-align: top;
        font-size: 12px;
    }
    
    .hiya-log-table tr:hover {
        background: var(--hiya-bg-alt);
    }
    
    .hiya-level-error { color: var(--hiya-error); font-weight: 600; }
    .hiya-level-warning { color: var(--hiya-warning); font-weight: 600; }
    .hiya-level-info { color: var(--hiya-primary); font-weight: 600; }
    .hiya-level-trace { color: #6b7280; }
    .hiya-level-profile { color: var(--hiya-success); }
    
    .hiya-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .hiya-badge-error { background: #fee2e2; color: var(--hiya-error); }
    .hiya-badge-warning { background: #fef3c7; color: var(--hiya-warning); }
    .hiya-badge-info { background: #dbeafe; color: var(--hiya-primary); }
    .hiya-badge-trace { background: #f1f5f9; color: #6b7280; }
    .hiya-badge-profile { background: #d1fae5; color: var(--hiya-success); }
    
    .hiya-log-json {
        margin: 0;
        font-size: 11px;
        white-space: pre-wrap;
        word-break: break-word;
        background: #1e293b;
        color: #e2e8f0;
        padding: 8px 12px;
        border-radius: 8px;
        font-family: 'Fira Code', monospace;
    }
    
    .hiya-log-sql {
        margin: 0;
        font-size: 11px;
        white-space: pre-wrap;
        word-break: break-word;
        background: #f1f5f9;
        padding: 8px 12px;
        border-radius: 8px;
        font-family: 'Fira Code', monospace;
        border-left: 3px solid var(--hiya-success);
    }
    
    .hiya-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 100000;
        align-items: center;
        justify-content: center;
    }
    
    .hiya-modal.show {
        display: flex;
    }
    
    .hiya-modal-content {
        background: white;
        border-radius: 16px;
        padding: 24px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 35px rgba(0,0,0,0.2);
    }
    
    .hiya-modal-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 16px;
    }
    
    .hiya-modal-buttons {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        justify-content: flex-end;
    }
    
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--hiya-bg-alt); border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--hiya-primary); }
    
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(20px); }
        15% { opacity: 1; transform: translateY(0); }
        85% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(-20px); }
    }
</style>

<!-- Expand button at bottom left (only appears when collapsed) -->
<button id="hiya-expand-btn" class="hiya-expand-btn hidden">
    <span class="expand-text">Hiya Debug Console</span>
    <span class="expand-icon">▶</span>
</button>

<div class="hiya-debug-bar" id="hiya-debug-bar">
    <!-- Header -->
    <div class="hiya-debug-header" onclick="toggleHiyaDebug()">
        <div class="hiya-debug-title">
            <strong>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"/>
                    <path d="M2 17L12 22L22 17"/>
                    <path d="M2 12L12 17L22 12"/>
                </svg>
                Hiya Debug Console
            </strong>
            <div class="hiya-debug-stats">
                <span>📋 <span id="log-count"><?php echo count($logs); ?></span> logs</span>
                <span>⚡ <span id="exec-time"><?php echo $appInfo['execution_time']; ?></span>s</span>
                <span>💾 <span id="mem-usage"><?php echo $appInfo['memory_usage']; ?></span></span>
                <span style="background: #3b82f6; color: white; border-radius: 20px; padding: 4px 12px;">🚀 Hiya v<?php echo $appInfo['hiya_version']; ?></span>
                <span>🎨 <?php echo $appInfo['environment']; ?></span>
            </div>
            <div class="hiya-pos-group" onclick="event.stopPropagation()">
                <button class="hiya-pos-btn" data-pos="bottom" title="Bottom Position">⬇️</button>
                <button class="hiya-pos-btn" data-pos="top" title="Top Position">⬆️</button>
            </div>
        </div>
        <div class="hiya-debug-actions" onclick="event.stopPropagation()">
            <button class="hiya-debug-btn" id="collapse-side-btn" title="Collapse to left side (Ctrl+Shift+S)">◀ Collapse</button>
            <button class="hiya-debug-btn" onclick="showExportModal()">📤 Export</button>
            <button class="hiya-debug-btn" id="btn-maximize" onclick="toggleMaximize()" title="Maximize">🗖 Maximize</button>
            <button class="hiya-debug-btn primary" id="hiya-debug-toggle" onclick="toggleHiyaDebug()">− Minimize</button>
            <button class="hiya-debug-btn" onclick="clearHiyaDebug()">🗑 Clear</button>
        </div>
    </div>
    
    <!-- Info Panel -->
    <div class="hiya-info-panel">
        <div class="hiya-info-item">
            <span class="hiya-info-label">📄 Request:</span>
            <span class="hiya-info-value"><?php echo $appInfo['method']; ?> <?php echo htmlspecialchars($appInfo['url']); ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🌐 IP:</span>
            <span class="hiya-info-value"><?php echo $appInfo['ip']; ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🐘 PHP:</span>
            <span class="hiya-info-value"><?php echo $appInfo['php_version']; ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🚀 Hiya:</span>
            <span class="hiya-info-value">v<?php echo $appInfo['hiya_version']; ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🖥️ OS:</span>
            <span class="hiya-info-value"><?php echo isset($appInfo['operating_system']) ? htmlspecialchars($appInfo['operating_system']) : php_uname('s') . ' ' . php_uname('r'); ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🌐 Server:</span>
            <span class="hiya-info-value"><?php echo isset($appInfo['server_software']) ? htmlspecialchars($appInfo['server_software']) : ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">💾 Peak:</span>
            <span class="hiya-info-value"><?php echo $appInfo['memory_peak']; ?></span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label" id="live-time-label">⏱️ Live:</span>
            <span class="hiya-info-value" id="live-time">0s</span>
        </div>
        <div class="hiya-info-item">
            <span class="hiya-info-label">🔗</span>
            <span class="hiya-info-value">
                <a href="https://www.taktikspace.com/hiya" 
                target="_blank" 
                rel="noopener noreferrer"
                style="color: inherit; text-decoration: none;"
                title="Hiya Framework Website">
                    www.taktikspace.com/hiya
                </a>
            </span>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <?php if ($config['enableSearch']): ?>
    <div class="hiya-filter-bar">
        <input type="text" id="hiya-log-search" class="hiya-filter-search" placeholder="🔍 Search by keyword, category, or message...">
        <div class="hiya-filter-group" id="hiya-level-filters">
            <label class="hiya-filter-checkbox"><input type="checkbox" value="error" checked> 🔴 Error</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" value="warning" checked> 🟡 Warning</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" value="info" checked> 🔵 Info</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" value="trace" checked> ⚪ Trace</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" value="profile" checked> 🟢 Profile</label>
        </div>
        <button class="hiya-debug-btn" onclick="resetFilters()">Reset Filters</button>
        <button class="hiya-debug-btn" onclick="selectAllLevels()">Select All</button>
        <button class="hiya-debug-btn" onclick="deselectAllLevels()">Deselect All</button>
    </div>
    <?php endif; ?>
    
    <!-- Log Table -->
    <div id="hiya-debug-content" class="hiya-log-content">
        <table class="hiya-log-table">
            <thead>
                <tr>
                    <th style="width: 80px">Level</th>
                    <th style="width: 80px">Time</th>
                    <th style="width: 70px">Rel ms</th>
                    <th style="width: 140px">Category</th>
                    <th>Message</th>
                    <?php if ($config['showMemory']): ?>
                    <th style="width: 80px">Memory</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="hiya-log-tbody">
                <?php foreach ($logs as $log): ?>
                <tr data-level="<?php echo $log['level']; ?>" 
                    data-category="<?php echo htmlspecialchars($log['category']); ?>"
                    data-search="<?php echo htmlspecialchars(strip_tags($log['message_raw'])); ?>"
                    data-time="<?php echo strtotime($log['time_formatted']); ?>">
                    <td><span class="hiya-badge hiya-badge-<?php echo $log['level']; ?>"><?php echo strtoupper($log['level']); ?></span></td>
                    <td class="hiya-level-<?php echo $log['level']; ?>"><?php echo date('H:i:s', strtotime($log['time_formatted'])); ?></td>
                    <td class="hiya-level-<?php echo $log['level']; ?>"><?php echo $log['relative_time']; ?></td>
                    <td class="hiya-level-<?php echo $log['level']; ?>"><?php echo htmlspecialchars($log['category']); ?></td>
                    <td><?php echo $log['message']; ?></td>
                    <?php if ($config['showMemory']): ?>
                    <td><?php echo $log['memory_formatted']; ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="hiya-modal">
    <div class="hiya-modal-content">
        <div class="hiya-modal-title">📤 Export Logs</div>
        <div class="hiya-filter-group" style="margin-bottom: 16px;">
            <label class="hiya-filter-checkbox"><input type="checkbox" id="export-all" checked> All visible logs</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" id="export-json"> JSON format</label>
            <label class="hiya-filter-checkbox"><input type="checkbox" id="export-csv"> CSV format</label>
        </div>
        <div class="hiya-modal-buttons">
            <button class="hiya-debug-btn" onclick="closeExportModal()">Cancel</button>
            <button class="hiya-debug-btn primary" onclick="exportLogs()">Export</button>
        </div>
    </div>
</div>

<script>
    // State variables (UI only, no sensitive data)
    let isMinimized = false;
    let isMaximized = false;
    let isCollapsedSide = false;
    let currentPosition = 'bottom';
    let startTime = Date.now();
    
    const debugBar = document.getElementById('hiya-debug-bar');
    const expandBtn = document.getElementById('hiya-expand-btn');
    const collapseSideBtn = document.getElementById('collapse-side-btn');
    const btnMaximize = document.getElementById('btn-maximize');
    const toggleBtn = document.getElementById('hiya-debug-toggle');
    const posButtons = document.querySelectorAll('.hiya-pos-btn');
    
    // ============ PERSISTENT STATE MANAGEMENT (UI only, no sensitive data) ============
    
    function loadSavedStates() {
        try {
            const savedCollapsedSide = localStorage.getItem('hiya_debug_collapsed_side');
            if (savedCollapsedSide !== null) {
                isCollapsedSide = savedCollapsedSide === 'true';
            } else {
                isCollapsedSide = false;
            }
            
            const savedMinimized = localStorage.getItem('hiya_debug_minimized');
            if (savedMinimized !== null) {
                isMinimized = savedMinimized === 'true';
            } else {
                isMinimized = <?php echo $config['collapsedByDefault'] ? 'true' : 'false'; ?>;
            }
            
            const savedMaximized = localStorage.getItem('hiya_debug_maximized');
            if (savedMaximized !== null) {
                isMaximized = savedMaximized === 'true';
            } else {
                isMaximized = false;
            }
            
            const savedPosition = localStorage.getItem('hiya_debug_position');
            if (savedPosition && (savedPosition === 'bottom' || savedPosition === 'top')) {
                currentPosition = savedPosition;
            } else {
                currentPosition = 'bottom';
            }
        } catch (e) {
            // localStorage access denied or error - use defaults
            console.warn('localStorage access denied or error:', e);
            isCollapsedSide = false;
            isMinimized = <?php echo $config['collapsedByDefault'] ? 'true' : 'false'; ?>;
            isMaximized = false;
            currentPosition = 'bottom';
        }
    }
    
    function saveToLocalStorage(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            // localStorage full or access denied - silently ignore
            console.debug('Unable to save to localStorage:', e);
        }
    }
    
    function applySavedStates() {
        if (isCollapsedSide) {
            applyCollapseSide();
        } else {
            if (expandBtn) expandBtn.classList.add('hidden');
            
            if (currentPosition === 'top') {
                debugBar.classList.add('position-top');
                posButtons.forEach(btn => {
                    if (btn.dataset.pos === 'top') {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            } else {
                debugBar.classList.remove('position-top');
                posButtons.forEach(btn => {
                    if (btn.dataset.pos === 'bottom') {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
            
            if (isMaximized && !isMinimized && !isCollapsedSide) {
                debugBar.classList.add('maximized');
                if (btnMaximize) {
                    btnMaximize.innerHTML = '✕ Exit';
                    btnMaximize.classList.add('primary');
                }
            } else {
                debugBar.classList.remove('maximized');
                if (btnMaximize) {
                    btnMaximize.innerHTML = '🗖 Maximize';
                    btnMaximize.classList.remove('primary');
                }
            }
            
            if (isMinimized && !isCollapsedSide) {
                applyMinimize();
            } else if (!isCollapsedSide) {
                applyExpand();
            }
        }
    }
    
    function applyCollapseSide() {
        debugBar.classList.add('collapsed-side');
        debugBar.classList.remove('minimized', 'maximized', 'position-top');
        
        if (expandBtn) expandBtn.classList.remove('hidden');
        
        saveToLocalStorage('hiya_debug_collapsed_side', 'true');
        saveToLocalStorage('hiya_debug_minimized', 'false');
        saveToLocalStorage('hiya_debug_maximized', 'false');
    }
    
    function expandFromSide() {
        isCollapsedSide = false;
        debugBar.classList.remove('collapsed-side');
        
        if (expandBtn) expandBtn.classList.add('hidden');
        
        const savedPosition = localStorage.getItem('hiya_debug_position') || 'bottom';
        currentPosition = savedPosition;
        
        if (currentPosition === 'top') {
            debugBar.classList.add('position-top');
        } else {
            debugBar.classList.remove('position-top');
        }
        
        saveToLocalStorage('hiya_debug_collapsed_side', 'false');
        showToast('📂 Console expanded');
    }
    
    function toggleCollapseSide() {
        if (isCollapsedSide) {
            expandFromSide();
        } else {
            isCollapsedSide = true;
            applyCollapseSide();
            showToast('📁 Console collapsed to left side');
        }
    }
    
    function applyMinimize() {
        const content = document.getElementById('hiya-debug-content');
        const infoPanel = document.querySelector('.hiya-info-panel');
        const filterBar = document.querySelector('.hiya-filter-bar');
        
        debugBar.classList.add('minimized');
        debugBar.classList.remove('maximized');
        if (content) content.style.display = 'none';
        if (infoPanel) infoPanel.style.display = 'none';
        if (filterBar) filterBar.style.display = 'none';
        if (toggleBtn) toggleBtn.innerHTML = '+ Expand';
        
        if (btnMaximize) {
            btnMaximize.innerHTML = '🗖 Maximize';
            btnMaximize.classList.remove('primary');
        }
        
        saveToLocalStorage('hiya_debug_minimized', 'true');
        saveToLocalStorage('hiya_debug_maximized', 'false');
    }
    
    function applyExpand() {
        const content = document.getElementById('hiya-debug-content');
        const infoPanel = document.querySelector('.hiya-info-panel');
        const filterBar = document.querySelector('.hiya-filter-bar');
        
        debugBar.classList.remove('minimized', 'collapsed-side');
        if (content) content.style.display = 'block';
        if (infoPanel) infoPanel.style.display = 'flex';
        if (filterBar) filterBar.style.display = 'flex';
        if (toggleBtn) toggleBtn.innerHTML = '− Minimize';
        
        saveToLocalStorage('hiya_debug_minimized', 'false');
    }
    
    function toggleHiyaDebug() {
        if (isCollapsedSide) return;
        
        isMinimized = !isMinimized;
        
        if (isMinimized) {
            applyMinimize();
            if (isMaximized) {
                isMaximized = false;
                if (btnMaximize) {
                    btnMaximize.innerHTML = '🗖 Maximize';
                    btnMaximize.classList.remove('primary');
                }
                saveToLocalStorage('hiya_debug_maximized', 'false');
            }
            showToast('📁 Console minimized');
        } else {
            applyExpand();
            showToast('📂 Console expanded');
        }
    }
    
    function toggleMaximize() {
        if (isCollapsedSide) {
            showToast('Please expand first before maximizing');
            return;
        }
        
        if (isMinimized) {
            showToast('Please expand first before maximizing');
            return;
        }
        
        if (isMaximized) {
            debugBar.classList.remove('maximized');
            isMaximized = false;
            btnMaximize.innerHTML = '🗖 Maximize';
            btnMaximize.classList.remove('primary');
            saveToLocalStorage('hiya_debug_maximized', 'false');
            showToast('⬚ Restored normal size');
        } else {
            debugBar.classList.add('maximized');
            isMaximized = true;
            btnMaximize.innerHTML = '✕ Exit';
            btnMaximize.classList.add('primary');
            saveToLocalStorage('hiya_debug_maximized', 'true');
            showToast('🗖 Maximized view - Press Esc to exit');
        }
    }
    
    function setPosition(position) {
        if (isCollapsedSide) return;
        
        currentPosition = position;
        
        if (position === 'top') {
            debugBar.classList.add('position-top');
        } else {
            debugBar.classList.remove('position-top');
        }
        
        posButtons.forEach(btn => {
            if (btn.dataset.pos === position) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        saveToLocalStorage('hiya_debug_position', position);
        showToast(`📍 Position changed to ${position === 'bottom' ? 'Bottom' : 'Top'}`);
    }
    
    function clearHiyaDebug() {
        var tbody = document.getElementById('hiya-log-tbody');
        if (tbody) {
            tbody.innerHTML = '';
            document.getElementById('log-count').textContent = '0';
            showToast('🗑 Logs cleared');
        }
    }
    
    function resetFilters() {
        var searchInput = document.getElementById('hiya-log-search');
        var levelCheckboxes = document.querySelectorAll('#hiya-level-filters input');
        
        if (searchInput) searchInput.value = '';
        levelCheckboxes.forEach(function(cb) {
            cb.checked = true;
        });
        
        filterLogs();
        showToast('🔄 Filters reset');
    }
    
    function selectAllLevels() {
        document.querySelectorAll('#hiya-level-filters input').forEach(cb => cb.checked = true);
        filterLogs();
    }
    
    function deselectAllLevels() {
        document.querySelectorAll('#hiya-level-filters input').forEach(cb => cb.checked = false);
        filterLogs();
    }
    
    function showExportModal() {
        document.getElementById('export-modal').classList.add('show');
    }
    
    function closeExportModal() {
        document.getElementById('export-modal').classList.remove('show');
    }
    
    function exportLogs() {
        var exportAll = document.getElementById('export-all').checked;
        var exportJson = document.getElementById('export-json').checked;
        var exportCsv = document.getElementById('export-csv').checked;
        
        var rows = document.querySelectorAll('#hiya-log-tbody tr');
        var logs = [];
        
        rows.forEach(function(row) {
            if (!exportAll && row.style.display === 'none') return;
            
            var level = row.querySelector('td:first-child span').innerText;
            var time = row.querySelector('td:nth-child(2)').innerText;
            var category = row.querySelector('td:nth-child(4)').innerText;
            var message = row.querySelector('td:nth-child(5)').innerText;
            
            logs.push({ level: level, time: time, category: category, message: message });
        });
        
        if (exportJson) {
            var dataStr = JSON.stringify(logs, null, 2);
            var blob = new Blob([dataStr], {type: 'application/json'});
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'hiya-logs-' + new Date().toISOString() + '.json';
            a.click();
            URL.revokeObjectURL(url);
        } else if (exportCsv) {
            var csvRows = [['Level', 'Time', 'Category', 'Message']];
            logs.forEach(log => {
                csvRows.push([log.level, log.time, log.category, log.message]);
            });
            var csvContent = csvRows.map(row => row.join(',')).join('\n');
            var blob = new Blob([csvContent], {type: 'text/csv'});
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'hiya-logs-' + new Date().toISOString() + '.csv';
            a.click();
            URL.revokeObjectURL(url);
        } else {
            var textLogs = logs.map(l => `[${l.time}] [${l.level}] [${l.category}] ${l.message}`).join('\n');
            navigator.clipboard.writeText(textLogs).then(() => showToast(`📋 Copied ${logs.length} logs`));
        }
        
        closeExportModal();
        showToast(`📤 Exported ${logs.length} logs`);
    }
    
    function showToast(message) {
        var toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #1e293b;
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            z-index: 100000;
            animation: fadeInOut 2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        document.body.appendChild(toast);
        setTimeout(function() { if (toast && toast.remove) toast.remove(); }, 2000);
    }
    
    // Filter functionality
    var searchInput = document.getElementById('hiya-log-search');
    var levelCheckboxes = document.querySelectorAll('#hiya-level-filters input');
    
    function filterLogs() {
        var searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        var activeLevels = [];
        levelCheckboxes.forEach(function(cb) {
            if (cb.checked) activeLevels.push(cb.value);
        });
        
        var rows = document.querySelectorAll('#hiya-log-tbody tr');
        var visibleCount = 0;
        
        rows.forEach(function(row) {
            var level = row.getAttribute('data-level');
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            var levelMatch = activeLevels.includes(level);
            var searchMatch = searchTerm === '' || searchText.includes(searchTerm);
            var isVisible = levelMatch && searchMatch;
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        document.getElementById('log-count').textContent = visibleCount;
    }
    
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterLogs, 300);
        });
    }
    
    levelCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', filterLogs);
    });
    
    // Live time counter
    setInterval(function() {
        let elapsed = Math.floor((Date.now() - startTime) / 1000);
        const liveTimeEl = document.getElementById('live-time');
        if (liveTimeEl) liveTimeEl.textContent = elapsed + 's';
    }, 1000);
    
    // Update execution time dynamically
    var execTime = <?php echo $appInfo['execution_time']; ?>;
    var execTimeEl = document.getElementById('exec-time');
    if (execTimeEl) {
        var startExec = Date.now() - (execTime * 1000);
        setInterval(function() {
            var elapsed = (Date.now() - startExec) / 1000;
            execTimeEl.textContent = elapsed.toFixed(3);
        }, 100);
    }
    
    // ============ EVENT LISTENERS ============
    
    if (collapseSideBtn) {
        collapseSideBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleCollapseSide();
        });
    }
    
    if (expandBtn) {
        expandBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            expandFromSide();
        });
    }
    
    posButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            setPosition(this.dataset.pos);
        });
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMaximized) {
            toggleMaximize();
        }
        if (e.key === 'Escape' && isCollapsedSide) {
            expandFromSide();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'L') {
            e.preventDefault();
            toggleHiyaDebug();
        }
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            toggleCollapseSide();
        }
        if (e.ctrlKey && e.key === 'M') {
            e.preventDefault();
            toggleMaximize();
        }
        if (e.ctrlKey && e.key === 'B') {
            e.preventDefault();
            setPosition('bottom');
        }
        if (e.ctrlKey && e.key === 'T') {
            e.preventDefault();
            setPosition('top');
        }
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            clearHiyaDebug();
        }
        if (e.ctrlKey && e.key === 'E') {
            e.preventDefault();
            showExportModal();
        }
    });
    
    // ============ INITIALIZATION ============
    
    loadSavedStates();
    applySavedStates();
    
</script>