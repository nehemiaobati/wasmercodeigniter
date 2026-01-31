<?php

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;
use App\Entities\Campaign;

/**
 * Model for handling email campaigns.
 * Tracks delivery status, recipient snapshots, and SMTP quota health.
 */
class CampaignModel extends Model
{
    protected $table            = 'campaigns';
    protected $primaryKey       = 'id';
    protected $returnType       = Campaign::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = ['subject', 'body', 'status', 'last_processed_id', 'sent_count', 'error_count', 'total_recipients', 'stop_at_count', 'max_user_id', 'quota_hit_at'];
}
