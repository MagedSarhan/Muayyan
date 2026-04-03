<?php
/**
 * Muayyan - Common HTML Header
 * Include at the top of every page
 */
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Muayyan - Academic Assessment Load & Performance Analysis System">
    <title><?= e($pageTitle) ?> | Muayyan</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
    <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
