<?php declare(strict_types=1);

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

        $name    = $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS);
        $email   = $this->request->getPost('email', FILTER_SANITIZE_EMAIL);
        $subject = $this->request->getPost('subject', FILTER_SANITIZE_SPECIAL_CHARS);
        $message = $this->request->getPost('message', FILTER_SANITIZE_SPECIAL_CHARS);

        $emailService = service('email');

        $emailService->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $emailService->setTo('nehemiahobati@gmail.com');
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
        
        $data = $emailService->printDebugger(['headers']);
        log_message('error', 'Portfolio email sending failed: ' . print_r($data, true));
        return redirect()->back()->with('error', 'Failed to send your message. Please try again later.');
    }
}
