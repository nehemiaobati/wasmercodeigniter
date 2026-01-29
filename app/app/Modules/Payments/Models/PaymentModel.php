<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models; // Updated namespace

use CodeIgniter\Model;

/**
 * Manages payment data and database interactions.
 */
class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Modules\Payments\Entities\Payment::class; // Updated return type
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'email', 'amount', 'reference', 'status', 'paystack_response'];

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
    protected $validationRules = [
        'user_id'   => 'required|is_natural_no_zero',
        'email'     => 'required|valid_email|max_length[255]',
        'amount'    => 'required|numeric|greater_than[0]',
        'reference' => 'required|is_unique[payments.reference]|max_length[100]',
        'status'    => 'required|in_list[pending,success,failed]',
    ];
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
}
