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
            'metaDescription' => 'Your dashboard. Check your account balance, manage your details, and access our AI and Crypto services.',
            'username'  => session()->get('username'),
            'email'     => session()->get('userEmail'),
            'member_since' => $user->created_at ?? null,
            'balance'   => $balance,
            'canonicalUrl' => url_to('home'), // Corrected route name
        ];
        // Add noindex directive for authenticated pages
        $data['robotsTag'] = 'noindex, follow';
        return view('home/welcome_user', $data);
    }

    public function landing(): string
    {
        $data = [
            'pageTitle'       => 'Afrikenkid | AI Tools & Crypto Data for Kenya & Africa',
            'metaDescription' => 'Unlock the power of Generative AI and access real-time Bitcoin & Litecoin data. offers pay-as-you-go tools for developers and creators in Kenya, with easy M-Pesa payments.',
            'heroTitle'       => 'The Developer\'s Toolkit for AI & Crypto',
            'heroSubtitle'    => 'Instantly query blockchain data and leverage Google\'s Gemini AI with simple, pay-as-you-go pricing. Built for creators and developers in Africa.',
            'canonicalUrl'    => url_to('landing'),
        ];
        return view('home/landing_page', $data);
    }

    public function terms(): string
    {
        $data = [
            'pageTitle' => 'Terms of Service | Afrikenkid',
            'metaDescription' => 'Read the official Terms of Service for using the platform, its AI tools, and cryptocurrency data services.',
            'canonicalUrl' => url_to('terms'),
        ];
        return view('home/terms', $data);
    }

    public function privacy(): string
    {
        $data = [
            'pageTitle' => 'Privacy Policy | Afrikenkid',
            'metaDescription' => 'Our Privacy Policy outlines how we collect, use, and protect your personal data when you use services.',
            'canonicalUrl' => url_to('privacy'),
        ];
        return view('home/privacy', $data);
    }
}
