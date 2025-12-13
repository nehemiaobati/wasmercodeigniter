<?php

namespace App\Modules\Gemini\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStreamOutputToUserSettings extends Migration
{

    public function up()
    {
        $this->forge->addColumn('user_settings', [
            'stream_output_enabled' => [
                'type'       => 'BOOLEAN',
                'default'    => 0, // Default to NOT streaming (or 1 if preferred, but existing code seems to default to false in logic usually)
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_settings', 'stream_output_enabled');
    }
}
