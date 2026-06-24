<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="language" content="en" />
    
    <title><?php echo Hiya::app()->name . ' - ' . CHtml::encode($this->pageTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            padding-top: 70px;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            color: #1e3a8a !important;
        }
        .navbar-brand i {
            color: #3b82f6;
        }
        .footer {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 20px 0;
            margin-top: 40px;
        }
        .footer a {
            color: #1e3a8a;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .container-main {
            min-height: calc(100vh - 200px);
        }
        .nav-link.active {
            font-weight: 600;
            color: #1e3a8a !important;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo Hiya::app()->createUrl('site/index'); ?>">
                <i class="bi bi-house-heart"></i>
                <?php echo CHtml::encode(Hiya::app()->name); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->getAction()->id == 'index' ? 'active' : ''; ?>" 
                           href="<?php echo Hiya::app()->createUrl('site/index'); ?>">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->getAction()->id == 'about' ? 'active' : ''; ?>" 
                           href="<?php echo Hiya::app()->createUrl('site/about'); ?>">
                            <i class="bi bi-info-circle"></i> About
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container container-main">
        <!-- Breadcrumbs -->
        <?php if (!empty($this->breadcrumbs)): ?>
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <?php foreach ($this->breadcrumbs as $label => $url): ?>
                        <?php if (is_string($label)): ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo Hiya::app()->createUrl($url); ?>">
                                    <?php echo CHtml::encode($label); ?>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active">
                                <?php echo CHtml::encode($url); ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php if (Hiya::app()->user->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i>
                <?php echo Hiya::app()->user->getFlash('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (Hiya::app()->user->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo Hiya::app()->user->getFlash('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <?php echo $content; ?>
    </div>

    <!-- Simple Footer -->
    <footer class="footer">
        <div class="container text-center">
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
                <span>
                    <i class="bi bi-heart-fill text-danger"></i>
                    <?php echo CHtml::encode(Hiya::app()->name); ?>
                    <span class="text-muted">v<?php echo Hiya::getVersion(); ?></span>
                </span>
                <span class="text-muted">|</span>
                <a href="https://www.taktikspace.com/hiya" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-globe"></i> Website
                </a>
                <span class="text-muted">|</span>
                <a href="https://github.com/Hiya-Framework/hiya" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-github"></i> GitHub
                </a>
                <span class="text-muted">|</span>
                <span class="text-muted">
                    &copy; <?php echo date('Y'); ?>
                </span>
            </div>
            <div class="small text-muted mt-1">
                Built with <i class="bi bi-heart-fill text-danger" style="font-size: 10px;"></i> for simplicity
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>