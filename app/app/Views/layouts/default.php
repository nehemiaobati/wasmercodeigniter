<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title><?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with Afrikenkid. Query Bitcoin & Litecoin, and interact with advanced AI. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta name="keywords" content="Generative AI, Cryptocurrency, Bitcoin, Litecoin, Crypto Query, AI Insights, Kenya, Africa, Mobile Money, Safaricom, M-Pesa, Airtel Money, Paystack">
    
    <!-- Geo-targeting for Kenya -->
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Nairobi">
    <meta name="geo.position" content="-1.286389;36.817223">
    <meta name="ICBM" content="-1.286389, 36.817223">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? current_url()) ?>">

    <!-- Robots Meta Tag -->
    <meta name="robots" content="index, follow">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" type="image/x-icon">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta property="og:title" content="<?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?>">
    <meta property="og:description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with Afrikenkid. Query Bitcoin & Litecoin, and interact with advanced AI. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta property="og:image" content="<?= base_url('assets/images/afrikenkid_og_image.jpg') ?>">
    <meta property="og:site_name" content="AFRIKENKID">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta name="twitter:title" content="<?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?>">
    <meta name="twitter:description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with Afrikenkid. Query Bitcoin & Litecoin, and interact with advanced AI. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta name="twitter:image" content="<?= base_url('assets/images/afrikenkid_twitter_image.jpg') ?>">

    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "AFRIKENKID",
        "applicationCategory": "DeveloperApplication",
        "operatingSystem": "Web",
        "description": "<?= esc($metaDescription ?? 'A platform offering generative AI insights and real-time cryptocurrency data queries, with payment options including Mobile Money and Credit Cards for the African market.') ?>",
        "url": "<?= esc($canonicalUrl ?? current_url()) ?>",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "KES"
        }
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
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
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
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

        /* Centrally Managed Pagination Styling */
        .pagination {
            --bs-pagination-padding-x: 0.85rem;
            --bs-pagination-padding-y: 0.45rem;
            --bs-pagination-font-size: 0.95rem;
            --bs-pagination-border-width: 0;
            --bs-pagination-border-radius: 0.375rem;
            --bs-pagination-hover-color: var(--primary-color);
            --bs-pagination-hover-bg: #e9ecef;
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: var(--primary-color);
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: #fff;
        }
        .pagination .page-item {
            margin: 0 4px;
        }
        .pagination .page-link {
            border-radius: var(--bs-pagination-border-radius) !important;
            transition: all 0.2s ease-in-out;
        }
        .pagination .page-item.active .page-link {
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
            transform: translateY(-2px);
        }
        
        /* Mobile Navbar Improvements */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 1rem;
            }
            .navbar .nav-item {
                margin-bottom: 0.5rem;
            }
            .navbar .dropdown-menu {
                width: 100%;
                text-align: center;
                border: 1px solid #dee2e6;
                box-shadow: none;
                background-color: var(--light-gray);
                margin-top: 0.5rem !important;
            }
            .navbar .dropdown-menu-end {
                right: auto;
                left: 0;
            }
            .navbar .auth-buttons-mobile {
                border-top: 1px solid #dee2e6;
                padding-top: 1rem;
                margin-top: 0.5rem;
            }
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>

<body class="d-flex flex-column min-vh-100">
    
    <nav id="mainNavbar" class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fs-4" href="<?= url_to('welcome') ?>"><i class="bi bi-box"></i> AFRIKENKID</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (session()->get('isLoggedIn')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url_to('home') ?>">Home</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Services
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
                                <li><a class="dropdown-item" href="<?= url_to('gemini.index') ?>">Gemini AI</a></li>
                                <li><a class="dropdown-item" href="<?= url_to('crypto.index') ?>">Crypto Data</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= url_to('payment.index') ?>">Top Up</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?= esc(session()->get('username')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if (session()->get('is_admin')): ?>
                                    <li><a class="dropdown-item" href="<?= url_to('admin.index') ?>">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= url_to('account.index') ?>">My Account</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?= url_to('logout') ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Responsive Auth Buttons -->
                        <li class="nav-item d-lg-none auth-buttons-mobile">
                            <div class="d-flex justify-content-center gap-2">
                                <a class="btn btn-outline-primary w-100" href="<?= url_to('login') ?>">Login</a>
                                <a class="btn btn-primary w-100" href="<?= url_to('register') ?>">Register</a>
                            </div>
                        </li>
                        <li class="nav-item d-none d-lg-flex align-items-center">
                            <a class="btn btn-outline-primary" href="<?= url_to('login') ?>">Login</a>
                            <a class="btn btn-primary ms-2" href="<?= url_to('register') ?>">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

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
                        <li><a href="<?= url_to('gemini.index') ?>">Gemini AI</a></li>
                        <li><a href="<?= url_to('crypto.index') ?>">Crypto Data</a></li>
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