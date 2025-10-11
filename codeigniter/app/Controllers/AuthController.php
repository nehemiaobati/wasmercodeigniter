<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;

/**
 * Handles user authentication processes, including registration, login, logout,
 * email verification, and password reset functionalities.
 */
class AuthController extends BaseController
{
    /**
     * Displays the user registration form.
     *
     * @return string The rendered registration view.
     */
    public function register(): string|ResponseInterface
    {
        helper(['form']);
        if ($this->session->has('isLoggedIn')) {
            return redirect()->to(url_to('home'));
        }
        $data = [];
        return view('auth/register', $data);
    }

    /**
     * Processes the user registration submission.
     *
     * Validates user input, creates a new user record, and sends an email verification link.
     *
     * @return ResponseInterface Redirects to the login page on success or back with errors on failure.
     */
    public function store(): ResponseInterface
    {
        helper(['form']);
        // Define validation rules for registration fields.
        $rules = [
            'username' => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]|max_length[255]',
            'confirmpassword' => 'matches[password]',
        ];

        // Validate the submitted data. If validation fails, display the registration form with errors.
        if (! $this->validate($rules)) {
            $response = $this->response;
            $response->setBody(view('auth/register', ['validation' => $this->validator]));
            return $response;
        }

        $userModel = new UserModel();
        // Generate a unique token for email verification.
        $token = bin2hex(random_bytes(50));
        $data = [
            'username' => $this->request->getVar('username'),
            'email'    => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'balance'  => 30, // Set initial balance for new users.
            'verification_token' => $token,
        ];
        // Save the new user data to the database.
        $userModel->save($data);

        // Prepare and send the email verification message.
        $emailService = service('email');
        $emailService->setTo($data['email']);
        $emailService->setReplyTo('afrikenkid@gmail.com');
        $emailService->setSubject('Email Verification');
        $verificationLink = url_to('verify_email', $token);
        $message = view('emails/verification_email', [
            'name' => $data['username'],
            'verificationLink' => $verificationLink
        ]);
        $emailService->setMessage($message);

        // If email sending is successful, redirect to login with a success message.
        if ($emailService->send()) {
            return redirect()->to(url_to('login'))->with('success', 'Registration successful. Please check your email to verify your account.');
        }

