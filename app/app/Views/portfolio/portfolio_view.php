<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
    html {
        scroll-behavior: smooth;
    }

    /* Add scroll-margin-top to sections to offset for the sticky navbars */
    main section[id] {
        scroll-margin-top: 160px; /* Combined height of both navbars */
    }

    /* Section-specific styles adapted for the light theme */
    .section-title h2 {
        font-weight: 700;
        color: var(--dark-gray);
    }

    /* Quick Navigation for page sections */
    .quick-nav {
        position: sticky;
        top: 85px; /* Adjust based on main navbar height */
        z-index: 999;
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 0.75rem;
        padding: 0.5rem 1rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07);
        margin-bottom: 4rem;
        margin-top: 2rem;
    }

    .quick-nav .nav-link {
        color: var(--secondary-color);
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
    }

    .quick-nav .nav-link:hover {
        color: var(--primary-color);
        background-color: #e9ecef;
    }

    .quick-nav .nav-link.active {
        color: #fff;
        background-color: var(--primary-color);
    }

    /* Hero Section */
    .hero-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        align-items: center;
        gap: 50px;
        padding: 4rem 0;
    }

    .hero-text h1 {
        font-weight: 700;
        color: var(--dark-gray);
    }

    .hero-text h1 strong {
        color: var(--primary-color);
    }

    .hero-text .subtitle {
        font-size: 1.5rem;
        margin: 10px 0 20px;
        font-weight: 500;
        color: var(--secondary-color);
    }

    .hero-text p {
        margin-bottom: 30px;
        max-width: 600px;
    }

    .hero-image {
        width: 250px;
        height: 250px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    /* Card-based sections */
    .skill-category,
    .portfolio-card,
    .education-item,
    .detail-card,
    .reference-item {
        background-color: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }

    .skills-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .skill-category h3 {
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .skill-category ul {
        list-style: none;
        padding: 0;
    }

    .skill-category ul li {
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .skill-category ul li:last-child {
        border-bottom: none;
    }

    .education-grid,
    .personal-details-grid,
    .references-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    /* Work Timeline */
    .work-timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .work-timeline::after {
        content: '';
        position: absolute;
        width: 4px;
        background-color: #e9ecef;
        top: 0;
        bottom: 0;
        left: 50%;
        margin-left: -2px;
    }

    .work-item {
        padding: 10px 40px;
        position: relative;
        background-color: inherit;
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
        width: 20px;
        height: 20px;
        right: -10px;
        background-color: var(--primary-color);
        border: 4px solid var(--light-gray);
        top: 25px;
        border-radius: 50%;
        z-index: 1;
    }

    .work-item:nth-child(even)::after {
        left: -10px;
    }

    .work-content {
        padding: 20px 30px;
        background-color: #fff;
        position: relative;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    /* Portfolio */
    .portfolio-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 30px;
    }

    .portfolio-card {
        transition: transform 0.3s ease;
    }

    .portfolio-card:hover {
        transform: translateY(-5px);
    }

    .portfolio-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
    }

    .portfolio-content {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .portfolio-content p {
        flex-grow: 1;
        margin: 15px 0 25px 0;
    }

    /* Contact Form */
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 50px;
        align-items: center;
    }

    @media (max-width: 992px) {
        .hero-grid {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .hero-text {
            order: 2;
        }

        .hero-image-container {
            order: 1;
            margin-bottom: 40px;
        }

        .hero-text p {
            margin-left: auto;
            margin-right: auto;
        }

        .work-timeline::after {
            left: 10px;
        }

        .work-item {
            width: 100%;
            padding-left: 50px;
            padding-right: 10px;
        }

        .work-item:nth-child(even) {
            left: 0%;
        }

        .work-item::after {
            left: 1px;
        }
    }

    @media (max-width: 768px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }

        .quick-nav {
            flex-direction: column !important;
            text-align: center;
        }
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
  "image": "<?= base_url('assets/images/potraitwebp.webp') ?>",
  "sameAs": [
    "https://www.linkedin.com/in/nehemia-obati-b74886344"
  ],
  "jobTitle": "Software Developer",
  "worksFor": {
    "@type": "Organization",
    "name": "Kingsway Business Systems LTD"
  },
  "knowsAbout": ["PHP", "CodeIgniter", "Python", "Google Cloud Platform", "AWS", "Linux", "Server Management", "SQL", "JavaScript"]
}
</script>
    <div class="container">
        <!-- Hero Section -->
        <section id="home" class="py-5">
            <div class="hero-grid">
                <div class="hero-text">
                    <h1>I am <strong>Nehemia Obati</strong></h1>
                    <p class="subtitle">Software Developer</p>
                    <p>I'm a full-stack developer with a passion for crafting dynamic and user-friendly web experiences. Fluent in technologies from front-end languages to back-end powerhouses like Python and PHP, and proficient in cloud platforms like GCP and AWS. My expertise in Bash and Linux empowers me to manage server environments with ease. I am constantly exploring new technologies to stay at the forefront of web development and am excited to leverage my comprehensive skillset to create innovative and impactful solutions.</p>
                    <a href="#portfolio" class="btn btn-primary">View My Work</a>
                    <a href="<?= base_url('assets/Nehemia Obati Resume.pdf') ?>" class="btn btn-outline-primary ms-2" target="_blank">Download Resume</a>
                </div>
                <div class="hero-image-container text-center">
                    <img src="<?= base_url('assets/images/potraitwebp.webp') ?>" alt="Nehemia Obati" class="hero-image">
                </div>
            </div>
        </section>

        <!-- Quick Navigation -->
        <nav class="nav nav-pills flex-column flex-sm-row justify-content-center quick-nav">
            <a class="nav-link" href="#skills">Skills</a>
            <a class="nav-link" href="#portfolio">Portfolio</a>
            <a class="nav-link" href="#work">Experience</a>
            <a class="nav-link" href="#education">Education</a>
            <a class="nav-link" href="#contact">Contact</a>
        </nav>

        <!-- Skills Section -->
        <section id="skills" class="py-5">
            <div class="section-title text-center">
                <h2>Technical Skills</h2>
            </div>
            <div class="skills-grid">
                <div class="skill-category">
                    <h3>Cloud & Servers</h3>
                    <ul>
                        <li>Cloud Environments Setup (GCP, AWS, Azure)</li>
                        <li>Local/Self-Hosted Server Setup</li>
                        <li>Linux/Windows Server Management</li>
                        <li>Windows IIS & Apache2 Web Server Config</li>
                    </ul>
                </div>
                <div class="skill-category">
                    <h3>Programming & Web</h3>
                    <ul>
                        <li>PHP (CodeIgniter) & Python (Flask)</li>
                        <li>HTML5, CSS, JavaScript</li>
                        <li>MySQL Database Management</li>
                    </ul>
                </div>
                <div class="skill-category">
                    <h3>Automation & Tools</h3>
                    <ul>
                        <li>Power Automate</li>
                        <li>Bash Scripting</li>
                        <li>Git & Version Control</li>
                        <li>Manual & Automated Testing</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Portfolio Section -->
        <section id="portfolio" class="py-5">
            <div class="section-title text-center">
                <h2>Portfolio</h2>
            </div>
            <div class="portfolio-grid">
                <div class="portfolio-card"> <img src="https://placehold.co/600x400/0d6efd/ffffff?text=PIMIS" alt="Screenshot of the PIMIS - Public Investment Management System for the National Treasury.">
                    <div class="portfolio-content">
                        <h3>PIMIS - Public Investment Management System</h3>
                        <p>A system for the National Treasury. Project TENDER NO. TNT/025/2020-2021.</p> <a href="https://pimisdev.treasury.go.ke/" target="_blank" class="btn btn-primary">View Project</a>
                    </div>
                </div>
                <div class="portfolio-card"> <img src="https://placehold.co/600x400/198754/ffffff?text=ECIPMS" alt="Dashboard of the ECIPMS - County Integrated Planning Management System for Kakamega County.">
                    <div class="portfolio-content">
                        <h3>ECIPMS - County Integrated Planning Management System</h3>
                        <p>Automated M&E system for Kakamega County Government. CONTRACT FOR THE SUPPLY, INSTALLATION AND COMMISSIONING OF STANDARDIZED AUTOMATED MONITORING AND EVALUATION SYSTEM. Project TENDER NO. CGKK/OG/2020/2021/01.</p> <a href="https://ecipms.kingsway.co.ke/" target="_blank" class="btn btn-primary">View Project</a>
                    </div>
                </div>
                <div class="portfolio-card"> <img src="https://placehold.co/600x400/6f42c1/ffffff?text=IFMIS" alt="The IFMIS e-procurement module for the National Treasury of Kenya.">
                    <div class="portfolio-content">
                        <h3>IFMIS - National Treasury</h3>
                        <p>Onsite support for IFMIS applications and E-Procurement enhancement. TENDER FOR PROVISION OF ONSITE SUPPORT FOR IFMIS APPLICATIONS AND ENHANCEMENT OF IFMIS E-PROCUREMENT. Project TENDER NO. TNT/029/2019-2020.</p>
                    </div>
                </div>
                <div class="portfolio-card"> <img src="https://placehold.co/600x400/fd7e14/ffffff?text=Oracle+Support" alt="Oracle Database logo representing the application support license project.">
                    <div class="portfolio-content">
                        <h3>Oracle E-Procurement - National Treasury</h3>
                        <p>Provision of Oracle application support licenses. TENDER FOR THE PROVISION OF ORACLE APPLICATION SUPPORT LICENSES. Project TENDER NO. TNT/026/2019-2020.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Work History Section -->
        <section id="work" class="py-5">
            <div class="section-title text-center">
                <h2>Work Experience</h2>
            </div>
            <div class="work-timeline">
                <div class="work-item">
                    <div class="work-content">
                        <p class="date">2021-01 - Current</p>
                        <h3>ICT Support</h3>
                        <p class="company">Kingsway Business Systems LTD</p>
                        <ul>
                            <li>Provided technical support, troubleshooting hardware/software, and environment setup for key government information systems (PIMIS, ECIPMS, IFMIS).</li>
                            <li>Maintained infrastructure, including software patching/updates, backups, and system performance monitoring.</li>
                            <li>Conducted manual and automated testing; maintained testing environments.</li>
                            <li>Documented technical procedures, installation instructions, and system specifications.</li>
                            <li>Delivered developer and user training sessions on new technologies and system features.</li>
                            <li>Interfaced with project managers and business users on technical matters.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Education & Certifications -->
        <section id="education" class="py-5 bg-light">
            <div class="container">
                <div class="section-title text-center">
                    <h2>Education & Certifications</h2>
                </div>
                <div class="education-grid">
                    <div class="education-item">
                        <h3>Computer Science</h3>
                        <h4>Zetech University - Ruiru</h4>
                        <p>Graduated: 2021-11</p>
                    </div>
                    <div class="education-item">
                        <h3>Certificate: CCNA 1-3 & Cyber Ops</h3>
                        <h4>Zetech University - Ruiru</h4>
                        <p>Completed: 2020-09</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Personal Details -->
        <section id="personal-details" class="py-5">
            <div class="personal-details-grid">
                <div class="detail-card">
                    <h3>Languages</h3>
                    <ul>
                        <li>English</li>
                        <li>Kiswahili</li>
                    </ul>
                </div>
                <div class="detail-card">
                    <h3>Interests</h3>
                    <ul>
                        <li>E-Sports</li>
                        <li>Basketball</li>
                        <li>Travelling</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- References -->
        <section id="references" class="py-5 bg-light">
            <div class="container">
                <div class="section-title text-center">
                    <h2>References</h2>
                </div>
                <div class="references-grid">
                    <div class="reference-item">
                        <p class="name">Kenneth Kadenge</p>
                        <p class="title">Project Manager, Kingsway Business Service Ltd.</p>
                        <p>Tel: 0722 310 030</p>
                    </div>
                    <div class="reference-item">
                        <p class="name">Dan Njiru</p>
                        <p class="title">Head of Department, Zetech University</p>
                        <p>Tel: 0719 321 351</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-5">
            <div class="section-title text-center">
                <h2>Get In Touch</h2>
            </div>
            <div class="contact-grid">
                <div>
                    <h4>Contact Information</h4>
                    <p>Feel free to reach out via email or phone, or send a message using the form.</p>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i> 00100, Nairobi Kenya</li>
                        <li class="mb-3"><i class="bi bi-envelope-fill text-primary me-2"></i> <a href="mailto:nehemiaobati@gmail.com">nehemiaobati@gmail.com</a></li>
                        <li class="mb-3"><i class="bi bi-telephone-fill text-primary me-2"></i> <a href="tel:+254794587533">+254794587533</a></li>
                    </ul>
                </div>
                <div>
                    <?= form_open(url_to('portfolio.sendEmail')) ?>
                    <div class="form-floating mb-3"><input type="text" class="form-control" id="name" name="name" placeholder="Name" required><label for="name">Name</label></div>
                    <div class="form-floating mb-3"><input type="email" class="form-control" id="email" name="email" placeholder="Email" required><label for="email">Email</label></div>
                    <div class="form-floating mb-3"><input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required><label for="subject">Subject</label></div>
                    <div class="form-floating mb-3"><textarea class="form-control" placeholder="Your Message" id="message" name="message" style="height: 120px" required></textarea><label for="message">Your Message</label></div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                    <?= form_close() ?>
                </div>
            </div>
        </section>
    </div>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const quickNavLinks = document.querySelectorAll('.quick-nav .nav-link');
        const sections = document.querySelectorAll('main section[id]');

        // No script needed for click-to-scroll, handled by CSS `scroll-margin-top` and `scroll-behavior: smooth;`
        
        // Intersection Observer for active link highlighting
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    quickNavLinks.forEach(link => {
                        link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                    });
                }
            });
        }, {
            rootMargin: '-170px 0px -50% 0px', // Adjusted root margin to trigger when the title is near the top
            threshold: 0
        });

        sections.forEach(section => {
            observer.observe(section);
        });
    });
</script>
<?= $this->endSection() ?>
