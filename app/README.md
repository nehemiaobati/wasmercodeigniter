# CodeIgniter 4 Web Platform

## Project Overview

This project is a comprehensive, multi-functional application built on the CodeIgniter 4 framework. It serves as a portal for registered users to access a suite of powerful digital services, including AI-driven tools and cryptocurrency data analysis.

For complete setup instructions, architectural details, and technical specifications, please refer to the **[Comprehensive Documentation](documentation.md)**.

## Key Features

*   **User Authentication:** Secure registration, login, and account management.
*   **Payment Gateway Integration:** Seamless payments via Paystack (M-Pesa, Airtel, Card).
*   **AI Service Integration:** Advanced text and multimedia interaction with Google's Gemini API, featuring conversational memory.
*   **Cryptocurrency Data Service:** Real-time balance and transaction queries for Bitcoin (BTC) and Litecoin (LTC) addresses.
*   **Administrative Dashboard:** Robust tools for user management, financial oversight, and email campaigns.

## Quick Start

An automated setup script (`setup.sh`) is provided to configure the entire server environment on Ubuntu.

```bash
# Make the script executable
chmod +x setup.sh

# Run with sudo to install and configure all dependencies
sudo ./setup.sh
```

After the script completes, you must edit the `.env` file to add your API keys and other sensitive credentials. For detailed manual setup and configuration, see the [full documentation](documentation.md).

## License

This project is open-source software licensed under the [MIT license](LICENSE).



