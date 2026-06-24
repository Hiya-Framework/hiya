<?php
$this->pageTitle = 'About';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title">
                    <i class="bi bi-info-circle text-primary"></i> About <?php echo CHtml::encode(Hiya::app()->name); ?>
                </h2>
                <hr>
                <p>
                    <strong><?php echo CHtml::encode(Hiya::app()->name); ?></strong> is a PHP framework built on 
                    Yii 1, designed to be:
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Fast</strong> - Optimized for performance
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Secure</strong> - Built-in security features
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Flexible</strong> - Easy to extend and customize
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Modern</strong> - Supports PHP 8.4+
                    </li>
                </ul>
                
                <h5 class="mt-4">Version</h5>
                <p><code>v<?php echo Hiya::getVersion(); ?></code></p>
                
                <h5 class="mt-4">Links</h5>
                <a href="https://github.com/Hiya-Framework/hiya" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-github"></i> GitHub Repository
                </a>
            </div>
        </div>
    </div>
</div>