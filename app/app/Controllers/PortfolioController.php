<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

helper('form');

class PortfolioController extends BaseController
{
    /**
     * Display the portfolio homepage.
     */
    public function index(): string
    {
        $data = [
            'pageTitle'       => 'Nehemia Obati | Software Developer Portfolio',
            'metaDescription' => 'The professional portfolio of Nehemia Obati, a full-stack software developer specializing in PHP (CodeIgniter), Python, and cloud solutions for clients in Kenya and beyond.',
            'canonicalUrl'    => url_to('portfolio.index'),
        ];

        return view('portfolio/portfolio_view', $data);
    }

    /**
     * Handle the contact form submission.
     */
    public function sendEmail(): RedirectResponse
    {
        // 1. Validate Form Input
        if (! $this->validate($this->_getValidationRules())) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        // 2. Verify reCAPTCHA
        $recaptchaResponse = $this->request->getPost('g-recaptcha-response');
        if (! $this->_verifyRecaptcha($recaptchaResponse)) {
            return redirect()->back()->withInput()->with('error', 'Please complete the reCAPTCHA.');
        }

        // 3. Prepare Email Data
        $emailData = [
            'name'    => $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS),
            'email'   => $this->request->getPost('email', FILTER_SANITIZE_EMAIL),
            'subject' => $this->request->getPost('subject', FILTER_SANITIZE_SPECIAL_CHARS),
            'message' => $this->request->getPost('message', FILTER_SANITIZE_SPECIAL_CHARS),
        ];

        // 4. Send Email
        if ($this->_sendPortfolioEmail($emailData)) {
            return redirect()->back()->with('success', 'Your message has been sent successfully!');
        }

        return redirect()->back()->with('error', 'Failed to send your message. Please try again later.');
    }

    // --- Helper Methods ---

    /**
     * Define validation rules for the contact form.
     */
    private function _getValidationRules(): array
    {
        return [
            'name'    => 'required|min_length[3]',
            'email'   => 'required|valid_email',
            'subject' => 'required|min_length[5]',
            'message' => 'required|min_length[10]',
        ];
    }

    /**
     * Verify the reCAPTCHA response using the service.
     *
     * @param string|null $response The reCAPTCHA response token.
     */
    private function _verifyRecaptcha(?string $response): bool
    {
        return service('recaptchaService')->verify($response);
    }

    /**
     * Configure and send the email.
     *
     * @param array $data Sanitized form data.
     */
    private function _sendPortfolioEmail(array $data): bool
    {
        $emailService = service('email');

        // Config values
        $fromEmail = config('Email')->fromEmail;
        $fromName  = config('Email')->fromName;

        $emailService->setFrom($fromEmail, $fromName);
        $emailService->setTo('nehemiahobati@gmail.com'); // Target recipient
        $emailService->setReplyTo($data['email']);        // User's email
        $emailService->setSubject($data['subject']);
        $emailService->setMessage($this->_buildEmailBody($data));
        $emailService->setMailType('html');

        if ($emailService->send()) {
            return true;
        }

        // Log failure details
        $debuggerData = $emailService->printDebugger(['headers']);
        log_message('error', '[PortfolioController] Email sending failed: ' . print_r($debuggerData, true));

        return false;
    }

    /**
     * Construct the HTML email body.
     *
     * @param array $data Sanitized form data.
     */
    private function _buildEmailBody(array $data): string
    {
        return '
        <html>
        <body>
            <p><strong>Name:</strong> ' . esc($data['name']) . '</p>
            <p><strong>Email:</strong> ' . esc($data['email']) . '</p>
            <p><strong>Subject:</strong> ' . esc($data['subject']) . '</p>
            <p><strong>Message:</strong></p>
            <p>' . nl2br(esc($data['message'])) . '</p>
        </body>
        </html>';
    }
}
