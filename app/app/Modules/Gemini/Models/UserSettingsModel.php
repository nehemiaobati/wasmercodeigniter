<?php

declare(strict_types=1);

namespace App\Modules\Gemini\Models;

use CodeIgniter\Model;
use App\Entities\UserSetting;

/**
 * Manages user settings data and database interactions.
 */
class UserSettingsModel extends Model
{
    protected $table            = 'user_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'App\Modules\Gemini\Entities\UserSetting';
    protected $useTimestamps    = true;
    protected $allowedFields    = ['user_id', 'assistant_mode_enabled', 'voice_output_enabled', 'stream_output_enabled'];
}
