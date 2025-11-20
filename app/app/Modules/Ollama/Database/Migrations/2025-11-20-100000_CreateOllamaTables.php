<?php declare(strict_types=1);

namespace App\Modules\Ollama\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOllamaTables extends Migration
{
    public function up()
    {
        // --- Ollama Interactions Table ---
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true
            ],
            'prompt_hash' => [
                'type'       => 'VARCHAR', 
                'constraint' => 64, 
                'comment'    => 'Hash for quick lookups'
            ],
            'user_input' => ['type' => 'TEXT'],
            'ai_response' => ['type' => 'TEXT'],
            'ai_model' => [
                'type'       => 'VARCHAR',
                'constraint' => 100
            ],
            'embedding' => [
                'type' => 'JSON', 
                'null' => true,
                'comment' => 'Vector representation of the interaction'
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ollama_interactions');
    }

    public function down()
    {
        $this->forge->dropTable('ollama_interactions');
    }
}
