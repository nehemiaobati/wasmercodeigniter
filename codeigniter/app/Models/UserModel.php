<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = User::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'password', 'username', 'balance', 'verification_token', 'is_verified', 'reset_token', 'reset_expires'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Calculates the sum of all user balances.
     *
     * @return string The total balance as a string, or '0.00' if no users exist.
     */
    public function getTotalBalance(): string
    {
        /** @var User|null $totalBalanceData */
        $totalBalanceData = $this->selectSum('balance')->first();
        return $totalBalanceData && $totalBalanceData->balance !== null ? (string) $totalBalanceData->balance : '0.00';
    }

    /**
     * Deducts a specified amount from a user's balance.
     *
     * @param int $userId The ID of the user.
     * @param int $amount The amount to deduct.
     *
     * @return bool True on success, false on failure (e.g., insufficient balance).
     */
    public function deductBalance(int $userId, int $amount): bool
    {
        /** @var User|null $user */
        $user = $this->find($userId);

        if ($user && $user->balance >= $amount) {
            $user->balance -= $amount;
            return $this->save($user);
        }

        return false;
    }

    /**
     * Adds a specified amount to a user's balance using precise calculation.
     *
     * @param int    $userId The ID of the user.
     * @param string $amount The amount to add, as a string.
     *
     * @return bool True on success, false if the user is not found.
     */
    public function addBalance(int $userId, string $amount): bool
    {
        /** @var User|null $user */
        $user = $this->find($userId);

        if ($user) {
            $currentBalance = $user->balance !== null ? (string) $user->balance : '0.00';
            $user->balance = bcadd($currentBalance, $amount, 2);
            return $this->save($user);
        }

        return false;
    }
}
