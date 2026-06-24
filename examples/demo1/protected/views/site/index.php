<?php
$this->pageTitle = 'Home';
?>

<div class="text-center py-5">
    <!-- Icon -->
    <div class="mb-4">
        <div class="d-inline-block p-4 bg-primary bg-opacity-10 rounded-circle">
            <i class="bi bi-heart-fill text-primary" style="font-size: 64px;"></i>
        </div>
    </div>
    
    <!-- Title -->
    <h1 class="display-4 fw-bold">
        <?php echo CHtml::encode(Hiya::app()->name); ?>
    </h1>
    <p class="lead text-muted">A lightweight PHP framework</p>
    
    <!-- Thank You Message -->
    <div class="mt-3">
        <div class="alert alert-success alert-dismissible fade show d-inline-block" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <strong>Thank you</strong> for visiting <?php echo CHtml::encode(Hiya::app()->name); ?>
        </div>
    </div>
    
    <!-- Development Status -->
    <div class="mt-3">
        <span class="badge bg-warning text-dark p-2">
            <i class="bi bi-tools"></i> Still in active development
        </span>
        <span class="badge bg-info text-white p-2 ms-2">
            <i class="bi bi-tag"></i> v<?php echo Hiya::getVersion(); ?>
        </span>
    </div>
    
    <!-- Description -->
    <div class="mt-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
        <p class="text-muted">
            <?php echo CHtml::encode(Hiya::app()->name); ?> is currently under active development. 
            We're working hard to bring you a stable and feature-rich framework.
        </p>
        <p class="text-muted small">
            <i class="bi bi-info-circle"></i> Not recommended for production use yet.
        </p>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-4">
        <a href="<?php echo Hiya::app()->createUrl('site/about'); ?>" class="btn btn-primary">
            <i class="bi bi-info-circle"></i> Learn More
        </a>
        <a href="https://github.com/Hiya-Framework/hiya" target="_blank" class="btn btn-outline-dark ms-2" rel="noopener noreferrer">
            <i class="bi bi-github"></i> GitHub
        </a>
    </div>
</div>
