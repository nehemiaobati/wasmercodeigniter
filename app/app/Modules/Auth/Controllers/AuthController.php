<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Auth\Libraries\AuthService;

/**
 * Handles user authentication processes.
 */
class AuthController extends BaseController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
        helper(['form']);
    }

    public function register(): string|ResponseInterface
    {
        if ($this->session->has('isLoggedIn')) {
            return redirect()->to(url_to('home'));
        }
        $data = [
            'pageTitle'       => 'Create Your Account | Afrikenkid',
            'metaDescription' => 'Sign up for a free account. Make your first deposit to get KSH 30 in starter credits.',
            'canonicalUrl'    => url_to('register'),
            'robotsTag'       => 'noindex, follow',
        ];

        return view('App\Modules\Auth\Views\register', $data);
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]|max_length[255]',
            'confirmpassword' => 'matches[password]',
            'terms' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // reCAPTCHA Check
        $recaptchaService = service('recaptchaService');
        if (! $recaptchaService->verify($this->request->getPost('g-recaptcha-response'))) {
            log_message('warning', "[AuthController] reCAPTCHA verification failed for registration.");
            return redirect()->back()->withInput()->with('error', 'reCAPTCHA verification failed. Please try again.');
        }

        // Delegate to Service
        $result = $this->authService->register($this->request->getPost());

        if ($result['success']) {
            return redirect()->to(url_to('login'))->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function login(): string|ResponseInterface
    {
        if ($this->session->has('isLoggedIn')) {
            return redirect()->to(url_to('home'));
        }
        $data = [
            'pageTitle'       => 'Login | Afrikenkid',
            'metaDescription' => 'Access your dashboard.',
            'canonicalUrl'    => url_to('login'),
            'robotsTag'       => 'noindex, follow',
        ];
        return view('App\Modules\Auth\Views\login', $data);
    }

    public function authenticate(): ResponseInterface
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // reCAPTCHA Check
        $recaptchaService = service('recaptchaService');
        if (! $recaptchaService->verify($this->request->getPost('g-recaptcha-response'))) {
            return redirect()->back()->withInput()->with('error', 'reCAPTCHA verification failed.');
        }

        // Delegate to Service
        $result = $this->authService->authenticate(
            $this->request->getVar('email'),
            $this->request->getVar('password')
        );

        if (! $result['success']) {
            log_message('warning', "[AuthController] Failed login: " . $result['message']);
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        // Set Session (Controller Responsibility for HTTP State)
        $user = $result['user'];
        $this->session->set([
            'isLoggedIn' => true,
            'userId'     => $user->id,
            'userEmail'  => $user->email,
            'username'   => $user->username,
            'is_admin'   => $user->is_admin,
            'member_since' => $user->created_at,
        ]);
        $this->session->regenerate();

        // Redirect Logic
        if ($this->session->has('redirect_url')) {
            $redirectUrl = $this->session->get('redirect_url');
            $this->session->remove('redirect_url');
            return redirect()->to($redirectUrl)->with('success', 'Welcome back!');
        }

        return redirect()->to(url_to('home'))->with('success', 'Login Successful');
    }

    public function logout(): ResponseInterface
    {
        $this->session->destroy();
        return redirect()->to(url_to('login'))->with('success', 'Logged out successfully.');
    }

    public function verifyEmail(string $token): ResponseInterface
    {
        if ($this->authService->verifyEmail($token)) {
            return redirect()->to(url_to('login'))->with('success', 'Email verified successfully. You can now log in.');
        }

        return redirect()->to(url_to('register'))->with('error', 'Invalid verification token.');
    }

    public function forgotPasswordForm(): string
    {
        $data = [
            'pageTitle' => 'Forgot Password | Afrikenkid',
            'metaDescription' => 'Reset your account password.',
            'canonicalUrl' => url_to('auth.forgot_password'),
            'robotsTag'    => 'noindex, follow',
        ];
        return view('App\Modules\Auth\Views\forgot_password', $data);
    }

    public function sendResetLink(): ResponseInterface
    {
        $rules = ['email' => 'required|valid_email'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->authService->sendResetLink($this->request->getVar('email'));

        if ($result['success']) {
            return redirect()->to(url_to('auth.forgot_password'))->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function resetPasswordForm(string $token): string|ResponseInterface
    {
        if (! $this->authService->validateResetToken($token)) {
            return redirect()->to(url_to('auth.forgot_password'))->with('error', 'Invalid or expired password reset token.');
        }

        $data = [
            'pageTitle'       => 'Reset Your Password | Afrikenkid',
            'metaDescription' => 'Choose a new, secure password.',
            'canonicalUrl'    => current_url(),
            'robotsTag'       => 'noindex, follow',
            'token'           => $token,
        ];
        return view('App\Modules\Auth\Views\reset_password', $data);
    }

    public function updatePassword(): ResponseInterface
    {
        $rules = [
            'token' => 'required',
            'password' => 'required|min_length[8]',
            'confirmpassword' => 'matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->authService->resetPassword($this->request->getVar('token'), $this->request->getVar('password'))) {
            return redirect()->to(url_to('login'))->with('success', 'Your password has been successfully updated. You can now log in.');
        }

        return redirect()->to(url_to('auth.forgot_password'))->with('error', 'Invalid or expired password reset token.');
    }
}
