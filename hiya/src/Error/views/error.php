<?php
/**
 * Public error view for Hiya Framework - Bright & Friendly Design
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error <?php echo $code; ?> - <?php echo htmlspecialchars($type); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(145deg, #fefce8 0%, #fef3c7 25%, #fed7aa 50%, #fde68a 75%, #fef9c3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Decorative floating elements */
        body::before {
            content: '✦';
            position: absolute;
            top: 10%;
            left: 5%;
            font-size: 40px;
            color: rgba(251, 191, 36, 0.3);
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }
        
        body::after {
            content: '✧';
            position: absolute;
            bottom: 15%;
            right: 8%;
            font-size: 55px;
            color: rgba(249, 115, 22, 0.2);
            animation: float 6s ease-in-out infinite reverse;
            pointer-events: none;
        }
        
        .error-container {
            max-width: 550px;
            width: 100%;
            animation: floatIn 0.6s cubic-bezier(0.34, 1.2, 0.64, 1);
            position: relative;
            z-index: 1;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 48px;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(255, 245, 215, 0.8);
            transition: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
        }
        
        .error-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.18);
        }
        
        .error-icon {
            padding: 50px 40px 20px 40px;
            font-size: 88px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-bottom: 2px solid #fde68a;
        }
        
        .error-icon span {
            display: inline-block;
            animation: gentleBounce 0.6s ease;
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.3));
        }
        
        .error-body {
            padding: 40px;
            background: white;
        }
        
        .error-code {
            font-size: 100px;
            font-weight: 800;
            background: linear-gradient(135deg, #ea580c 0%, #f59e0b 40%, #fbbf24 70%, #fcd34d 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -4px;
        }
        
        .error-title {
            font-size: 26px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        
        .error-message {
            color: #b45309;
            margin-bottom: 32px;
            line-height: 1.7;
            font-size: 15px;
            font-weight: 400;
            padding: 0 16px;
            background: #fffbeb;
            padding: 16px 20px;
            border-radius: 24px;
            border-left: 4px solid #f59e0b;
        }
        
        .divider {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #ea580c);
            border-radius: 4px;
            margin: 24px auto;
        }
        
        .btn-group {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            border-radius: 60px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.45);
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        }
        
        .btn-primary:active {
            transform: translateY(1px);
        }
        
        .btn-secondary {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        
        .btn-secondary:hover {
            background: #fde68a;
            transform: translateY(-3px);
            border-color: #fbbf24;
            box-shadow: 0 6px 12px rgba(251, 191, 36, 0.2);
        }
        
        .help-text {
            margin-top: 28px;
            font-size: 13px;
            color: #d97706;
        }
        
        .help-text a {
            color: #ea580c;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 2px solid #fde68a;
            padding-bottom: 2px;
        }
        
        .help-text a:hover {
            color: #c2410c;
            border-bottom-color: #f59e0b;
        }
        
        @keyframes floatIn {
            from { opacity: 0; transform: scale(0.96) translateY(25px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        @keyframes gentleBounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-12px) scale(1.05); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        @media (max-width: 640px) {
            .error-container { max-width: 95%; }
            .error-icon { padding: 40px 30px 15px 30px; font-size: 70px; }
            .error-body { padding: 30px 24px; }
            .error-code { font-size: 70px; letter-spacing: -2px; }
            .error-title { font-size: 20px; }
            .error-message { font-size: 13px; margin-bottom: 24px; padding: 12px 16px; }
            .btn { padding: 10px 20px; font-size: 13px; }
            .divider { width: 60px; height: 3px; }
        }
        
        @media (max-width: 480px) {
            .error-icon { padding: 30px 20px 10px 20px; font-size: 60px; }
            .error-body { padding: 24px 20px; }
            .error-code { font-size: 55px; }
            .error-title { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <span>
                    <?php 
                    if ($code == 404) echo '🔍';
                    elseif ($code == 403) echo '🔒';
                    elseif ($code == 500) echo '⚙️';
                    else echo '🌟';
                    ?>
                </span>
            </div>
            <div class="error-body">
                <div class="error-code"><?php echo $code; ?></div>
                <div class="error-title"><?php echo htmlspecialchars($type); ?></div>
                <div class="error-message">
                    <?php 
                    if ($code == 404) {
                        echo "✨ Oops! The page you're looking for has wandered off into the digital sunset. Let's get you back on track! ✨";
                    } elseif ($code == 403) {
                        echo "🔐 Whoops! This area is VIP only. You don't have the golden ticket to enter here. 🔐";
                    } elseif ($code == 500) {
                        echo "🛠️ Our servers are taking a coffee break! Don't worry, our tech team is on it. Try again in a moment! 🛠️";
                    } else {
                        echo "💫 " . htmlspecialchars($message) . " 💫";
                    }
                    ?>
                </div>
                <div class="divider"></div>
                <div class="btn-group">
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Go Back
                    </a>
                    <a href="<?php echo Yii::app()->createUrl('site/index'); ?>" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        Homepage
                    </a>
                </div>
                <div class="help-text">
                    💬 Need a helping hand? <a href="<?php echo Yii::app()->createUrl('site/contact'); ?>">Talk to our support team</a> 💬
                </div>
            </div>
        </div>
    </div>
</body>
</html>