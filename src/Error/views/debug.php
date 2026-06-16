<?php
/**
 * Debug error view for Hiya Framework - Clean & Modern Design
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug Error - <?php echo $code; ?> | Hiya Framework</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 50%, #f0fdf4 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .error-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .error-header {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .error-code-badge {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .error-type-badge {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        
        .error-location {
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            color: #475569;
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 10px;
            border-left: 3px solid #3b82f6;
        }
        
        .error-location strong {
            color: #3b82f6;
        }
        
        /* Hiya Logo */
        .Hiya-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            padding: 8px 20px 8px 16px;
            border-radius: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon svg { width: 20px; height: 20px; }
        .logo-text { font-size: 16px; font-weight: 700; color: #1e293b; }
        .logo-version { font-size: 10px; color: #94a3b8; margin-left: 4px; }
        
        /* Card */
        .error-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .error-body {
            padding: 28px 32px;
        }
        
        /* Tabs */
        .error-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0;
            flex-wrap: wrap;
        }
        
        .error-tab {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            border: none;
            background: none;
            transition: all 0.2s;
            border-radius: 8px 8px 0 0;
        }
        
        .error-tab:hover { color: #3b82f6; background: #f8fafc; }
        .error-tab.active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
            margin-bottom: -1px;
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.2s ease; }
        
        /* Error Message Box */
        .error-message-box {
            background: #fef2f2;
            padding: 16px 20px;
            border-radius: 12px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            border-left: 4px solid #ef4444;
            margin-bottom: 20px;
            line-height: 1.6;
            overflow-x: auto;
        }
        
        /* Stack Trace */
        .trace-item {
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 6px;
            font-family: 'Fira Code', monospace;
            font-size: 11px;
            overflow-x: auto;
            border-left: 2px solid #3b82f6;
            transition: all 0.15s;
        }
        
        .trace-item:hover {
            background: #f1f5f9;
        }
        
        .trace-file { color: #2563eb; font-weight: 500; }
        .trace-line { color: #d97706; }
        .trace-function { color: #059669; }
        
        /* Code Preview - Default Expanded */
        .code-preview {
            background: #1e293b;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            font-family: 'Fira Code', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        
        .code-line {
            color: #e2e8f0;
            line-height: 1.6;
            white-space: pre;
            font-family: 'Fira Code', monospace;
        }
        
        .code-line-highlight {
            background: #ef4444;
            color: white;
            display: inline-block;
            width: 100%;
            padding: 2px 0;
            border-radius: 4px;
        }
        
        .line-number {
            color: #6b7280;
            display: inline-block;
            width: 45px;
            text-align: right;
            margin-right: 15px;
            user-select: none;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 12px;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.15s;
        }
        
        .info-item:hover {
            background: #f1f5f9;
        }
        
        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .info-value {
            font-size: 12px;
            color: #1e293b;
            font-family: 'Fira Code', monospace;
            word-break: break-word;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: #ffffff;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
        }
        
        .btn-copy {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-copy:hover {
            background: #e2e8f0;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 16px 24px;
            color: #94a3b8;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            background: #fafcff;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-container {
            animation: fadeInUp 0.3s ease;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .error-body { padding: 20px; }
            .error-title { font-size: 20px; }
            .error-header { flex-direction: column; }
            .info-grid { grid-template-columns: 1fr; }
            .error-tabs { justify-content: center; }
        }
        
        @media print {
            .action-buttons, .footer, .Hiya-logo, .error-tabs { display: none; }
            body { background: white; padding: 0; }
            .error-card { box-shadow: none; border: 1px solid #ddd; }
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Header -->
        <div class="error-header">
            <div>
                <div class="error-badge">
                    <span class="error-code-badge"><?php echo $code; ?></span>
                    <span class="error-type-badge"><?php echo strtoupper($errorType); ?></span>
                </div>
                <div class="error-title"><?php echo htmlspecialchars($type ?? 'Error'); ?></div>
                <div class="error-location">
                    📍 <strong>in</strong> <?php echo htmlspecialchars($file ?? 'Unknown'); ?> 
                    <strong class="trace-line">line <?php echo $line ?? '?'; ?></strong>
                </div>
            </div>
            <div class="Hiya-logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="1.5" fill="white"/>
                    </svg>
                </div>
                <div>
                    <span class="logo-text">Hiya Framework</span>
                    <span class="logo-version">v<?php echo defined('HIYA_VERSION') ? HIYA_VERSION : '2.0'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Main Card -->
        <div class="error-card">
            <div class="error-body">
                <!-- Tabs Navigation -->
                <div class="error-tabs">
                    <button class="error-tab active" data-tab="message">📝 Error</button>
                    <button class="error-tab" data-tab="trace">🔍 Stack Trace</button>
                    <button class="error-tab" data-tab="environment">🌐 Environment</button>
                    <button class="error-tab" data-tab="system">⚙️ System</button>
                </div>
                
                <!-- Tab: Error Message with Code Context -->
                <div class="tab-content active" id="tab-message">
                    <div class="error-message-box">
                        <?php echo nl2br(htmlspecialchars($message)); ?>
                    </div>
                    
                    <!-- Code Context - Default Expanded -->
                    <?php if (isset($line) && isset($file) && file_exists($file)): ?>
                    <div class="code-preview">
                        <?php
                        $lines = file($file);
                        $start = max(0, $line - 6);
                        $end = min(count($lines) - 1, $line + 5);
                        for ($i = $start; $i <= $end; $i++):
                            $currentLine = $i + 1;
                            $isErrorLine = ($currentLine == $line);
                        ?>
                        <div class="code-line <?php echo $isErrorLine ? 'code-line-highlight' : ''; ?>">
                            <span class="line-number"><?php echo $currentLine; ?></span>
                            <?php echo htmlspecialchars(rtrim($lines[$i])); ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Stack Trace -->
                <div class="tab-content" id="tab-trace">
                    <?php if (isset($trace) && !empty($trace)): ?>
                        <?php foreach (array_slice($trace, 0, 15) as $traceItem): ?>
                        <div class="trace-item">
                            <span class="trace-file"><?php echo isset($traceItem['file']) ? basename($traceItem['file']) : '[internal]'; ?></span>
                            <span class="trace-line">:<?php echo $traceItem['line'] ?? '0'; ?></span>
                            <span class="trace-function"> → <?php echo isset($traceItem['function']) ? $traceItem['function'] . '()' : '{main}'; ?></span>
                            <?php if (isset($traceItem['class'])): ?>
                            <div style="color: #94a3b8; font-size: 10px; margin-top: 4px;">
                                <?php echo $traceItem['class'] . $traceItem['type'] . $traceItem['function']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($trace) > 15): ?>
                        <div class="trace-item" style="text-align: center; color: #94a3b8; border-left-color: #94a3b8;">
                            ... and <?php echo count($trace) - 15; ?> more frames
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="trace-item">No stack trace available</div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Environment -->
                <div class="tab-content" id="tab-environment">
                    <div class="info-grid">
                        <?php if (isset($requestInfo)): ?>
                            <?php foreach ($requestInfo as $label => $value): ?>
                            <div class="info-item">
                                <div class="info-label"><?php echo $label; ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (isset($serverInfo)): ?>
                            <?php foreach ($serverInfo as $label => $value): ?>
                            <div class="info-item">
                                <div class="info-label"><?php echo $label; ?></div>
                                <div class="info-value"><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab: System -->
                <div class="tab-content" id="tab-system">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">PHP Version</div>
                            <div class="info-value"><?php echo PHP_VERSION; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Memory Usage</div>
                            <div class="info-value"><?php echo $memoryUsage; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Peak Memory</div>
                            <div class="info-value"><?php echo $peakMemory ?? $memoryUsage; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Execution Time</div>
                            <div class="info-value"><?php echo $executionTime; ?>s</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Error Time</div>
                            <div class="info-value"><?php echo $timestamp; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Hiya Framework</div>
                            <div class="info-value">v<?php echo defined('HIYA_VERSION') ? HIYA_VERSION : '2.0'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Operating System</div>
                            <div class="info-value"><?php echo PHP_OS; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Server Software</div>
                            <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button onclick="copyErrorDetails()" class="btn btn-copy">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copy Error
                    </button>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Go Back
                    </a>
                    <a href="<?php echo Hiya::app()->createUrl('site/index'); ?>" class="btn btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        Home
                    </a>
                </div>
            </div>
            
            <div class="footer">
                <strong>Hiya Framework</strong> v<?php echo defined('HIYA_VERSION') ? HIYA_VERSION : '2.0'; ?> · 
                <strong>PHP</strong> <?php echo PHP_VERSION; ?> · 
                <strong>Debug Mode</strong>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.error-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.error-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
            });
        });
        
        // Copy error details
        function copyErrorDetails() {
            let details = '=== ERROR DETAILS ===\n';
            details += 'Code: <?php echo $code; ?>\n';
            details += 'Type: <?php echo htmlspecialchars($type ?? 'Error'); ?>\n';
            details += 'Message: <?php echo htmlspecialchars($message); ?>\n';
            details += 'File: <?php echo htmlspecialchars($file ?? 'Unknown'); ?>\n';
            details += 'Line: <?php echo $line ?? '?'; ?>\n\n';
            
            <?php if (isset($trace) && !empty($trace)): ?>
            details += '=== STACK TRACE ===\n';
            <?php foreach (array_slice($trace, 0, 12) as $traceItem): ?>
            details += '<?php echo isset($traceItem['file']) ? addslashes(basename($traceItem['file'])) : '[internal]'; ?>:<?php echo $traceItem['line'] ?? '0'; ?> → <?php echo isset($traceItem['function']) ? addslashes($traceItem['function']) . '()' : '{main}'; ?>\n';
            <?php endforeach; ?>
            details += '\n';
            <?php endif; ?>
            
            details += '=== SYSTEM INFO ===\n';
            details += 'PHP: <?php echo PHP_VERSION; ?>\n';
            details += 'Memory: <?php echo $memoryUsage; ?>\n';
            details += 'Time: <?php echo $executionTime; ?>s\n';
            details += 'Timestamp: <?php echo $timestamp; ?>\n';
            details += 'Hiya: <?php echo defined('HIYA_VERSION') ? HIYA_VERSION : '2.0'; ?>\n';
            
            navigator.clipboard.writeText(details).then(() => {
                showToast('✅ Error details copied!');
            }).catch(() => {
                alert('Failed to copy');
            });
        }
        
        // Toast notification
        function showToast(msg) {
            let toast = document.createElement('div');
            toast.textContent = msg;
            toast.style.cssText = `position:fixed;bottom:20px;right:20px;background:#059669;color:white;padding:10px 20px;border-radius:40px;font-size:13px;z-index:10000;animation:slideIn 0.3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.15);`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }
        
        // Animation style
        let style = document.createElement('style');
        style.textContent = `@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}`;
        document.head.appendChild(style);
        
        console.log('🔍 Hiya Debug Error Page Loaded');
    </script>
</body>
</html>