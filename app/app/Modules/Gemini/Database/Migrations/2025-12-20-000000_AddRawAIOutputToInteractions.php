<?php

namespace App\Modules\Gemini\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRawAIOutputToInteractions extends Migration
{
    public function up()
    {
        $fields = [
            'ai_output_raw' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'ai_output'
            ],
        ];
        $this->forge->addColumn('interactions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('interactions', 'ai_output_raw');
    }
}
