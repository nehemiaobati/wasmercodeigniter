<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\UserSetting;

/**
 * Manages user settings data and database interactions.
 */
class UserSettingsModel extends Model
{
    protected $table            = 'user_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = UserSetting::class;
    protected $useTimestamps    = true;
    protected $allowedFields    = ['user_id', 'assistant_mode_enabled', 'voice_output_enabled'];
}
