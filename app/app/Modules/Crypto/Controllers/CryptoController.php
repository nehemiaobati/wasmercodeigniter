<?php declare(strict_types=1);

namespace App\Modules\Crypto\Controllers; // Updated namespace

use App\Controllers\BaseController; // Keep BaseController from core
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use App\Modules\Crypto\Libraries\CryptoService; 

class CryptoController extends BaseController
{
    /**
     * Fixed cost for crypto queries in USD.
     * @var float
     */
    private const CRYPTO_QUERY_COST_USD = 0.01;

    /**
     * USD to KSH conversion rate.
     * @var int
     */
    private const USD_TO_KSH_RATE = 129;

    /**
     * Minimum required balance to attempt a query in KSH.
     * @var float
     */
    private const MINIMUM_BALANCE_KSH = 0.01;

    protected CryptoService $cryptoService;
    protected UserModel $userModel;

    /**
     * Displays the public-facing landing page for the CryptoQuery tool.
     *
     * @return string The rendered view.
     */
    public function publicPage(): string
    {
        $data = [
            'pageTitle'       => 'Crypto Wallet Insights & Analytics | BTC & LTC Tracker',
            'metaDescription' => 'Track Bitcoin (BTC) and Litecoin (LTC) wallet activities instantly. Audit balances and visualize transaction histories with our professional blockchain analytics solution.',
            'canonicalUrl'    => url_to('crypto.public'),
        ];

        // Updated view path to reflect module structure
        return view('App\Modules\Crypto\Views\crypto\public_page', $data);
    }

    /**
     * Constructor.
     * Initializes the CryptoService and UserModel.
     */
    public function __construct()
    {
        $this->cryptoService = service('cryptoService');
        $this->userModel = new UserModel();
    }

    /**
     * Displays the crypto query form.
     *
     * @return string The rendered view.
     */
    public function index(): string
    {
        $data = [
            'pageTitle'       => 'Crypto Analytics Dashboard | Afrikenkid',
            'metaDescription' => 'Access deep on-chain data for Bitcoin and Litecoin. Monitor transaction flows and audit wallet balances in real-time.',
            'canonicalUrl'    => url_to('crypto.index'),
            'result'          => session()->getFlashdata('result'),
            'errors'          => session()->getFlashdata('errors')
        ];
        // Add noindex directive for authenticated pages
        $data['robotsTag'] = 'noindex, follow';
        // Updated view path to reflect module structure
        return view('App\Modules\Crypto\Views\crypto\query_form', $data);
    }

    /**
     * Processes a crypto query, including a balance check and deduction within a transaction.
     *
     * @return RedirectResponse
     */
    public function query(): RedirectResponse
    {
        $rules = [
            'asset' => 'required|in_list[btc,ltc]',
            'query_type' => 'required|in_list[balance,tx]',
            'address' => 'required|min_length[26]|max_length[55]',
            'limit' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $userId = (int) session()->get('userId');
        if ($userId <= 0) {
            return redirect()->back()->withInput()->with('error', 'User not logged in or invalid user ID.');
        }

        $asset = $this->request->getPost('asset');
        $queryType = $this->request->getPost('query_type');
        $address = $this->request->getPost('address');
        $limit = $this->request->getPost('limit');
        
        $result = [];
        $errors = [];

        // --- Balance Check ---
        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'User not found.');
        }

        $costInKSH = (self::CRYPTO_QUERY_COST_USD * self::USD_TO_KSH_RATE);
        $deductionAmount = max(self::MINIMUM_BALANCE_KSH, ceil($costInKSH * 100) / 100);

        if (bccomp((string) $user->balance, (string) $deductionAmount, 2) < 0) {
            $error = "Insufficient balance. This query costs approx. KSH " . number_format($deductionAmount, 2) .
                     ", but you only have KSH " . $user->balance . ".";
            return redirect()->back()->withInput()->with('error', $error);
        }
        
        try {
            // --- Execute Query ---
            if ($asset === 'btc') {
                $result = ($queryType === 'balance') 
                    ? $this->cryptoService->getBtcBalance($address) 
                    : $this->cryptoService->getBtcTransactions($address, $limit);
            } elseif ($asset === 'ltc') {
                $result = ($queryType === 'balance') 
                    ? $this->cryptoService->getLtcBalance($address) 
                    : $this->cryptoService->getLtcTransactions($address, $limit);
            }

            if (isset($result['error'])) {
                $errors[] = $result['error'];
            }

        } catch (\Exception $e) {
            $errors[] = 'An error occurred while fetching crypto data: ' . $e->getMessage();
            log_message('error', 'Crypto query API error: ' . $e->getMessage());
        }

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('error', $errors);
        }
        
        // --- Deduct Cost within a Transaction ---
        $db = \Config\Database::connect();
        $db->transStart();

        $deductionSuccess = $this->userModel->deductBalance($userId, (string)$deductionAmount);
        
        $db->transComplete();

        if ($db->transStatus() === false || !$deductionSuccess) {
            log_message('critical', "Transaction failed while deducting crypto query cost for user ID: {$userId}");
            // The user got the data but we failed to charge them. Log this critical error.
            return redirect()->back()->withInput()
                ->with('result', $result)
                ->with('error', 'Query successful, but a billing error occurred. Please contact support.');
        }

        $costMessage = "KSH " . number_format($deductionAmount, 2) . " deducted for your query.";
        return redirect()->back()->withInput()
            ->with('result', $result)
            ->with('success', $costMessage);
    }
}
