<?php declare(strict_types=1);

namespace App\Modules\Payments\Entities; // Updated namespace

use CodeIgniter\Entity\Entity;

class Payment extends Entity
{
    /**
     * @property int|null $id
     * @property int|null $user_id
     * @property string|null $email
     * @property int|null $amount
     * @property string|null $reference
     * @property string|null $status
     * @property string|null $paystack_response
     * @property string|null $created_at
     * @property string|null $updated_at
     * @property string|null $deleted_at
     */
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
