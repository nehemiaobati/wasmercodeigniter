<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel; // Assuming User model exists and is accessible

class BalanceFilter implements FilterInterface
{
    /**
     * Do whatever processing will be needed for the filter.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$this->_isAuthenticated()) {
            return redirect()->to(url_to('login'));
        }

        $user = $this->_getUser();

        if ($this->_isBalanceLow($user)) {
            return $this->_handleLowBalance($request);
        }

        return null;
    }

    /**
     * Do whatever processing will be needed for the response.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // This filter only needs logic in the 'before' method.
    }

    // --- Helper Methods ---

    private function _isAuthenticated(): bool
    {
        return session()->has('userId');
    }

    private function _getUser()
    {
        $userModel = new UserModel();
        return $userModel->find(session()->get('userId'));
    }

    private function _isBalanceLow($user): bool
    {
        // Check if user exists and has balance
        if (!$user || !isset($user->balance)) {
            return true;
        }

        // Define the minimum required balance for AI/Crypto operations.
        $requiredBalance = 1;

        return $user->balance < $requiredBalance;
    }

    private function _handleLowBalance(RequestInterface $request)
    {
        $requiredBalance = 1;
        $message = 'Your balance is too low. You need at least ' . $requiredBalance . ' to continue.';

        // Check for AJAX request
        if ($request instanceof \CodeIgniter\HTTP\IncomingRequest && $request->isAJAX()) {
            session()->setFlashdata('alert', $message);
            return response()->setJSON([
                'status' => 'error',
                'message' => 'Insufficient balance. You need at least ' . $requiredBalance . ' to continue.',
                'redirect' => url_to('payment.index'),
                'csrf_token' => csrf_hash()
            ])->setStatusCode(403);
        }

        // Standard Redirect
        session()->setFlashdata('alert', $message);
        return redirect()->to(url_to('payment.index'));
    }
}
