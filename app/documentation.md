# Comprehensive Project Documentation

## 1. Project Overview

The Web Platform is a comprehensive, multi-functional application built on the CodeIgniter 4 framework. It serves as a portal for registered users to access a suite of powerful digital services. The platform is designed with a modular architecture, featuring a robust user authentication system, an account management dashboard with an integrated balance and payment system, and an administrative panel for user oversight.

The core services offered include:
*   **Gemini AI Studio:** An advanced interface allowing users to interact with Google's Gemini AI, featuring text and multimedia prompts, conversational memory, and context-aware responses.
*   **Cryptocurrency Data Service:** A tool for querying real-time balance and transaction data for Bitcoin (BTC) and Litecoin (LTC) addresses.

The application integrates with several third-party APIs, including Paystack for secure payments, Google reCAPTCHA for spam prevention, and the respective blockchain and AI service endpoints.

## 2. Key Features

*   **User Authentication:** Secure registration, login, password reset, and email verification flows.
*   **Payment Gateway Integration:** Seamless payment processing via Paystack, supporting M-Pesa, Airtel Money, and card payments.
*   **AI Service Integration:** Advanced interaction with Google's Gemini API, including text and multimedia prompts, conversational memory (`MemoryService`), and advanced keyword extraction (`TokenService`).
*   **Cryptocurrency Data Service:** Real-time balance and transaction queries for Bitcoin (BTC) and Litecoin (LTC) addresses.
*   **Administrative Dashboard:** A secure area for administrators to manage users, update balances, view logs, and send email campaigns.
*   **Secure Data Handling:** Adherence to security best practices, including the use of `.env` for credentials, CSRF protection, and input validation.
*   **Offline Model Training:** A dedicated CLI command (`php spark train`) for training text classification models separately from the web application.

## 3. Core Technologies

*   **Backend:** PHP 8.1+, CodeIgniter 4, Pandoc, ffmpeg
*   **Frontend:** Bootstrap 5, JavaScript, HTML5, CSS3
*   **Database:** MySQL (via MySQLi driver)
*   **Key Libraries:** Parsedown (for Markdown rendering), Dompdf (for PDF generation), PHP-FFMpeg (for audio processing), NlpTools (for Natural Language Processing)
*   **Development & Deployment:** Composer, PHPUnit, Spark CLI, Git

## 4. Installation and Setup

This guide covers both automated and manual setup procedures.

### 4.1. Prerequisites

Ensure your system has the following installed before you begin:
*   **PHP:** Version 8.1 or higher with `intl`, `mbstring`, `bcmath`, `curl`, `xml`, `zip`, and `gd` extensions.
*   **MySQL Server:** For the application database.
*   **Composer:** For managing PHP dependencies.
*   **Node.js & NPM:** For frontend asset management (if applicable).
*   **Git:** For cloning the project repository.
*   **Pandoc:** For document conversion (e.g., `sudo apt install pandoc`).
*   **ffmpeg:** For video and audio processing (e.g., `sudo apt install ffmpeg`).

### 4.2. Automated Setup on Ubuntu (`setup.sh`)

The `setup.sh` script is the recommended method for a clean Ubuntu server installation. It automates the entire process.

1.  **Save the script:** Save the `setup.sh` file to your server.
2.  **Make it executable:** `chmod +x setup.sh`
3.  **Run with sudo:** `sudo ./setup.sh`

The script will:
*   Update the system and install essential utilities.
*   Install Apache2, PHP 8.2 with required extensions, and MySQL Server.
*   Create a secure database and user, generating a random password.
*   Install Composer and Node.js.
*   Clone the project repository.
*   Install project dependencies, create the `.env` file, and run database migrations.
*   Configure an Apache virtual host to serve the application.
*   Display a summary with credentials and next steps.

### 4.3. Manual Installation

1.  **Clone Repository:** `git clone <repository_url> .`
2.  **Install Dependencies:** Run `composer install` to download PHP packages.
3.  **Configure Environment:**
    *   Copy `env` to a new file named `.env`.
    *   Open `.env` and configure all `app.*`, `database.*`, `email.*`, and API key variables (see section 4.4).
4.  **Database Migration:** Run `php spark migrate` to create the database tables.
5.  **Set Permissions:** Ensure the `writable` directory is writable by the web server: `chmod -R 775 writable/`.
6.  **Web Server Setup:** Configure your web server (Apache/Nginx) to point its document root to the project's `public` directory.

### 4.4. Environment Configuration (`.env` file)

After setup (automated or manual), you must configure the `.env` file with your specific credentials:

*   `CI_ENVIRONMENT`: Set to `production` for live environments, `development` otherwise.
*   `app.baseURL`: The base URL of your application (e.g., `http://yourdomain.com`).
*   `database.default.*`: Database connection details. The `setup.sh` script configures this automatically.
*   `encryption.key`: A unique, 32-character random string for encryption. The `setup.sh` script generates this.
*   `PAYSTACK_SECRET_KEY`: Your secret key from Paystack.
*   `GEMINI_API_KEY`: Your API key for Google Gemini.
*   `recaptcha_siteKey` & `recaptcha_secretKey`: Your keys for Google reCAPTCHA.
*   `email.*`: Configuration for your email sending service (e.g., SMTP server, credentials).

