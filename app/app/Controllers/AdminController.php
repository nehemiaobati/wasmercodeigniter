<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Entities\User; // Import the User entity

/**
 * Manages administrative functions related to user accounts, including viewing,
 * searching, updating balances, and deleting users.
 */
class AdminController extends BaseController
{
    /**
     * Displays a paginated list of all users.
     *
     * Retrieves users with pagination and provides total user count and balance information.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface The rendered admin index view with user data.
     */
    public function index()
    {
        $userModel = new UserModel();
        // Paginate users, displaying 10 per page.
        $data['users'] = $userModel->paginate(10);
        // Pass the pager instance to the view for pagination controls.
        $data['pager'] = $userModel->pager;
        // Get the total number of users for display.
        $data['total_users'] = $userModel->pager->getTotal();
        // Retrieve the total balance of all users, assuming UserModel has getTotalBalance().
        $data['total_balance'] = $userModel->getTotalBalance();

        return $this->response->setBody(view('admin/index_view', $data));
    }

    /**
     * Handles user search functionality based on a query parameter.
     *
     * Searches for users by username or email. If no search query is provided,
     * it redirects to the main user list.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface The rendered view with search results.
     */
    public function searchUsers()
    {
        $userModel = new UserModel();
        // Get the search query from the 'q' GET parameter.
        $searchQuery = $this->request->getGet('q');

        // If no search query is provided, redirect to the main user list.
        if (empty($searchQuery)) {
            return redirect()->to(url_to('admin.index'));
        }

        // Perform a search for users whose username or email contains the search query.
        // Use paginate(10) to retrieve paginated results.
        $data['users'] = $userModel->like('username', $searchQuery)
                                   ->orLike('email', $searchQuery)
                                   ->paginate(10);
        // Pass the pager instance to the view for pagination controls.
        $data['pager'] = $userModel->pager;
        $data['search_query'] = $searchQuery;
        // Count the number of users found in the search results.
        $data['total_users'] = count($data['users']);

        // Render the user search results view.
        return $this->response->setBody(view('admin/user_search_results', $data));
    }

    /**
     * Displays the details of a specific user.
     *
     * @param int $id The unique identifier of the user to display.
     * @return \CodeIgniter\HTTP\ResponseInterface The rendered view with user details or an error message.
     */
    public function show($id)
    {
        $userModel = new UserModel();
        $data['user'] = $userModel->find($id);

        // If the user is not found, redirect back with an error message.
        if (!$data['user']) {
            return redirect()->back()->with('error', 'User not found.');
        }

        return $this->response->setBody(view('admin/user_details', $data));
    }

    /**
     * Updates a user's balance.
     *
     * Validates input for amount and action (deposit/withdraw) and performs
     * precise balance updates using bcmath functions within a database transaction.
     *
     * @param int $id The unique identifier of the user whose balance is to be updated.
     * @return \CodeIgniter\HTTP\ResponseInterface Redirects back with status messages or errors.
     */
    public function updateBalance($id)
    {
        $userModel = new UserModel();
        
        /** @var User|null $user */
        $user = $userModel->find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $rules = [
            'amount' => 'required|numeric|greater_than[0]',
            'action' => 'required|in_list[deposit,withdraw]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = $this->request->getPost('amount');
        $action = $this->request->getPost('action');

        $db = \Config\Database::connect();
        $db->transStart();

        // Re-fetch user within transaction to ensure we have the latest data and lock the row
        $userInTransaction = $userModel->find($id);
        if (!$userInTransaction) {
             $db->transComplete();
             return redirect()->back()->with('error', 'User not found during transaction.');
        }

        if ($action === 'deposit') {
            $userModel->addBalance($id, (string)$amount);
        } elseif ($action === 'withdraw') {
            if (bccomp((string) $userInTransaction->getBalance(), (string) $amount, 2) < 0) {
                // End transaction before redirecting
                $db->transComplete();
                return redirect()->back()->withInput()->with('error', 'Insufficient balance.');
            }
            $userModel->deductBalance($id, (string)$amount);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', "Admin balance update transaction failed for user ID: {$id}");
            return redirect()->back()->withInput()->with('error', 'A database error occurred. Failed to update balance.');
        }
        
        return redirect()->to(url_to('admin.users.show', $id))->with('success', 'Balance updated successfully.');
    }


    /**
     * Deletes a specific user.
     *
     * Prevents an administrator from deleting their own account.
     *
     * @param int $id The unique identifier of the user to delete.
     * @return \CodeIgniter\HTTP\ResponseInterface Redirects to the admin index with a status message.
     */
    public function delete($id)
    {
        $userModel = new UserModel();
        // Get the ID of the currently logged-in user to prevent self-deletion.
        $currentUserId = session()->get('userId');

        // Prevent the administrator from deleting their own account.
        if ($id == $currentUserId) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        // Attempt to delete the user from the database.
        if ($userModel->delete($id)) {
            // Redirect to the admin index with a success message.
            return redirect()->to(url_to('admin.index'))->with('success', 'User deleted successfully.');
        } else {
            // If deletion fails, redirect back with an error message.
            return redirect()->back()->with('error', 'Failed to delete user.');
        }
    }
}
