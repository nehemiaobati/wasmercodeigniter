<?php

namespace App\Modules\Ollama\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOllamaSettingsAndPrompts extends Migration
{
    public function up()
    {
        // Table: ollama_prompts
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'prompt_text' => ['type' => 'TEXT'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('ollama_prompts');

        // Table: ollama_user_settings
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'assistant_mode_enabled' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('ollama_user_settings');
    }

    public function down()
    {
        $this->forge->dropTable('ollama_user_settings');
        $this->forge->dropTable('ollama_prompts');
    }
}
