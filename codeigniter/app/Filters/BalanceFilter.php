<?php

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
        // Check if user is logged in
        if (!session()->has('userId')) { // Changed from 'user_id' to 'userId'
            // Redirect to login if not logged in
            // Using url_to() to match user preference
            return redirect()->to(url_to('login')); // Changed from direct path '/login'
        }

        $userModel = new UserModel();
        $userId = session()->get('userId'); // Changed from 'user_id' to 'userId'
        $user = $userModel->find($userId);

        // Check if user exists and has balance
        if (!$user || !isset($user->balance)) {
            // Handle case where user or balance is not found, maybe redirect to a profile page or error
            // Redirect to the main payment page using url_to() and set an alert
            session()->setFlashdata('alert', 'User data not found or balance missing. Please check your account or make a payment.');
            return redirect()->to(url_to('payment.index')); // Changed from url_to('payment.initiate')
        }

        // Define the minimum required balance for crypto operations.
        // This value can be made configurable if needed.
        $requiredBalance = 10.0; 
        
        if ($user->balance < $requiredBalance) {
            // Redirect to the main payment page if balance is insufficient using url_to() and set an alert
            // Including the required balance in the alert message
            session()->setFlashdata('alert', 'Your balance is too low. You need at least ' . $requiredBalance . ' to continue.');
            return redirect()->to(url_to('payment.index')); // Changed from url_to('payment.initiate')
        }

        // If balance is sufficient, allow the request to proceed
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
}