**IMPORTANT:** The `.env` file contains sensitive credentials and must **NEVER** be committed to version control.

### 4.5. Post-Installation Steps

1.  **Edit `.env`:** As mentioned above, configure all your API keys and service credentials.
2.  **Domain Configuration:** Point your domain's DNS 'A' record to the server's IP address.
3.  **HTTPS Setup (Recommended):** For security, enable HTTPS using Certbot.
    ```bash
    sudo apt install certbot python3-certbot-apache
    sudo certbot --apache
    ```

## 5. Application Architecture

The project follows a strict **Model-View-Controller (MVC)** pattern, extended with a **Service layer** to encapsulate business logic.

*   **`app/Controllers`:** Orchestrate the web request-response cycle. They receive input, call the appropriate services, and pass data to views.
*   **`app/Models`:** Handle all database interactions, representing the data layer.
*   **`app/Entities`:** Object-oriented representations of database table rows.
*   **`app/Views`:** Handle all presentation logic. They receive data from controllers and render the HTML.
*   **`app/Libraries` (Services):** Contain the core business logic. This includes interacting with third-party APIs (Paystack, Gemini), processing data, and performing complex calculations. This keeps controllers lean.
*   **`app/Commands`:** Hold custom command-line tasks executable via `php spark`. This is used for tasks like training AI models, which are separate from the web request lifecycle.
*   **`app/Config`:** Centralizes all application configuration.
*   **`app/Filters`:** Middleware classes used for protecting routes (e.g., ensuring a user is authenticated).
*   **`app/Database`:** Contains database migrations and seeders for schema management.

## 6. Directory Structure

```
└── nehemiaobati-codeigniter/
    ├── README.md
    ├── documentation.md
    ├── setup.sh
    ├── app/
    │   ├── Commands/
    │   ├── Config/
    │   ├── Controllers/
    │   ├── Database/
    │   ├── Entities/
    │   ├── Filters/
    │   ├── Helpers/
    │   ├── Language/
    │   ├── Libraries/ (Services)
    │   ├── Models/
    │   └── Views/
    ├── public/ (Web Server Root)
    └── writable/
```

## 7. Key Features and Modules

*   **Authentication:** Secure registration, login, and password reset flows with email verification and reCAPTCHA protection.
*   **User Dashboard:** A personalized dashboard for users to view their balance and transaction history.
*   **Admin Panel:** Provides user management, financial oversight, log viewing, and a system for sending email campaigns to all registered users.
*   **Payment System:** Securely processes payments via Paystack, with a robust system for initiating and verifying transactions.
*   **Crypto Data Service:** Leverages third-party APIs to provide real-time BTC and LTC balance and transaction data.
*   **Gemini AI Studio:**
    *   **Rich Text & Multimedia Prompting:** Allows users to input text and upload various media types (images, audio, PDF) for analysis.
    *   **Conversational Memory (`MemoryService`):** A sophisticated hybrid search system that combines vector (semantic) and keyword (lexical) search to provide relevant context from past conversations to the AI.
    *   **Advanced Keyword Extraction (`TokenService`):** Processes user input through an NLP pipeline that tokenizes, removes stop words, and stems words to identify key concepts.
*   **CLI Commands (`php spark train`):** An offline command for building and saving text classification models, ensuring that performance-intensive training does not impact the live web application.

## 8. Production Deployment & Optimization

For optimal performance in a production environment, the following commands are crucial and are included in the `setup.sh` script.

*   `composer install --no-dev --optimize-autoloader`: This installs only production dependencies and creates an optimized classmap, significantly speeding up class loading.
*   `php spark optimize`: This CodeIgniter-specific command caches the final configuration and file locations, reducing the framework's workload on every request.

A typical manual deployment script should execute these commands after updating the code:
```bash
# 1. Install PHP dependencies with an optimized autoloader
composer install --no-dev --optimize-autoloader

# 2. Run any pending database migrations
php spark migrate

# 3. Optimize CodeIgniter's framework caches
php spark optimize
```

## 9. Security Best Practices

*   **`.env` File:** Ensure the `.env` file is never committed to your Git repository. Add it to `.gitignore`.
*   **Web Server Root:** The server's document root MUST point to the `/public` directory to prevent direct web access to application files.
*   **Server Updates:** Regularly update your server and its packages to patch security vulnerabilities.
*   **Firewall:** Configure a firewall (e.g., `ufw`) to restrict access to only necessary ports (80 for HTTP, 443 for HTTPS, 22 for SSH).
*   **Database Security:** Use strong, unique passwords for database users and limit their privileges.

## 10. Contributing

Contributions are welcome. For project-specific contributions, please open an issue to discuss the proposed change or submit a pull request. For contributions to the framework itself, refer to the official CodeIgniter documentation.

## 11. License

This project is open-source software licensed under the [MIT license](LICENSE).