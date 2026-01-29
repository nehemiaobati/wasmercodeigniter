<?php

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;
use App\Entities\Campaign;

class CampaignModel extends Model
{
    protected $table            = 'campaigns';
    protected $primaryKey       = 'id';
    protected $returnType       = Campaign::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = ['subject', 'body'];
}
