<?php

namespace App\Modules\Ollama\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStreamOutputToOllamaSettings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('ollama_user_settings', [
            'stream_output_enabled' => ['type' => 'BOOLEAN', 'default' => false, 'after' => 'assistant_mode_enabled'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('ollama_user_settings', 'stream_output_enabled');
    }
}
