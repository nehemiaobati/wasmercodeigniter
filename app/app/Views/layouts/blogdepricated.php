<?= '
' ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title><?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data. Query Bitcoin & Litecoin, and interact with advanced AI. Pay easily with Mobile Money or Credit Card.') ?>">
    
    <!-- Canonical & Robots -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta name="robots" content="<?= esc($robotsTag ?? 'index, follow') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" type="image/x-icon">

    <!-- Social Media Meta -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta property="og:title" content="<?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?>">
    <meta property="og:description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with Afrikenkid. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta property="og:image" content="<?= base_url('assets/images/afrikenkid_og_image.jpg') ?>">
    <meta name="twitter:card" content="summary_large_image">

    <!-- JSON-LD Schema (Dynamically inserted for blog posts) -->
    <?= $json_ld_schema ?? '' ?>

    <!-- Stylesheets -->
    <link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: var(--bs-primary);
            --secondary-color: var(--bs-secondary);
            --light-bg: var(--bs-body-bg);
            --card-bg: var(--bs-card-bg);
            --text-body: var(--bs-body-color);
            --text-heading: var(--bs-heading-color);
            --border-color: var(--bs-border-color);
            --header-bg: var(--bs-body-bg);
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); color: var(--text-body); visibility: hidden; }
        .navbar { background-color: var(--header-bg) !important; border-bottom: 1px solid var(--border-color); }
        .footer { background-color: var(--card-bg); color: var(--text-body); }
    </style>
    <?= $this->renderSection('styles') ?>
</head>

<body class="d-flex flex-column min-vh-100">
    
    <!-- The main navigation is inherited from the default layout -->
    <?= $this->include('layouts/default') ?>

    <main class="flex-grow-1">
        <?= $this->renderSection('blog_content') ?>
    </main>

    <!-- The footer is also inherited -->
    <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        // Theme manager script from default layout is inherited
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>