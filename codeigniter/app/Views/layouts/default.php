<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= esc($pageTitle ?? 'AFRIKENKID - Crypto & AI Services') ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Real-time cryptocurrency data and cutting-edge AI insights.') ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? current_url()) ?>">

    <!-- Robots Meta Tag -->
    <meta name="robots" content="<?= esc($robotsMeta ?? 'index, follow') ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta property="og:title" content="<?= esc($pageTitle ?? 'AFRIKENKID') ?>">
    <meta property="og:description" content="<?= esc($metaDescription ?? 'Real-time cryptocurrency data and cutting-edge AI insights.') ?>">
    <meta property="og:image" content="<?= esc($ogImage ?? base_url('assets/images/default_og_image.jpg')) ?>">
    <meta property="og:site_name" content="<?= esc($siteName ?? 'AFRIKENKID') ?>">
    <meta property="og:locale" content="<?= esc($locale ?? 'en_US') ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta name="twitter:title" content="<?= esc($pageTitle ?? 'AFRIKENKID') ?>">
    <meta name="twitter:description" content="<?= esc($metaDescription ?? 'Real-time cryptocurrency data and cutting-edge AI insights.') ?>">
    <meta name="twitter:image" content="<?= esc($twitterImage ?? base_url('assets/images/default_twitter_image.jpg')) ?>">

    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= esc($siteName ?? 'AFRIKENKID') ?>",
      "url": "<?= esc($canonicalUrl ?? current_url()) ?>",
      "description": "<?= esc($metaDescription ?? 'Real-time cryptocurrency data and cutting-edge AI insights.') ?>"
    }
    </script>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --text-body: #495057;
            --header-bg: #ffffff;
            --footer-bg: #212529;
            --footer-text: #adb5bd;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-body);
        }

        .navbar {
            transition: box-shadow 0.3s ease-in-out;
        }
        
        .navbar.scrolled {
            box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }

        .navbar .nav-link {
            font-weight: 500;
            color: var(--dark-gray);
            transition: color 0.3s;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: var(--primary-color);
        }

        .navbar .dropdown-menu {
            border-radius: 0.5rem;
            border-color: #e9ecef;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }

        .navbar .dropdown-item:hover {
            background-color: var(--primary-color);
            color: white;
        }

        main {
            flex-grow: 1;
        }
        
        .flash-message-container .alert {
            border-radius: 0.5rem;
            border-left-width: 5px;
        }

        .footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding-top: 3rem;
            padding-bottom: 1rem;
        }

        .footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: white;
        }
        
        .footer .list-unstyled li {
            margin-bottom: 0.5rem;
        }
        
        .footer .social-icons a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #495057;
            color: white;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        
        .footer .social-icons a:hover {
            background-color: var(--primary-color);
        }

        .footer .footer-bottom {
            border-top: 1px solid #495057;
            padding-top: 1rem;
            margin-top: 2rem;
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <header>
        <nav id="mainNavbar" class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
            <div class="container">
                <a class="navbar-brand fs-4" href="<?= url_to('welcome') ?>"><i class="bi bi-box"></i> AFRIKENKID</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto text-center">
                        <?php if (session()->get('isLoggedIn')): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= url_to('home') ?>">Dashboard</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Services
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
                                    <li><a class="dropdown-item" href="<?= url_to('crypto.index') ?>">Crypto Service</a></li>
                                    <li><a class="dropdown-item" href="<?= url_to('gemini.index') ?>">Gemini AI</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?= url_to('payment.index') ?>">Top Up</a></li>
                             <?php if (session()->get('is_admin')): ?>
                                <li class="nav-item"><a class="nav-link" href="<?= url_to('admin.index') ?>">Admin Panel</a></li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle"></i> <?= esc(session()->get('username')) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?= url_to('account.index') ?>">My Account</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= url_to('logout') ?>">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="<?= url_to('login') ?>">Login</a></li>
                            <li class="nav-item"><a class="btn btn-primary text-white ms-lg-2 px-3" href="<?= url_to('register') ?>">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container my-4 flash-message-container">
            <?= $this->include('partials/flash_messages') ?>
        </div>
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase">AFRIKENKID</h5>
                    <p>Providing innovative solutions for real-time data access and AI-powered insights to help you succeed in the digital world.</p>
                    <div class="social-icons">
                        <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                        <a href="https://www.linkedin.com/in/nehemia-obati-b74886344" class="linkedin"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="github"><i class="bi bi-github"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase">Services</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url_to('crypto.index') ?>">Crypto Data</a></li>
                        <li><a href="<?= url_to('gemini.index') ?>">Gemini AI</a></li>
                        <li><a href="<?= url_to('payment.index') ?>">Pricing</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase">Support</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url_to('contact.form') ?>">Contact Us</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                     <h5 class="text-uppercase">Legal</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url_to('terms') ?>">Terms of Service</a></li>
                        <li><a href="<?= url_to('privacy') ?>">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center footer-bottom">
                &copy; <?= date('Y') ?> AFRIKENKID. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add shadow to navbar on scroll
            const navbar = document.getElementById('mainNavbar');
            if (navbar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });
            }
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
