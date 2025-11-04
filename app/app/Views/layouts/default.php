<?= '
' ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title><?= esc($pageTitle ?? 'AFRIKENKID | Generative AI & Crypto Data') ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Explore generative AI and real-time crypto data with . Query Bitcoin & Litecoin, and interact with advanced AI. Pay easily with Mobile Money or Credit Card.') ?>">
    <meta name="keywords" content="Generative AI, Google Gemini, Crypto Data, Bitcoin Wallet, Litecoin Wallet, Blockchain Query, AI Tools, Kenya, M-Pesa,Lipa na Mpesa, Mobile Money Africa, CodeIgniter Development">
    
    <!-- Geo-targeting for Kenya -->
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Nairobi">
    <meta name="geo.position" content="-1.286389;36.817223">
    <meta name="ICBM" content="-1.286389, 36.817223">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? current_url()) ?>">

    <!-- Robots Meta Tag -->
    <meta name="robots" content="<?= esc($robotsTag ?? 'index, follow') ?>">
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
    <link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-green: #198754;
            
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-body: #495057;
            --text-heading: #212529;
            --border-color: #dee2e6;
            --header-bg: #ffffff;
            --footer-bg: #212529; /* Kept for reference, but now overridden */
        }

        html[data-bs-theme="dark"] {
            --light-bg: #121212;
            --card-bg: #1e1e1e;
            --text-body: #adb5bd;
            --text-heading: #f8f9fa;
            --border-color: #495057;
            --header-bg: #1e1e1e;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-body);
            visibility: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .blueprint-card {
            background-color: var(--card-bg);
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease-in-out;
        }
        html[data-bs-theme="dark"] .blueprint-card {
            box-shadow: none;
        }
        .blueprint-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.8rem 1.5rem rgba(0,0,0,0.07) !important;
        }
        
        .navbar {
            background-color: var(--header-bg) !important;
            border-bottom: 1px solid var(--border-color);
            transition: box-shadow 0.3s ease-in-out, background-color 0.3s ease;
        }
        .navbar-brand { font-weight: 700; color: var(--primary-color) !important; }
        .navbar .nav-link {
            font-weight: 500;
            color: var(--text-body);
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            transition: color 0.2s ease, background-color 0.2s ease;
        }
        .navbar .nav-link:hover { color: var(--primary-color); }
        .navbar .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.1);
        }
        .dropdown-menu {
            border-radius: 0.5rem;
            border-color: var(--border-color);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .dropdown-item:hover { background-color: var(--primary-color); color: white; }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .theme-toggle {
            cursor: pointer;
            padding: 0.5rem;
        }

        @media (max-width: 991.98px) {
            .offcanvas {
                background-color: var(--card-bg);
            }
            .offcanvas-header {
                border-bottom: 1px solid var(--border-color);
            }
            .offcanvas .nav-link {
                font-size: 1.1rem;
                padding: 0.75rem 0;
            }
            .offcanvas .auth-buttons-mobile {
                border-top: 1px solid var(--border-color);
                padding-top: 1rem;
                margin-top: 1rem;
            }
        }

        main { flex-grow: 1; }
        .flash-message-container .alert { border-radius: 0.5rem; border-left-width: 5px; }

        /* MODIFICATION: Footer styles updated to use theme variables */
        .footer { 
            background-color: var(--card-bg); 
            color: var(--text-body); 
            padding-top: 3rem; 
            padding-bottom: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .footer h5 { 
            color: var(--text-heading); 
            font-weight: 600; 
            margin-bottom: 1rem; 
        }
        .footer a { 
            color: var(--text-body); 
            text-decoration: none; 
            transition: color 0.3s; 
        }
        .footer a:hover { 
            color: var(--primary-color); 
        }
        .footer .list-unstyled li { margin-bottom: 0.5rem; }
        .footer .social-icons a { display: inline-flex; justify-content: center; align-items: center; width: 40px; height: 40px; border-radius: 50%; background-color: var(--light-bg); color: var(--text-body); font-size: 1.2rem; margin-right: 0.5rem; }
        .footer .social-icons a:hover { background-color: var(--primary-color); color: white; }
        .footer .footer-bottom { 
            border-top: 1px solid var(--border-color); 
            padding-top: 1rem; 
            margin-top: 2rem; 
        }
        
        .pagination { --bs-pagination-padding-x: 0.85rem; --bs-pagination-padding-y: 0.45rem; --bs-pagination-font-size: 0.95rem; --bs-pagination-border-width: 0; --bs-pagination-border-radius: 0.375rem; --bs-pagination-hover-color: var(--primary-color); --bs-pagination-hover-bg: #e9ecef; --bs-pagination-active-color: #fff; --bs-pagination-active-bg: var(--primary-color); --bs-pagination-disabled-color: #6c757d; --bs-pagination-disabled-bg: #fff; }
        .pagination .page-item { margin: 0 4px; }
        .pagination .page-link { border-radius: var(--bs-pagination-border-radius) !important; }
        .pagination .page-item.active .page-link { box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2); transform: translateY(-2px); }
    </style>
    <?= $this->renderSection('styles') ?>
    
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1266537441823413');
    //fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1266537441823413&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>

<body class="d-flex flex-column min-vh-100">
    
    <nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fs-4" href="<?= url_to('landing') ?>"><i class="bi bi-box"></i> AFRIKENKID</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (session()->get('isLoggedIn')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'home') ? 'active' : '' ?>" href="<?= url_to('home') ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'gemini') ? 'active' : '' ?>" href="<?= url_to('gemini.index') ?>">AI Studio</a></li>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'crypto') ? 'active' : '' ?>" href="<?= url_to('crypto.index') ?>">Crypto Query</a></li>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'payment') ? 'active' : '' ?>" href="<?= url_to('payment.index') ?>">Top Up</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li class="nav-item"><a class="nav-link <?= current_url() == url_to('landing') ? 'active' : '' ?>" href="<?= url_to('landing') ?>">Home</a></li>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'ai-studio') ? 'active' : '' ?>" href="<?= url_to('gemini.public') ?>">AI Studio</a></li>
                        <li class="nav-item"><a class="nav-link <?= str_contains(current_url(), 'crypto-query') ? 'active' : '' ?>" href="<?= url_to('crypto.public') ?>">Crypto Query</a></li>
                        <li class="nav-item d-flex align-items-center ms-lg-3">
                            <a class="btn btn-outline-primary" href="<?= url_to('login') ?>">Login</a>
                            <a class="btn btn-primary ms-2" href="<?= url_to('register') ?>">Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <span class="nav-link theme-toggle" id="theme-toggle-desktop">
                            <i class="bi bi-moon-stars-fill"></i>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileNavLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <?php if (session()->get('isLoggedIn')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('home') ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('gemini.index') ?>">AI Studio</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('crypto.index') ?>">Crypto Query</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('payment.index') ?>">Top Up</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('account.index') ?>">My Account</a></li>
                    <?php if (session()->get('is_admin')): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url_to('admin.index') ?>">Admin Panel</a></li>
                    <?php endif; ?>
                     <li class="nav-item"><hr class="dropdown-divider"></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('logout') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('landing') ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('gemini.public') ?>">AI Studio</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url_to('crypto.public') ?>">Crypto Query</a></li>
                    <li class="nav-item auth-buttons-mobile">
                        <div class="d-grid gap-2">
                            <a class="btn btn-outline-primary" href="<?= url_to('login') ?>">Login</a>
                            <a class="btn btn-primary" href="<?= url_to('register') ?>">Register</a>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="nav-item mt-3 d-flex justify-content-between align-items-center">
                    <span>Theme</span>
                    <span class="theme-toggle" id="theme-toggle-mobile">
                        <i class="bi bi-moon-stars-fill"></i>
                    </span>
                </li>
            </ul>
        </div>
    </div>


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
                    <p>Build smarter with pay-as-you-go platform. Leverage Generative AI (Google Gemini), query real-time BTC & LTC wallet data, and securely top up with M-Pesa. Built for developers in Kenya and Africa.</p>
                    <div class="social-icons">
                        <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                        <a href="https://www.linkedin.com/in/nehemia-obati-b74886344" class="linkedin"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="github"><i class="bi bi-github"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase">Services</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url_to('gemini.public') ?>">Gemini AI</a></li>
                        <li><a href="<?= url_to('crypto.public') ?>">Crypto Data</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5 class="text-uppercase">Support</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= url_to('contact.form') ?>">Contact Us</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="<?= url_to('documentation') ?>">Documentation</a></li>
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
    
    <?php if ($showCookieBanner ?? false): ?>
        <?= $this->include('partials/cookie_banner') ?>
    <?php endif; ?>

    <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const themeToggles = document.querySelectorAll('.theme-toggle');
            const sunIcon = '<i class="bi bi-brightness-high-fill"></i>';
            const moonIcon = '<i class="bi bi-moon-stars-fill"></i>';

            const getStoredTheme = () => localStorage.getItem('theme');
            const setStoredTheme = theme => localStorage.setItem('theme', theme);

            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme();
                if (storedTheme) {
                    return storedTheme;
                }
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };

            const setTheme = theme => {
                if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-bs-theme', theme);
                }
                updateToggleIcons(theme);
            };
            
            const updateToggleIcons = (theme) => {
                 themeToggles.forEach(toggle => {
                    toggle.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
                });
            };

            setTheme(getPreferredTheme());

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                const storedTheme = getStoredTheme();
                if (storedTheme !== 'light' && storedTheme !== 'dark') {
                    setTheme(getPreferredTheme());
                }
            });

            themeToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const theme = getStoredTheme() === 'dark' ? 'light' : 'dark';
                    setStoredTheme(theme);
                    setTheme(theme);
                });
            });

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

            document.body.style.visibility = 'visible';
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>

</html>