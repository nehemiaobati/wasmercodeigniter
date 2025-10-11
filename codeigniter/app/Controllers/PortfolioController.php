<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

helper('form');

class PortfolioController extends BaseController
{
    public function index(): string
    {
        return view('portfolio/portfolio_view'); // View name updated
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

        $name    = $this->request->getPost('name');
        $email   = $this->request->getPost('email');
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');

        $emailService = service('email');

        $emailService->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $emailService->setTo('nehemiahobati@gmail.com');
        $emailService->setSubject($subject);
        $emailService->setMessage("Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}");

        if ($emailService->send()) {
            return redirect()->back()->with('success', 'Your message has been sent successfully!');
        }
        
        $data = $emailService->printDebugger(['headers']);
        log_message('error', 'Portfolio email sending failed: ' . print_r($data, true));
        return redirect()->back()->with('error', 'Failed to send your message. Please try again later.');
    }
}
