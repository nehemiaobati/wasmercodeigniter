<?php declare(strict_types=1);

namespace App\Modules\Ollama\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOllamaEntities extends Migration
{
    public function up()
    {
        // Concepts/Entities Table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entity_key' => ['type' => 'VARCHAR', 'constraint' => 255], // lowercase keyword
            'name' => ['type' => 'VARCHAR', 'constraint' => 255], // Original display name
            'access_count' => ['type' => 'INT', 'default' => 0],
            'relevance_score' => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 1.0],
            'mentioned_in' => ['type' => 'JSON', 'null' => true], // List of Interaction IDs
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['user_id', 'entity_key']);
        $this->forge->createTable('ollama_entities');

        // Update Interactions Table to support scores and keywords
        $this->forge->addColumn('ollama_interactions', [
            'relevance_score' => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 1.0, 'after' => 'ai_model'],
            'keywords' => ['type' => 'JSON', 'null' => true, 'after' => 'embedding']
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('ollama_entities');
        $this->forge->dropColumn('ollama_interactions', ['relevance_score', 'keywords']);
    }
}