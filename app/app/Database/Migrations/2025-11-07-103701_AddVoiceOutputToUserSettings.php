<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoiceOutputToUserSettings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_settings', [
            'voice_output_enabled' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'null'    => false,
                'after'   => 'assistant_mode_enabled',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_settings', 'voice_output_enabled');
    }
}
