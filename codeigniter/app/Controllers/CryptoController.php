<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use App\Libraries\CryptoService;

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
            'title' => 'Crypto Query',
            'result' => session()->getFlashdata('result'),
            'errors' => session()->getFlashdata('errors')
        ];
        return view('crypto/query_form', $data); // View name updated
    }

    /**
     * Processes a crypto query, including a balance check before execution.
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

        $asset = $this->request->getPost('asset');
        $queryType = $this->request->getPost('query_type');
        $address = $this->request->getPost('address');
        $limit = $this->request->getPost('limit');

        $result = [];
        $errors = [];

        try {
            // --- Balance Check ---
            $userId = (int) session()->get('userId');
            if ($userId > 0) {
                $user = $this->userModel->find($userId);
                if (! $user) {
                    $errors[] = 'User not found.';
                } else {
                    $costInKSH = (self::CRYPTO_QUERY_COST_USD * self::USD_TO_KSH_RATE);
                    $deductionAmount = max(self::MINIMUM_BALANCE_KSH, ceil($costInKSH * 100) / 100);

                    if (bccomp((string) $user->balance, (string) $deductionAmount, 2) < 0) {
                        $errors[] = "Insufficient balance. This query costs approx. KSH " . number_format($deductionAmount, 2) .
                                    ", but you only have KSH " . $user->balance . ".";
                    }
                }
            } else {
                $errors[] = 'User not logged in or invalid user ID.';
                log_message('error', 'User not logged in or invalid user ID during balance check.');
            }

            if (!empty($errors)) {
                return redirect()->back()->withInput()->with('error', $errors);
            }
            // --- End Balance Check ---

            // --- Execute Query ---
            if ($asset === 'btc') {
                if ($queryType === 'balance') {
                    $result = $this->cryptoService->getBtcBalance($address);
                } else {
                    $result = $this->cryptoService->getBtcTransactions($address, $limit);
                }
            } elseif ($asset === 'ltc') {
                if ($queryType === 'balance') {
                    $result = $this->cryptoService->getLtcBalance($address);
                } else {
                    $result = $this->cryptoService->getLtcTransactions($address, $limit);
                }
            }

            if (isset($result['error'])) {
                $errors[] = $result['error'];
            }
            // --- End Execute Query ---

            // --- Deduct Cost ---
            if (empty($errors)) {
                $costInKSH = (self::CRYPTO_QUERY_COST_USD * self::USD_TO_KSH_RATE);
                $deductionAmount = max(self::MINIMUM_BALANCE_KSH, ceil($costInKSH * 100) / 100);
                $costMessage = "KSH " . number_format($deductionAmount, 2) . " deducted for your AI query.";

                if ($userId > 0) {
                    if ($this->userModel->deductBalance($userId, (string)$deductionAmount)) {
                        session()->setFlashdata('success', $costMessage);
                    } else {
                        // This error message covers insufficient balance or other deduction failures
                        $errors[] = 'Insufficient balance or failed to update balance.';
                    }
                } else {
                    // This case should ideally not be reached due to the earlier check, but included for safety.
                    $errors[] = 'User not logged in or invalid user ID. Cannot deduct balance.';
                    log_message('error', 'User not logged in or invalid user ID during balance deduction.');
                }
            }
            // --- End Deduct Cost ---

        } catch (\Exception $e) {
            $errors[] = 'An unexpected error occurred: ' . $e->getMessage();
            log_message('error', 'Crypto query error: ' . $e->getMessage());
        }

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('error', $errors);
        }

        return redirect()->back()->withInput()->with('result', $result);
    }
}
