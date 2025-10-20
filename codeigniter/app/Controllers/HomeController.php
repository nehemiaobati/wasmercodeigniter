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
            'pageTitle' => 'Dashboard | ' . session()->get('username'),
            'metaDescription' => 'Your Afrikenkid dashboard. Check your account balance, manage your details, and access our AI and Crypto services.',
            'username'  => session()->get('username'),
            'email'     => session()->get('userEmail'),
            'member_since' => $user->created_at ?? null,
            'balance'   => $balance,
            'canonicalUrl' => url_to('home'), // Corrected route name
        ];
        return view('home/welcome_user', $data);
    }

    public function landing(): string
    {
        $data = [
            'pageTitle' => 'Afrikenkid | Generative AI & Real-Time Crypto Data',
            'metaDescription' => 'Afrikenkid provides innovative solutions for generative AI and real-time cryptocurrency data. Access AI-powered insights, query BTC/LTC data, and utilize secure payment options like Mobile Money (Safaricom, Airtel) and Credit Cards. Built for Kenya, Africa, and the global digital economy.',
            'heroTitle' => 'Build Your Dreams with Us',
            'heroSubtitle' => 'Providing innovative solutions for real-time data access and AI-powered insights to help you succeed in the digital world.',
            'canonicalUrl' => url_to('welcome'),
        ];
        return view('home/landing_page', $data);
    }

    public function terms(): string
    {
        $data = [
            'pageTitle' => 'Terms of Service | Afrikenkid',
            'metaDescription' => 'Read the official Terms of Service for using the Afrikenkid platform, its AI tools, and cryptocurrency data services.',
            'canonicalUrl' => url_to('terms'),
        ];
        return view('home/terms', $data);
    }

    public function privacy(): string
    {
        $data = [
            'pageTitle' => 'Privacy Policy | Afrikenkid',
            'metaDescription' => 'Our Privacy Policy outlines how we collect, use, and protect your personal data when you use Afrikenkid\'s services.',
            'canonicalUrl' => url_to('privacy'),
        ];
        return view('home/privacy', $data);
    }
}
