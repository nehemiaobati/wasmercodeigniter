<?php declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;

class HomeController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): string
    {
        $userId = session()->get('userId');
        $user = null;
        $balance = '0.00';

        if ($userId) {
            $user = $this->userModel->find($userId);
            if ($user && isset($user->balance)) {
                $balance = $user->balance;
            }
        }

        $data = [
            'pageTitle' => 'Welcome, ' . session()->get('username'),
            'username'  => session()->get('username'),
            'email'     => session()->get('userEmail'),
            'member_since' => $user->created_at ?? null,
            'balance'   => $balance,
        ];
        return view('home/welcome_user', $data);
    }

    public function landing(): string
    {
        $data = [
            'pageTitle' => 'Welcome to Our Page!',
            'heroTitle' => 'Build Your Dreams with Us',
            'heroSubtitle' => 'We provide innovative solutions to help you succeed.',
        ];
        return view('home/landing_page', $data);
    }

    public function terms(): string
    {
        $data = [
            'pageTitle' => 'Terms of Service',
        ];
        return view('home/terms', $data);
    }

    public function privacy(): string
    {
        $data = [
            'pageTitle' => 'Privacy Policy',
        ];
        return view('home/privacy', $data);
    }
}
