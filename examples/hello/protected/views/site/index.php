<?php
// views/site/index.php

$this->pageTitle = $title ?? 'Hiya';
?>

<h2><?php echo $title ?? 'Welcome'; ?></h2>
<p><?php echo $message ?? 'Hello from Hiya!'; ?></p>

<div style="background: #f0fdf4; padding: 16px; border-radius: 8px; border-left: 4px solid #22c55e;">
    <strong>Hiya is working!</strong>
    <ul>
        <li>Framework: Hiya <?php echo Hiya::getVersion(); ?></li>
        <li>Debug: <?php echo HIYA_DEBUG ? 'ON' : 'OFF'; ?></li>
        <li>Layout: Running successfully!</li>
    </ul>
</div>
