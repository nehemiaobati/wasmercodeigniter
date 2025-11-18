<?php

namespace App\Modules\Gemini\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMemoryTables extends Migration
{
    public function up()
    {
        // --- Interactions Table ---
        // Stores each conversation turn with the AI.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unique_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'timestamp' => ['type' => 'DATETIME'],
            'user_input_raw' => ['type' => 'TEXT'],
            'ai_output' => ['type' => 'TEXT'],
            'relevance_score' => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 1.0],
            'last_accessed' => ['type' => 'DATETIME'],
            'context_used_ids' => ['type' => 'JSON', 'null' => true],
            'embedding' => ['type' => 'JSON', 'null' => true],
            'keywords' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('unique_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('interactions');

        // --- Entities Table ---
        // Acts as the knowledge graph, tracking concepts and their relationships.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entity_key' => ['type' => 'VARCHAR', 'constraint' => 255], // lowercase name
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Concept'],
            'access_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'relevance_score' => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 1.0],
            'mentioned_in' => ['type' => 'JSON', 'null' => true],
            'relationships' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['user_id', 'entity_key']);
        $this->forge->createTable('entities');
    }

    public function down()
    {
        $this->forge->dropTable('interactions');
        $this->forge->dropTable('entities');
    }
}
