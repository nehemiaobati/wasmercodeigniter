# CodeIgniter 4 Project Setup and Usage

## Project Overview

This project is a CodeIgniter 4 application designed to [**briefly describe the project's purpose here - e.g., manage user accounts, process payments, interact with AI services**]. It leverages the power and flexibility of the CodeIgniter framework to provide a robust and secure web application.

## Key Features

*   [**Feature 1: e.g., User Authentication**]
*   [**Feature 2: e.g., Payment Gateway Integration (Paystack)**]
*   [**Feature 3: e.g., AI Service Integration (Gemini API)**]
*   [**Feature 4: e.g., Secure Data Handling**]

## Prerequisites

Before you begin, ensure you have the following installed on your Ubuntu server:

*   **Ubuntu Server:** This script is optimized for Ubuntu.
*   **Sudo Access:** You will need root privileges to run the setup script.
*   **PHP:** Version 8.1 or higher is required. The script installs PHP 8.2 with necessary extensions.
*   **MySQL Server:** Required for database storage.
*   **Composer:** For managing PHP dependencies.
*   **Node.js & NPM:** For frontend asset management (if applicable).
*   **Git:** For cloning the project repository.

## Automated Setup Script (`setup.sh`)

The `setup.sh` script automates the entire installation and configuration process. It performs the following actions:

1.  **System Update:** Updates package lists and upgrades existing packages.
2.  **Essential Utilities:** Installs `openssl`, `unzip`, `git`, `sudo`, `nano`, and `perl`.
3.  **Apache Installation:** Installs the Apache2 web server.
4.  **PHP Installation:** Installs PHP 8.2 and essential extensions (`mysql`, `intl`, `mbstring`, `bcmath`, `curl`, `xml`, `zip`, `gd`).
5.  **MySQL Setup:** Installs MySQL server, creates a secure database (`${DB_NAME}`) and user (`${DB_USER}`), and grants necessary privileges.
6.  **Composer Installation:** Installs the latest version of Composer globally.
7.  **Node.js Installation:** Installs Node.js (version 20.x) and NPM.
8.  **Project Cloning:** Clones the project repository from `${GIT_REPO_URL}` into `${PROJECT_PATH}`.
9.  **Project Configuration:**
    *   Installs PHP dependencies using Composer.
    *   Creates and configures the `.env` file with generated database credentials and encryption key.
    *   Runs database migrations using `php spark migrate`.
    *   Sets appropriate file permissions for the `writable` directory.
10. **Apache Virtual Host Configuration:** Sets up a virtual host for Apache to serve the project from the `public` directory and enables the `mod_rewrite` module.
11. **Final Summary:** Displays a summary of the installation, including database credentials, installed versions, and next steps.

## Running the Setup Script

1.  **Save the script:** Save the content of this `setup.sh` file to your server.
2.  **Make it executable:** `chmod +x setup.sh`
3.  **Run with sudo:** `sudo ./setup.sh`

## Project Configuration (`.env` file)

After the script completes, you will need to configure the `.env` file located at `${PROJECT_PATH}/.env` with your specific details:

*   **`CI_ENVIRONMENT`**: Set to `production` for live environments.
*   **`app.baseURL`**: The base URL of your application (e.g., `http://yourdomain.com`).
*   **`database.default.*`**: The script generates these, but you can modify them if needed.
*   **`encryption.key`**: The script generates this for encryption purposes.
*   **`PAYSTACK_SECRET_KEY`**: Your Paystack secret key.
*   **`GEMINI_API_KEY`**: Your Google Gemini API key.
*   **`recaptcha_siteKey` & `recaptcha_secretKey`**: Your reCAPTCHA site and secret keys.
*   **`email.*`**: Your email server configuration (host, port, credentials, etc.).

**IMPORTANT:** Never commit your `.env` file to version control, as it contains sensitive credentials.

## Post-Installation Steps

1.  **Edit `.env`:** As mentioned above, configure your API keys and email settings in the `.env` file.
2.  **Domain Configuration:** Point your domain's DNS 'A' record to this server's IP address.
3.  **HTTPS Setup (Recommended):** For enhanced security, install Certbot to enable HTTPS:
    ```bash
    sudo apt install certbot python3-certbot-apache
    sudo certbot --apache
    ```

## Security Best Practices

*   **`.env` File:** Ensure the `.env` file is never committed to your Git repository. Add it to your `.gitignore` file.
*   **Server Updates:** Regularly update your server and its packages to patch security vulnerabilities.
*   **Firewall:** Configure a firewall (e.g., `ufw`) to restrict access to only necessary ports.
*   **Database Security:** Use strong, unique passwords for your database users and limit their privileges.

## Contributing

Contributions are welcome! Please refer to the CodeIgniter documentation for guidelines on contributing to the framework itself. For project-specific contributions, please open an issue or submit a pull request.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
