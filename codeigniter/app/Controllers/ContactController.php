<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

helper('form');

class ContactController extends BaseController
{
    public function form(): string
    {
        $data = [
            'pageTitle' => 'Contact Us | Afrikenkid',
            'metaDescription' => 'Get in touch with the Afrikenkid team for support, inquiries, or custom development projects. We serve Kenya, Africa, and global clients.',
            'canonicalUrl' => url_to('contact.form'), // Added this line
        ];
        return view('contact/contact_form', $data);
    }

    public function send(): RedirectResponse
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

        // Instantiate the RecaptchaService.
        $recaptchaService = service('recaptchaService');

        // Verify the reCAPTCHA response.
        if (! $recaptchaService->verify($recaptchaResponse)) {
            // If reCAPTCHA verification fails, add a validation error and redirect back.
            $this->validator->setError('recaptcha', 'Please complete the reCAPTCHA.');
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $name    = $this->request->getPost('name');
        $email   = $this->request->getPost('email');
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');

        $emailService = service('email');

        $emailService->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $emailService->setTo('nehemiahobati@gmail.com');
        $emailService->setReplyTo('afrikenkid@gmail.com');
        $emailService->setSubject($subject);
        $emailService->setMessage("Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}");

        if ($emailService->send()) {
            session()->setFlashdata('warning', 'Your message has been sent. Please note that email delivery may experience slight delays.');
            return redirect()->back()->with('success', 'Your message has been sent successfully!');
        }
        
        $data = $emailService->printDebugger(['headers']);
        log_message('error', 'Email sending failed: ' . print_r($data, true));
        return redirect()->back()->with('error', 'Failed to send your message. Please try again later.');
    }
}
