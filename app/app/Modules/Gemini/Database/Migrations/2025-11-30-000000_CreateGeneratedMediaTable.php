<?php

namespace App\Modules\Gemini\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGeneratedMediaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'video'],
            ],
            'model_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'local_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'remote_op_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'completed', 'failed'],
                'default'    => 'pending',
            ],
            'cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
                'default'    => 0.0000,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('generated_media');
    }

    public function down()
    {
        $this->forge->dropTable('generated_media');
    }
}
