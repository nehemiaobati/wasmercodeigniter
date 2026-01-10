<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<style>
    /* Section offset for sticky nav */
    main section[id] {
        scroll-margin-top: 170px;
        /* Account for both main navbar and quick-nav height */
    }

    /* Quick Nav Glassmorphism & Position */
    .quick-nav-wrapper {
        position: sticky;
        top: 85px;
        /* Directly below main navbar */
        z-index: 1020;
    }

    .quick-nav {
        background-color: rgba(var(--bs-body-bg-rgb), 0.8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .quick-nav .nav-link {
        color: var(--bs-body-color);
        font-weight: 500;
        transition: all 0.2s;
    }

    .quick-nav .nav-link:hover,
    .quick-nav .nav-link.active {
        color: var(--bs-primary);
        background-color: var(--bs-primary-bg-subtle);
    }

    /* Timeline Component */
    .work-timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .work-timeline::after {
        content: '';
        position: absolute;
        width: 3px;
        background-color: var(--bs-border-color);
        top: 0;
        bottom: 0;
        left: 50%;
        margin-left: -1px;
    }

    .work-item {
        padding: 10px 40px;
        position: relative;
        width: 50%;
    }

    .work-item:nth-child(odd) {
        left: 0;
    }

    .work-item:nth-child(even) {
        left: 50%;
    }

    .work-item::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        right: -8px;
        top: 30px;
        background-color: var(--bs-primary);
        border: 4px solid var(--bs-body-bg);
        border-radius: 50%;
        z-index: 1;
        box-shadow: 0 0 0 1px var(--bs-border-color);
    }

    .work-item:nth-child(even)::after {
        left: -8px;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .work-timeline::after {
            left: 20px;
        }

        .work-item {
            width: 100%;
            padding-left: 50px;
            padding-right: 0;
            left: 0 !important;
        }

        .work-item::after {
            left: 12px !important;
        }

        .hero-img {
            width: 200px;
            height: 200px;
        }
    }

    .hero-img {
        width: 280px;
        height: 280px;
        object-fit: cover;
        border: 6px solid rgba(var(--bs-body-bg-rgb), 0.5);
    }

    .portfolio-img {
        height: 220px;
        object-fit: cover;
        width: 100%;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .hero-bg-blur {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 110%;
        height: 110%;
        background-color: var(--bs-primary);
        opacity: 0.4;
        border-radius: 50%;
        filter: blur(50px);
        z-index: -1;
    }

    .icon-circle-lg {
        width: 40px;
        height: 40px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Person",
            "name": "Nehemia Obati",
            "url": "<?= url_to('portfolio.index') ?>",
            "image": "<?= base_url('public/assets/images/potraitwebp.webp') ?>",
            "sameAs": ["https://www.linkedin.com/in/nehemia-obati-b74886344"],
            "jobTitle": "Software Developer",
            "worksFor": {
                "@type": "Organization",
                "name": "Kingsway Business Systems LTD"
            },
            "knowsAbout": ["PHP", "CodeIgniter", "Python", "Google Cloud Platform", "AWS", "Linux", "Server Management", "SQL", "JavaScript"]
        }
    </script>
    <div class="container pb-5">
        <!-- Hero Section -->
        <section id="home" class="py-5 mb-4">
            <div class="row align-items-center gy-5">
                <div class="col-lg-7 order-2 order-lg-1 text-center text-lg-start">
                    <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 mb-3">Software Developer</div>
                    <h1 class="display-3 fw-bold mb-3">I am <span class="text-primary">Nehemia Obati</span></h1>
                    <p class="lead text-body-secondary mb-4 col-lg-10 px-0">
                        Full-Stack Developer specializing in robust web ecosystems. I build scalable applications using <strong>PHP</strong>, <strong>Python</strong>, and <strong>HTML</strong>, orchestrated on <strong>GCP</strong>, <strong>AWS</strong>, and <strong>Azure</strong>. With deep expertise in <strong>Linux</strong> and <strong>Windows</strong> server management, I deliver solutions that are as reliable as they are dynamic.
                    </p>
                    <div class="d-flex gap-2 justify-content-center justify-content-lg-start">
                        <a href="#portfolio" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">View My Work</a>
                        <a href="<?= base_url('public/assets/Nehemia Obati Resume.pdf') ?>" class="btn btn-outline-primary btn-lg rounded-pill px-4" target="_blank">Download Resume</a>
                    </div>
                </div>
                <div class="col-lg-5 order-1 order-lg-2 text-center">
                    <div class="position-relative d-inline-block">
                        <div class="hero-bg-blur"></div>
                        <img src="<?= base_url('public/assets/images/potraitwebp.webp') ?>" alt="Nehemia Obati" class="hero-img rounded-circle shadow-lg">
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Navigation -->
        <div class="quick-nav-wrapper mb-5">
            <nav class="nav nav-pills justify-content-center quick-nav shadow-sm rounded-4 p-2 border border-secondary-subtle">
                <a class="nav-link rounded-pill" href="#skills">Skills</a>
                <a class="nav-link rounded-pill" href="#portfolio">Portfolio</a>
                <a class="nav-link rounded-pill" href="#work">Experience</a>
                <a class="nav-link rounded-pill" href="#education">Education</a>
                <a class="nav-link rounded-pill" href="#contact">Contact</a>
            </nav>
        </div>

        <!-- Skills Section -->
        <section id="skills" class="py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6">Technical Skills</h2>
            </div>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                    <div class="blueprint-card h-100 p-4">
                        <h4 class="fw-bold border-bottom border-primary pb-2 mb-3 text-primary">Cloud & Servers</h4>
                        <ul class="list-unstyled d-grid gap-2 mb-0">
                            <li class="d-flex align-items-center"><i class="bi bi-cloud-check me-2 text-body-tertiary"></i>Cloud Setup (GCP, AWS, Azure)</li>
                            <li class="d-flex align-items-center"><i class="bi bi-server me-2 text-body-tertiary"></i>Local/Self-Hosted Servers</li>
                            <li class="d-flex align-items-center"><i class="bi bi-hdd-network me-2 text-body-tertiary"></i>Linux/Windows Management</li>
                            <li class="d-flex align-items-center"><i class="bi bi-gear-wide-connected me-2 text-body-tertiary"></i>IIS & Apache2 Config</li>
                        </ul>
                    </div>
                </div>
                <div class="col">
                    <div class="blueprint-card h-100 p-4">
                        <h4 class="fw-bold border-bottom border-primary pb-2 mb-3 text-primary">Programming</h4>
                        <ul class="list-unstyled d-grid gap-2 mb-0">
                            <li class="d-flex align-items-center"><i class="bi bi-code-slash me-2 text-body-tertiary"></i>PHP (CodeIgniter) & Python (Flask)</li>
                            <li class="d-flex align-items-center"><i class="bi bi-filetype-html me-2 text-body-tertiary"></i>HTML5, CSS, JavaScript</li>
                            <li class="d-flex align-items-center"><i class="bi bi-database me-2 text-body-tertiary"></i>MySQL Management</li>
                        </ul>
                    </div>
                </div>
                <div class="col">
                    <div class="blueprint-card h-100 p-4">
                        <h4 class="fw-bold border-bottom border-primary pb-2 mb-3 text-primary">Automation</h4>
                        <ul class="list-unstyled d-grid gap-2 mb-0">
                            <li class="d-flex align-items-center"><i class="bi bi-robot me-2 text-body-tertiary"></i>Power Automate</li>
                            <li class="d-flex align-items-center"><i class="bi bi-terminal me-2 text-body-tertiary"></i>Bash Scripting</li>
                            <li class="d-flex align-items-center"><i class="bi bi-git me-2 text-body-tertiary"></i>Git & Version Control</li>
                            <li class="d-flex align-items-center"><i class="bi bi-check2-circle me-2 text-body-tertiary"></i>Testing (Manual/Auto)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portfolio Section -->
        <section id="portfolio" class="py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6">Portfolio</h2>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <!-- Project Items -->
                <?php
                $projects = [
                    [
                        'title' => 'PIMIS',
                        'desc' => 'Public Investment Management System for the National Treasury. TNT/025/2020-2021.',
                        'img' => 'https://placehold.co/600x400/0d6efd/ffffff?text=PIMIS',
                        'link' => 'https://pimisdev.treasury.go.ke/'
                    ],
                    [
                        'title' => 'ECIPMS',
                        'desc' => 'Automated M&E system for Kakamega County. TENDER NO. CGKK/OG/2020/2021/01.',
                        'img' => 'https://placehold.co/600x400/198754/ffffff?text=ECIPMS',
                        'link' => 'https://ecipms.kingsway.co.ke/'
                    ],
                    [
                        'title' => 'IFMIS',
                        'desc' => 'Onsite support for IFMIS applications & E-Procurement. TNT/029/2019-2020.',
                        'img' => 'https://placehold.co/600x400/6f42c1/ffffff?text=IFMIS',
                        'link' => null
                    ],
                    [
                        'title' => 'Oracle Support',
                        'desc' => 'Provision of Oracle application support licenses. TNT/026/2019-2020.',
                        'img' => 'https://placehold.co/600x400/fd7e14/ffffff?text=Oracle+Support',
                        'link' => null
                    ]
                ];
                ?>
                <?php foreach ($projects as $proj): ?>
                    <div class="col">
                        <div class="card blueprint-card h-100 border-0 overflow-hidden">
                            <img src="<?= $proj['img'] ?>" alt="<?= $proj['title'] ?>" class="portfolio-img" loading="lazy">
                            <div class="card-body p-4 d-flex flex-column">
                                <h4 class="card-title fw-bold"><?= $proj['title'] ?></h4>
                                <p class="card-text text-body-secondary flex-grow-1"><?= $proj['desc'] ?></p>
                                <?php if ($proj['link']): ?>
                                    <a href="<?= $proj['link'] ?>" target="_blank" class="btn btn-outline-primary mt-3 stretched-link">View Project</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Work History Section -->
        <section id="work" class="py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6">Work Experience</h2>
            </div>
            <div class="work-timeline">
                <div class="work-item">
                    <div class="blueprint-card p-4">
                        <span class="badge bg-primary mb-2">2021-01 - Current</span>
                        <h4 class="fw-bold">ICT Support</h4>
                        <h6 class="text-muted mb-3">Kingsway Business Systems LTD</h6>
                        <ul class="mb-0 ps-3 small text-body-secondary">
                            <li class="mb-1">Technical support & environment setup for PIMIS, ECIPMS, IFMIS.</li>
                            <li class="mb-1">Infrastructure maintenance: patching, backups, monitoring.</li>
                            <li class="mb-1">Manual & automated testing.</li>
                            <li class="mb-1">Documentation & user training.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Education & Certifications -->
        <section id="education" class="py-5">
            <div class="blueprint-card p-5 bg-opacity-10 bg-primary-subtle">
                <div class="text-center mb-5">
                    <h2 class="fw-bold display-6">Education & Certifications</h2>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-5">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-mortarboard fs-1 text-primary mb-3"></i>
                                <h5 class="card-title fw-bold">Computer Science</h5>
                                <p class="card-text text-muted">Zetech University - Ruiru</p>
                                <span class="badge bg-secondary">Graduated: 2021-11</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-patch-check fs-1 text-success mb-3"></i>
                                <h5 class="card-title fw-bold">CCNA 1-3 & Cyber Ops</h5>
                                <p class="card-text text-muted">Zetech University - Ruiru</p>
                                <span class="badge bg-secondary">Completed: 2020-09</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Personal Details -->
        <section id="personal-details" class="py-5">
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col">
                    <div class="blueprint-card p-4 text-center h-100">
                        <i class="bi bi-translate fs-2 text-primary mb-2"></i>
                        <h4 class="fw-bold">Languages</h4>
                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <span class="badge bg-body-secondary text-body-emphasis border">English</span>
                            <span class="badge bg-body-secondary text-body-emphasis border">Kiswahili</span>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="blueprint-card p-4 text-center h-100">
                        <i class="bi bi-star fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold">Interests</h4>
                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <span class="badge bg-body-secondary text-body-emphasis border">E-Sports</span>
                            <span class="badge bg-body-secondary text-body-emphasis border">Basketball</span>
                            <span class="badge bg-body-secondary text-body-emphasis border">Travelling</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- References -->
        <section id="references" class="py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6">References</h2>
            </div>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 bg-body-tertiary">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold">Kenneth Kadenge</h5>
                            <p class="text-muted mb-2">Project Manager, Kingsway Business Service Ltd.</p>
                            <a href="tel:0722310030" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-telephone-fill me-1"></i> 0722 310 030</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 bg-body-tertiary">
                        <div class="card-body p-4 text-center">
                            <h5 class="fw-bold">Dan Njiru</h5>
                            <p class="text-muted mb-2">Head of Department, Zetech University</p>
                            <a href="tel:0719321351" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-telephone-fill me-1"></i> 0719 321 351</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-5 mb-5">
            <div class="row g-5">
                <div class="col-lg-5">
                    <h2 class="fw-bold mb-4">Get In Touch</h2>
                    <p class="lead text-body-secondary mb-4">Feel free to reach out via email or phone, or send a message using the form. I'm always open to discussing new projects or opportunities.</p>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center p-3 rounded-3 bg-body-tertiary border">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center icon-circle-lg">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Location</h6>
                                <span class="text-body-secondary small">00100, Nairobi Kenya</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-3 rounded-3 bg-body-tertiary border">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center icon-circle-lg">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Email</h6>
                                <a href="mailto:nehemiaobati@gmail.com" class="text-decoration-none small text-body-secondary">nehemiaobati@gmail.com</a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-3 rounded-3 bg-body-tertiary border">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center icon-circle-lg">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">Phone</h6>
                                <a href="tel:+254794587533" class="text-decoration-none small text-body-secondary">+254794587533</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="blueprint-card p-4 p-md-5">
                        <h4 class="mb-4 fw-bold">Send a Message</h4>
                        <?= form_open(url_to('portfolio.sendEmail'), ['id' => 'contactForm', 'class' => 'needs-validation']) ?>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" autocomplete="name" required>
                            <label for="name">Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" autocomplete="email" required>
                            <label for="email">Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                            <label for="subject">Subject</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" placeholder="Your Message" id="message" name="message" style="height: 150px" required></textarea>
                            <label for="message">Your Message</label>
                        </div>
                        <div class="mb-4">
                            <div class="g-recaptcha" data-sitekey="<?= service('recaptchaService')->getSiteKey() ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2" id="sendMessageButton">
                            <i class="bi bi-send-fill"></i> Send Message
                        </button>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    class PortfolioApp {
        constructor() {
            this.quickNavLinks = document.querySelectorAll('.quick-nav .nav-link');
            this.sections = document.querySelectorAll('main section[id]');
            this.contactForm = document.getElementById('contactForm');
            this.sendMessageButton = document.getElementById('sendMessageButton');
            this.init();
        }

        init() {
            this.initScrollSpy();
            this.initContactForm();
        }

        initScrollSpy() {
            if (!this.quickNavLinks.length || !this.sections.length) return;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.updateActiveNavLink(entry.target.getAttribute('id'));
                    }
                });
            }, {
                rootMargin: '-150px 0px -60% 0px'
            }); // Adjusted for better active state accuracy

            this.sections.forEach(section => observer.observe(section));
        }

        updateActiveNavLink(sectionId) {
            this.quickNavLinks.forEach(link => {
                const isActive = link.getAttribute('href') === `#${sectionId}`;
                link.classList.toggle('active', isActive);
            });
        }

        initContactForm() {
            if (!this.contactForm || !this.sendMessageButton) return;
            this.contactForm.addEventListener('submit', () => {
                this.sendMessageButton.setAttribute('disabled', 'disabled');
                this.sendMessageButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
            });
        }
    }
    document.addEventListener('DOMContentLoaded', () => new PortfolioApp());
</script>
<?= $this->endSection() ?>