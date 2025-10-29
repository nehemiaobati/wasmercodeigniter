<?php declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PaymentModel;

/**
 * Handles user account-related functionalities, including displaying user information,
 * transaction history, and processing payment references.
 */
class AccountController extends BaseController
{
    /**
     * Displays the user's account information and transaction history.
     *
     * Retrieves user details and paginated transaction data for the logged-in user.
     * Processes transaction references to determine the correct display value.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface The rendered account index view.
     */
    public function index()
    {
        $userModel = new UserModel();
        $paymentModel = new PaymentModel();

        // Retrieve the user ID from the session.
        $userId = session()->get('userId');

        // If the user is not logged in, redirect to the login page.
        if (!$userId) {
            return redirect()->to(url_to('login'));
        }

        // Load the form helper to make form_open() available in views.
        helper('form');

        $user = $userModel->find($userId);

        // Pass user data to the view.
        $data['user'] = $user;
        
        $data['pageTitle'] = 'My Account | Afrikenkid';
        $data['metaDescription'] = 'Manage your Afrikenkid profile, view your account balance, and see your full transaction history.';
        $data['canonicalUrl'] = url_to('account.index');
        // Add noindex directive for authenticated pages
        $data['robotsTag'] = 'noindex, follow';

        // Retrieve paginated transactions for the user, ordered by creation date.
        // Displays 5 transactions per page.
        $data['transactions'] = $paymentModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->paginate(5);
        // Pass the pager instance to the view for pagination controls.
        $data['pager'] = $paymentModel->pager;

        // If the user is not found (which should not happen if logged in), redirect to home.
        if (!$user) {
            return redirect()->to(url_to('home'))->with('error', 'User not found.');
        }

        // Initialize an array to store display references for transactions.
        $data['display_references'] = [];

        // Fetch total transaction count for debugging pagination.
        $totalTransactions = $paymentModel->where('user_id', $userId)->countAllResults();
        log_message('debug', 'Total transactions for user ID ' . $userId . ': ' . $totalTransactions);

        // Process each transaction to determine the reference to display.
        if (!empty($data['transactions'])) {
            foreach ($data['transactions'] as $transaction) {
                $paystack_ref = null;
                $db_ref = $transaction->reference ?? null; // Reference from the payments table.
                $display_ref = 'N/A'; // Default value if no reference is found.

                // Attempt to extract the reference from the Paystack response if available.
                if (!empty($transaction->paystack_response)) {
                    $paystackResponse = json_decode($transaction->paystack_response, true); // Decode JSON response.

                    if (is_array($paystackResponse)) {
                        // Try to get reference from Paystack response, regardless of transaction status.
                        $paystack_ref = $paystackResponse['reference'] ?? null;
                    }
                }

                // Prioritize the Paystack reference if available, otherwise use the database reference.
                if (!empty($paystack_ref)) {
                    $display_ref = $paystack_ref;
                } elseif (!empty($db_ref)) {
                    $display_ref = $db_ref;
                }
                // If both are empty, $display_ref remains 'N/A'.

                // Add the determined reference to the display_references array.
                $data['display_references'][] = $display_ref;
            }
        }

        // Render the account index view with the prepared data.
        return $this->response->setBody(view('account/index', $data));
    }
}
