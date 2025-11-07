### 1. Project Overview

The Web Platform is a comprehensive, multi-functional application built on the CodeIgniter 4 framework. It serves as a portal for registered users to access a suite of powerful digital services. The platform is designed with a modular architecture, featuring a robust user authentication system, an account management dashboard with an integrated balance and payment system, and an administrative panel for user oversight.

The core services offered include:
*   **Gemini AI Studio:** An advanced interface allowing users to interact with Google's Gemini AI, featuring text and multimedia prompts, conversational memory, and context-aware responses.
*   **Cryptocurrency Data Service:** A tool for querying real-time balance and transaction data for Bitcoin (BTC) and Litecoin (LTC) addresses.

The application integrates with several third-party APIs, including Paystack for secure payments, Google reCAPTCHA for spam prevention, and the respective blockchain and AI service endpoints.

### 2. Core Technologies

*   **Backend:** PHP 8.1+, CodeIgniter 4
*   **Frontend:** Bootstrap 5, JavaScript, HTML5, CSS3
*   **Database:** MySQL (via MySQLi driver)
*   **Key Libraries:** Parsedown (for Markdown rendering), TinyMCE (for rich text editing), Kint (for debugging), **NlpTools (for Natural Language Processing)**
*   **Development Tooling:** Composer, PHPUnit, **Spark CLI**

### 3. Installation and Setup

1.  **Prerequisites:** Ensure you have PHP 8.1+, Composer, and a MySQL database server installed.
2.  **Clone Repository:** Clone the project to your local machine.
3.  **Install Dependencies:** Run `composer install` to download the required PHP packages.
4.  **Configure Environment:**
    *   Copy the `env` file to a new file named `.env`.
    *   Open `.env` and configure all `app.*`, `database.*`, `email.*`, and API key variables.
5.  **Install Frontend Assets:** Download TinyMCE and place it in the `public/assets/tinymce/` directory.
6.  **Database Migration:** Run the migrations to create the necessary database tables:
    ```bash
    php spark migrate
    ```
7.  **(Optional) Train Classification Models:** For future use, you can train the intent classification models. Add a `training_data.csv` file to `writable/training/` and run the command:
    ```bash
    php spark train
    ```
8.  **Run the Application:** Start the development server:
    ```bash
    php spark serve
    ```

### 4. Application Architecture

The project strictly follows the **Model-View-Controller (MVC)** architectural pattern, extended with a **Service layer**.

*   **`app/Controllers`:** Controllers orchestrate the web request-response cycle.
*   **`app/Commands` (New):** This directory holds all custom command-line tasks. These commands are built to be executed with `php spark` and are the standard way to handle background or administrative tasks (like training models). They are completely separate from the web-facing controllers.
*   **`app/Models`:** Models handle all database interactions.
*   **`app/Entities`:** Entities are object-oriented representations of database table rows.
*   **`app/Views`:** Views handle all presentation logic.
*   **`app/Libraries` (Services):** This directory contains the core business logic, including the new `TrainingService` and `TokenService`.
*   **`app/Config`:** Centralizes all application configuration.
*   **`app/Filters`:** Middleware classes for protecting routes.
*   **`app/Database`:** Contains all database migrations.

### 5. Database Schema

The database schema includes tables for `users`, `payments`, `prompts`, `interactions`, `entities`, `user_settings`, and `campaigns`.

### 6. Key Features and Modules

*   **Authentication:** Secure registration, login, and password reset flows.
*   **User Dashboard:** Personalized dashboard and transaction history.
*   **Admin Panel:** User management, financial oversight, and email campaigns.
*   **Payment System:** Secure payment processing via Paystack.
*   **Crypto Data Service:** Real-time BTC and LTC data queries.
*   **Gemini AI Studio:**
    *   **Rich Text & Multimedia Prompting.**
    *   **Conversational Memory (`MemoryService`):** A sophisticated hybrid search system that combines vector and keyword search to provide context to the AI.
    *   **Advanced Keyword Extraction (`TokenService`):** User input is processed through a robust NLP pipeline that strips HTML tags, tokenizes, removes stop words, and stems words to their root form.
*   **Offline Model Training (`TrainingService` / `Train` Command):** The application now includes a dedicated service and a `php spark train` command to build and save text classification models for future integration. This keeps the performance-intensive training process separate from the live web application and follows CodeIgniter's best practices for CLI tasks.

### 7. Documentation and Best Practices (`clinerules.md`)

The project is governed by a strict set of internal rules documented in `.clinerules.md`, which ensures a high standard of code quality, security, and maintainability.

### 8. Production Deployment & Optimization

For optimal performance in a production environment, a specific deployment workflow is required. This involves optimizing both the PHP autoloader and the CodeIgniter framework's internal caches.

*   **`composer install --no-dev --optimize-autoloader`**: This command should be run during deployment. It tells Composer to skip development-only packages and, more importantly, to create a highly optimized "classmap." This allows PHP to find class files with a direct array lookup instead of slower filesystem checks, significantly speeding up application boot time.

*   **`php spark optimize`**: This is a CodeIgniter-specific command that should be run after your code is deployed and Composer has finished. It performs several key framework optimizations:
    *   **Config Caching:** It reads all configuration files and your `.env` file once, then caches the final, merged configuration object. This saves the framework from having to do this work on every single request.
    *   **File Locator Caching:** It scans your application for controllers, views, models, and other files and caches their locations. This is the cache that needs to be cleared (`php spark cache:clear`) during development when you add a new class file so that `spark` can discover it. The `optimize` command handles this automatically for production.

A typical production deployment script should execute these commands in order after the new code has been pulled or uploaded:
```bash
# 1. Install PHP dependencies with an optimized autoloader
composer install --no-dev --optimize-autoloader

# 2. Run any pending database migrations
php spark migrate

# 3. Optimize CodeIgniter's framework caches
php spark optimize