        // Log an error if email sending fails and redirect back with an error message.
        log_message('error', 'Email sending failed: ' . print_r($emailService->printDebugger(['headers']), true));
        return redirect()->back()->withInput()->with('error', 'Registration failed. Could not send verification email.');
    }

    /**
     * Displays the user login form.
     *
     * @return string The rendered login view.
     */
    public function login(): string
    {
        helper(['form']);
        $data = [];
        return view('auth/login', $data);
    }

    /**
     * Authenticates a user based on provided credentials.
     *
     * Validates login credentials and sets session variables upon successful authentication.
     * Checks for email verification status.
     *
     * @return ResponseInterface Redirects to the home page on successful login, or back with errors on failure.
     */
    public function authenticate(): ResponseInterface
    {
        helper(['form']);
        // Define validation rules for login fields.
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        // Validate the submitted login data. If validation fails, display the login form with errors.
        if (! $this->validate($rules)) {
            $response = $this->response;
            $response->setBody(view('auth/login', [
                'validation' => $this->validator,
            ]));
            return $response;
        }

        $userModel = new UserModel();
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        // Retrieve the user by email.
        $user = $userModel->where('email', $email)->first();

        // Verify the password and check if the user exists.
        if (! $user || ! password_verify($password, $user->password)) {
            return redirect()->back()->withInput()->with('error', 'Invalid login credentials.');
        }

        // Check if the user's email has been verified.
        if (! $user->is_verified) {
            return redirect()->back()->withInput()->with('error', 'Please verify your email before logging in.');
        }

        // Set session variables upon successful authentication.
        $this->session->set([
            'isLoggedIn' => true,
            'userId'     => $user->id,
            'userEmail'  => $user->email,
            'username'   => $user->username, // Store username in session.
            'is_admin'   => $user->is_admin,
            'member_since' => $user->created_at, // Store creation date as member since.
        ]);

        // Redirect to the home page with a success message.
        return redirect()->to(url_to('home'))->with('success', 'Login Successful');
    }

    /**
     * Logs out the currently authenticated user.
     *
     * Destroys the session and redirects to the login page.
     *
     * @return ResponseInterface Redirects to the login page after logout.
     */
    public function logout(): ResponseInterface
    {
        $this->session->destroy();
        return redirect()->to(url_to('login'))->with('success', 'Logged out successfully.');
    }

    /**
     * Verifies a user's email address using a provided token.
     *
     * Updates the user's verification status in the database.
     *
     * @param string $token The email verification token.
     * @return ResponseInterface Redirects to login on success or registration with an error on failure.
     */
    public function verifyEmail(string $token): ResponseInterface
    {
        $userModel = new UserModel();
        $user = $userModel->where('verification_token', $token)->first();

        if ($user) {
            $user->is_verified = true;
            $user->verification_token = null; // Clear the token after verification.
            $userModel->save($user);

            return redirect()->to(url_to('login'))->with('success', 'Email verified successfully. You can now log in.');
        }

        return redirect()->to(url_to('register'))->with('error', 'Invalid verification token.');
    }

    /**
     * Displays the form for requesting a password reset.
     *
     * @return string The rendered forgot password form view.
     */
    public function forgotPasswordForm(): string
    {
        helper(['form']);
        return view('auth/forgot_password');
    }

    /**
     * Sends a password reset link to the user's email address.
     *
     * Generates a reset token and expiration date, then sends an email with the reset link.
     *
     * @return ResponseInterface Redirects back with status messages or errors.
     */
    public function sendResetLink(): ResponseInterface
    {
        helper(['form']);
        $rules = ['email' => 'required|valid_email'];

        // Validate the email address.
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $this->request->getVar('email'))->first();

        if ($user) {
            // Generate a reset token and set its expiration time (1 hour).
            $token = bin2hex(random_bytes(50));
            $user->reset_token = $token;
            $user->reset_expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour.
            $userModel->save($user);

            // Prepare and send the password reset email.
            $emailService = service('email');
        $emailService->setTo($user->email);
        $emailService->setReplyTo('afrikenkid@gmail.com');
        $emailService->setSubject('Password Reset Request');
            $resetLink = url_to('auth.reset_password', $token);
            $message = view('emails/reset_password_email', [
                'name' => $user->username,
                'resetLink' => $resetLink
            ]);
            $emailService->setMessage($message);

            // Log an error if email sending fails.
            if (! $emailService->send()) {
                log_message('error', 'Password reset email sending failed: ' . print_r($emailService->printDebugger(['headers']), true));
                return redirect()->back()->with('error', 'Could not send password reset email. Please try again later.');
            }
        }

        // Redirect with a success message, regardless of whether a user was found, to prevent email enumeration.
        return redirect()->to(url_to('auth.forgot_password'))->with('success', 'If a matching account was found, a password reset link has been sent to your email address.');
    }

    /**
     * Displays the form to reset a user's password using a token.
     *
     * Validates the reset token and its expiration.
     *
     * @param string $token The password reset token.
     * @return string|\CodeIgniter\HTTP\ResponseInterface The rendered reset password form or a redirect with an error.
     */
    public function resetPasswordForm(string $token): string|ResponseInterface
    {
        helper(['form']);
        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $token)->first();

        // Check if the token is valid and has not expired.
        if (! $user || strtotime($user->reset_expires) < time()) {
            return redirect()->to(url_to('auth.forgot_password'))->with('error', 'Invalid or expired password reset token.');
        }

        // Render the reset password form, passing the token.
        return view('auth/reset_password', ['token' => $token]);
    }

    /**
     * Updates a user's password after a successful reset request.
     *
     * Validates the token, new password, and confirmation password.
     *
     * @return ResponseInterface Redirects to the login page on success or back with errors on failure.
     */
    public function updatePassword(): ResponseInterface
    {
        helper(['form']);
        // Define validation rules for password reset.
        $rules = [
            'token' => 'required',
            'password' => 'required|min_length[8]',
            'confirmpassword' => 'matches[password]',
        ];

        // Validate the submitted data.
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $this->request->getVar('token'))->first();

        // Re-validate token and expiration.
        if (! $user || strtotime($user->reset_expires) < time()) {
            return redirect()->to(url_to('auth.forgot_password'))->with('error', 'Invalid or expired password reset token.');
        }

        // Update the user's password and clear reset token/expiration.
        $user->password = password_hash($this->request->getVar('password'), PASSWORD_DEFAULT);
        $user->reset_token = null;
        $user->reset_expires = null;
        $userModel->save($user);

        // Redirect to login with a success message.
        return redirect()->to(url_to('login'))->with('success', 'Your password has been successfully updated. You can now log in.');
    }
}
