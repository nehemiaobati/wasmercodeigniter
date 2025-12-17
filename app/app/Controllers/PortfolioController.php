<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

helper('form');

class PortfolioController extends BaseController
{
    public function index(): string
    {
        $data = [
            'pageTitle' => 'Nehemia Obati | Software Developer Portfolio',
            'metaDescription' => 'The professional portfolio of Nehemia Obati, a full-stack software developer specializing in PHP (CodeIgniter), Python, and cloud solutions for clients in Kenya and beyond.',
            'canonicalUrl' => url_to('portfolio.index'), // Added this line
        ];
        return view('portfolio/portfolio_view', $data);
    }

    public function sendEmail(): RedirectResponse
    {
        $rules = [
            'name'    => 'required|min_length[3]',
            'email'   => 'required|valid_email',
            'subject' => 'required|min_length[5]',
            'message' => 'required|min_length[10]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        // Get reCAPTCHA response from the form submission.
        $recaptchaResponse = $this->request->getPost('g-recaptcha-response');

        // Verify the reCAPTCHA response.
        if (! service('recaptchaService')->verify($recaptchaResponse)) {
            return redirect()->back()->withInput()->with('error', 'Please complete the reCAPTCHA.');
        }

        $name    = $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS);
        $email   = $this->request->getPost('email', FILTER_SANITIZE_EMAIL);
        $subject = $this->request->getPost('subject', FILTER_SANITIZE_SPECIAL_CHARS);
        $message = $this->request->getPost('message', FILTER_SANITIZE_SPECIAL_CHARS);

        $emailService = service('email');

        // Use config/env values
        $fromEmail = config('Email')->fromEmail;
        $fromName  = config('Email')->fromName;

        $emailService->setFrom($fromEmail, $fromName);
        $emailService->setTo('nehemiahobati@gmail.com');
        $emailService->setReplyTo($email); // User's email
        $emailService->setSubject($subject);
        // --- FIX START: Construct an HTML email body ---
        // We build an HTML string for proper formatting and escape all user input
        // to prevent XSS attacks.
        $emailContent = '
        <html>
        <body>
            <p><strong>Name:</strong> ' . esc($name) . '</p>
            <p><strong>Email:</strong> ' . esc($email) . '</p>
            <p><strong>Subject:</strong> ' . esc($subject) . '</p>
            <p><strong>Message:</strong></p>
            <p>' . nl2br(esc($message)) . '</p>
        </body>
        </html>';

        $emailService->setMessage($emailContent);
        // Ensure the email client renders this as HTML
        $emailService->setMailType('html');
        // --- FIX END ---

        if ($emailService->send()) {
            return redirect()->back()->with('success', 'Your message has been sent successfully!');
        }

        $debuggerData = $emailService->printDebugger(['headers']);
        log_message('error', '[PortfolioController] Email sending failed: ' . print_r($debuggerData, true));
        log_message('error', '[PortfolioController] SMTP Host: ' . config('Email')->SMTPHost);

        return redirect()->back()->with('error', 'Failed to send your message. Please try again later.');
    }
}
