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
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $email  = $this->request->getPost('email');
        $amount = (int) $this->request->getPost('amount');
        $userId = session()->get('userId');

        $reference = 'PAY-' . Time::now()->getTimestamp() . '-' . bin2hex(random_bytes(5));

        $this->paymentModel->insert([
            'user_id'   => $userId,
            'email'     => $email,
            'amount'    => $amount,
            'reference' => $reference,
            'status'    => 'pending',
        ]);

        // Updated URL generation to use the route name for the verify action
        $callbackUrl = url_to('payment.verify') . '?app_reference=' . $reference;

        $response = $this->paystackService->initializeTransaction($email, $amount, $callbackUrl);

        if ($response['status'] === true) {
            return redirect()->to($response['data']['authorization_url']);
        }

        return redirect()->back()->with('error', ['paystack' => $response['message']]);
    }

    public function verify(): RedirectResponse
    {
        $appReference = $this->request->getGet('app_reference');
        $paystackReference = $this->request->getGet('trxref');

        if (empty($appReference) || empty($paystackReference)) {
            return redirect()->to(url_to('payment.index'))->with('error', ['payment' => 'Payment reference not found.']);
        }

        $payment = $this->paymentModel->where('reference', $appReference)->first();

        if ($payment === null) {
            return redirect()->to(url_to('payment.index'))->with('errors', ['payment' => 'Invalid payment reference.']);
        }

        if ($payment->status === 'success') {
            return redirect()->to(url_to('payment.index'))->with('success', 'Payment already verified.');
        }

        $response = $this->paystackService->verifyTransaction($paystackReference);

        if ($response['status'] === true && isset($response['data']['status']) && $response['data']['status'] === 'success') {

            $db = \Config\Database::connect();
            $db->transStart();

            $jsonResponse = json_encode($response['data']);
            if ($jsonResponse === false) {
                log_message('error', 'Failed to encode Paystack success response for reference: ' . $paystackReference);
                $jsonResponse = json_encode(['error' => 'JSON encoding failed']);
            }
            $this->paymentModel->update($payment->id, [
                'status'            => 'success',
                'paystack_response' => $jsonResponse,
            ]);

            if ($payment->user_id) {
                $this->userModel->addBalance((int) $payment->user_id, (string) $payment->amount);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('critical', 'Payment verification transaction failed for payment ID: ' . $payment->id);
                return redirect()->to(url_to('payment.index'))->with('error', ['payment' => 'A critical error occurred. Please contact support.']);
            }

            return redirect()->to(url_to('payment.index'))->with('success', 'Payment successful!');
        }

        $jsonResponse = json_encode($response['data'] ?? $response);
        if ($jsonResponse === false) {
            log_message('error', 'Failed to encode Paystack failure response for reference: ' . $paystackReference);
            $jsonResponse = json_encode(['error' => 'JSON encoding failed']);
        }
        $this->paymentModel->update($payment->id, [
            'status'            => 'failed',
            'paystack_response' => $jsonResponse,
        ]);

        return redirect()->to(url_to('payment.index'))->with('error', ['payment' => $response['message'] ?? 'Payment verification failed.']);
    }
}
