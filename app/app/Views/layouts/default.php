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
    <meta name="keywords" content="Generative AI, Google Gemini, Crypto Data, Bitcoin Wallet, Litecoin Wallet, Blockchain Query, AI Tools, Kenya, M-Pesa, Lipa na Mpesa, Mobile Money Africa, CodeIgniter Development">

    <!-- Geo-targeting -->
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Nairobi">

    <!-- Canonical & Robots -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta name="robots" content="<?= esc($robotsTag ?? 'index, follow') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" type="image/x-icon">

    <!-- Social Media Meta -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($canonicalUrl ?? current_url()) ?>">
    <meta property="og:title" content="<?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?>">
    <meta property="og:description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with Afrikenkid. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta property="og:image" content="<?= base_url('public/assets/images/afrikenkid_og_image.jpg') ?>">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Structured Data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "AFRIKENKID",
            "applicationCategory": "DeveloperApplication",
            "operatingSystem": "Web",
            "description": "<?= esc($metaDescription ?? 'A platform offering generative AI insights and real-time cryptocurrency data queries, with payment options including Mobile Money and Credit Cards for the African market.') ?>",
            "url": "<?= esc($canonicalUrl ?? current_url()) ?>",
            "offers": { "@type": "Offer", "price": "0", "priceCurrency": "KES" }
        }
    </script>

    <!-- Stylesheets -->
    <link href="<?= base_url('public/assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/bootstrap-icons/font/bootstrap-icons.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS variables now directly reference Bootstrap's variables for better theme integration. */
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

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-body);
            visibility: hidden; /* Prevents FOUC */
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .blueprint-card {
            background-color: var(--card-bg);
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease-in-out;
        }

        html[data-bs-theme="dark"] .blueprint-card {
            box-shadow: none;
        }

        .blueprint-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.8rem 1.5rem rgba(0, 0, 0, 0.07) !important;
        }

        .navbar {
            background-color: var(--header-bg) !important;
            border-bottom: 1px solid var(--border-color);
            transition: box-shadow 0.3s ease-in-out, background-color 0.3s ease;
        }
        
        .navbar.scrolled {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
        }

        .navbar-brand {
            color: var(--primary-color) !important;
        }

        .navbar .nav-link {
            color: var(--text-body);
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .navbar .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        
        .footer {
            background-color: var(--card-bg);
            color: var(--text-body);
        }
        .footer h5 { color: var(--text-heading); }
        .footer a:hover { color: var(--primary-color); }
    </style>
    <?= $this->renderSection('styles') ?>

    <!-- Meta Pixel Code (Unchanged) -->
</head>

<body class="d-flex flex-column min-vh-100">
    <?php
        // Navigation logic is defined directly in the layout file.
        $navLinks = [
            'loggedIn' => [
                ['id' => 'dashboard', 'url' => url_to('home'), 'title' => 'Dashboard'],
                ['id' => 'ai-studio', 'url' => url_to('gemini.index'), 'title' => 'AI Studio'],
                ['id' => 'crypto-query', 'url' => url_to('crypto.index'), 'title' => 'Crypto Query'],
                ['id' => 'top-up', 'url' => url_to('payment.index'), 'title' => 'Top Up'],
            ],
            'loggedOut' => [
                ['id' => 'home', 'url' => url_to('landing'), 'title' => 'Home'],
                ['id' => 'ai-studio', 'url' => url_to('gemini.public'), 'title' => 'AI Studio'],
                ['id' => 'crypto-query', 'url' => url_to('crypto.public'), 'title' => 'Crypto Query'],
            ]
        ];
        
        $linksToRender = function($pageIdentifier, $extraClasses = '') use ($navLinks) {
            $linksToShow = session()->get('isLoggedIn') ? $navLinks['loggedIn'] : $navLinks['loggedOut'];
            $currentIdentifier = $pageIdentifier ?? '';

            foreach ($linksToShow as $link) {
                $isActive = ($currentIdentifier === $link['id']) ? 'active' : '';
                echo '<li class="nav-item">';
                echo '<a class="nav-link ' . $extraClasses . ' ' . $isActive . '" href="' . $link['url'] . '">' . esc($link['title']) . '</a>';
                echo '</li>';
            }
        };
    ?>

    <nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top py-3">
        <div class="container">
            <!-- EDIT: Added .fw-bold utility class -->
            <a class="navbar-brand fs-4 fw-bold" href="<?= url_to('landing') ?>"><i class="bi bi-box"></i> AFRIKENKID</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    
                    <?php $linksToRender($pageIdentifier ?? '', 'fw-medium'); // EDIT: Passed .fw-medium to the links ?>

                    <?php if (session()->get('isLoggedIn')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5"></i>
                                <span><?= esc(session()->get('username')) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (session()->get('is_admin')): ?>
                                    <li><a class="dropdown-item" href="<?= url_to('admin.index') ?>">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= url_to('account.index') ?>">My Account</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url_to('logout') ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item d-flex align-items-center ms-lg-3">
                            <a class="btn btn-outline-primary" href="<?= url_to('login') ?>">Login</a>
                            <a class="btn btn-primary ms-2" href="<?= url_to('register') ?>">Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <!-- EDIT: Added .cursor-pointer utility class -->
                        <span class="nav-link theme-toggle cursor-pointer" id="theme-toggle-desktop" role="button" aria-label="Toggle theme"></span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="mobileNavLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                
                <?php $linksToRender($pageIdentifier ?? '', 'fw-medium'); // EDIT: Passed .fw-medium to the links ?>
                
                <li class="nav-item"><hr class="dropdown-divider"></li>

                <?php if (session()->get('isLoggedIn')): ?>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= url_to('account.index') ?>">My Account</a></li>
                     <?php if (session()->get('is_admin')): ?>
                        <li class="nav-item"><a class="nav-link fw-medium" href="<?= url_to('admin.index') ?>">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link fw-medium" href="<?= url_to('logout') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item auth-buttons-mobile pt-3 mt-3 border-top">
                        <div class="d-grid gap-2">
                            <a class="btn btn-outline-primary" href="<?= url_to('login') ?>">Login</a>
                            <a class="btn btn-primary" href="<?= url_to('register') ?>">Register</a>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="nav-item mt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-medium">Theme</span>
                    <!-- EDIT: Added .cursor-pointer utility class -->
                    <span class="theme-toggle cursor-pointer" id="theme-toggle-mobile" role="button" aria-label="Toggle theme"></span>
                </li>
            </ul>
        </div>
    </div>

    <main class="min-vh-100">
        <div class="container my-4">
            <?= $this->include('partials/flash_messages') ?>
        </div>
        <?= $this->renderSection('content') ?>
    </main>

    <!-- EDIT: Added .border-top utility class -->
    <footer class="footer mt-auto pt-5 pb-4 border-top">
        <div class="container">
            <!-- Footer content remains identical -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>AFRIKENKID</h5>
                    <p class="small">Build smarter with a pay-as-you-go platform. Leverage Generative AI, query real-time BTC & LTC wallet data, and securely top up with M-Pesa. Built for developers in Kenya and Africa.</p>
                </div>
                <div class="col-lg-2 col-6">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= url_to('gemini.public') ?>">Gemini AI</a></li>
                        <li><a href="<?= url_to('crypto.public') ?>">Crypto Data</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-6">
                     <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= url_to('contact.form') ?>">Contact Us</a></li>
                        <li><a href="<?= url_to('documentation') ?>">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-12 mt-4 mt-lg-0">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= url_to('terms') ?>">Terms of Service</a></li>
                        <li><a href="<?= url_to('privacy') ?>">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center border-top pt-4 mt-4">
                &copy; <?= date('Y') ?> AFRIKENKID. All rights reserved.
                <?php if (ENVIRONMENT !== 'production') : ?>
                    <div class="small text-muted mt-2">Page rendered in <strong>{elapsed_time}</strong>s</div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <?php if ($showCookieBanner ?? false): ?>
        <?= $this->include('partials/cookie_banner') ?>
    <?php endif; ?>

    <script src="<?= base_url('public/assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // JavaScript remains unchanged as it is already well-structured.
            const themeManager = {
                toggles: document.querySelectorAll('.theme-toggle'),
                icons: { dark: '<i class="bi bi-moon-stars-fill"></i>', light: '<i class="bi bi-brightness-high-fill"></i>' },
                init() {
                    const preferredTheme = this.getPreferredTheme();
                    this.setTheme(preferredTheme); this.updateToggleIcons(preferredTheme); this.bindEvents();
                    document.body.style.visibility = 'visible';
                },
                getStoredTheme: () => localStorage.getItem('theme'),
                setStoredTheme: theme => localStorage.setItem('theme', theme),
                getPreferredTheme() {
                    const storedTheme = this.getStoredTheme();
                    if (storedTheme) return storedTheme;
                    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                },
                setTheme(theme) {
                    const effectiveTheme = (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : theme;
                    document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
                },
                updateToggleIcons(theme) {
                    this.toggles.forEach(toggle => { toggle.innerHTML = theme === 'dark' ? this.icons.light : this.icons.dark; });
                },
                bindEvents() {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        if (!this.getStoredTheme()) {
                            const newTheme = this.getPreferredTheme(); this.setTheme(newTheme); this.updateToggleIcons(newTheme);
                        }
                    });
                    this.toggles.forEach(toggle => {
                        toggle.addEventListener('click', () => {
                            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                            this.setStoredTheme(newTheme); this.setTheme(newTheme); this.updateToggleIcons(newTheme);
                        });
                    });
                }
            };
            themeManager.init();
            const navbar = document.getElementById('mainNavbar');
            if (navbar) { window.addEventListener('scroll', function() { navbar.classList.toggle('scrolled', window.scrollY > 50); }); }
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
