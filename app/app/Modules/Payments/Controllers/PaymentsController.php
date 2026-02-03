<?php

declare(strict_types=1);

namespace App\Modules\Payments\Controllers;

use App\Controllers\BaseController; // BaseController is still in app/Controllers
use App\Modules\Payments\Libraries\PaystackService;
use App\Modules\Payments\Models\PaymentModel; // Updated path
use App\Models\UserModel; // UserModel is still in app/Models
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

class PaymentsController extends BaseController
{
    protected PaymentModel $paymentModel;
    protected PaystackService $paystackService;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->paymentModel    = new PaymentModel();
        $this->paystackService = \Config\Services::paystackService();
        $this->userModel       = new UserModel();
        helper(['form', 'url']);
    }

    public function index(): string
    {
        $data = [
            'pageTitle'       => 'Top Up Your Account | Afrikenkid',
            'metaDescription' => 'Securely add funds to your account via M-Pesa, Airtel Money, or Credit Card. All payments are processed by Paystack.',
            'canonicalUrl'    => url_to('payment.index'),
            'email'           => session()->get('userEmail') ?? '',
            'errors'          => session()->getFlashdata('errors'),
        ];
        // Add noindex directive for authenticated pages
        $data['robotsTag'] = 'noindex, follow';

        // Updated view path to reflect module structure
        return view('App\Modules\Payments\Views\payment\payment_form', $data);
    }

    public function initiate(): RedirectResponse
    {
        $rules = [
            'email'  => 'required|valid_email',
            'amount' => 'required|numeric|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email  = $this->request->getPost('email');
        $amount = (int) $this->request->getPost('amount');
        $userId = session()->get('userId');

        $reference = $this->paystackService->generateReference();

        $this->paymentModel->db->transStart();

        $this->paymentModel->insert([
            'user_id'   => $userId,
            'email'     => $email,
            'amount'    => $amount,
            'reference' => $reference,
            'status'    => 'pending',
        ]);

        $this->paymentModel->db->transComplete();

        if ($this->paymentModel->db->transStatus() === false) {
            log_message('error', "[PaymentsController] Payment record insertion failed for User ID {$userId}. Reference: {$reference}");
            return redirect()->back()->withInput()->with('error', 'Failed to initiate payment. Please try again.');
        }

        // Updated URL generation to use the route name for the verify action
        $callbackUrl = url_to('payment.verify') . '?app_reference=' . $reference;

        $response = $this->paystackService->initializeTransaction($email, $amount, $callbackUrl);

        if ($response['status'] === true) {
            return redirect()->to($response['data']['authorization_url']);
        }

        log_message('error', "[PaymentsController] Payment initiation failed for User ID {$userId}. Reference: {$reference}. Error: " . ($response['message'] ?? 'Unknown Paystack error'));
        return redirect()->back()->with('error', ['paystack' => $response['message']]);
    }

    public function verify(): RedirectResponse
    {
        $appReference = $this->request->getGet('app_reference');
        $paystackReference = $this->request->getGet('trxref');

        if (empty($appReference) || empty($paystackReference)) {
            return redirect()->to(url_to('payment.index'))->with('error', ['payment' => 'Payment reference not found.']);
        }

        $result = $this->paystackService->verifyAndProcessPayment((string) $appReference, (string) $paystackReference);

        if ($result['status'] === true) {
            return redirect()->to(url_to('payment.index'))->with('success', $result['message']);
        }

        return redirect()->to(url_to('payment.index'))->with('error', ['payment' => $result['message']]);
    }
}
