<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Modules\Payments\Models\PaymentModel;

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

        // Load the form helper to make form_open() available in views.
        helper('form');

        $userId = session()->get('userId'); // Assuming this is now handled by a filter or authentication system
        $user = $userModel->find($userId);

        // Pass user data to the view.
        $data['user'] = $user;

        $data['pageTitle'] = 'My Account | Afrikenkid';
        $data['metaDescription'] = 'Manage your profile, view your account balance, and see your full transaction history.';
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

        // Process each transaction to determine the reference to display via the Entity.
        if (!empty($data['transactions'])) {
            foreach ($data['transactions'] as $transaction) {
                $data['display_references'][] = $transaction->getDisplayReference();
            }
        }

        // Render the account index view with the prepared data.
        return $this->response->setBody(view('App\Modules\Account\Views\index', $data));
    }
}
