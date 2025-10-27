<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\Forge;

/**
 * Create Campaigns Table Migration
 */
class Migration_2025_10_27_042600_CreateCampaignsTable extends Migration
{
    /**
     * @var Forge
     */
    protected $forge;

    public function __construct()
    {
        $this->forge = \Config\Database::forge();
    }

    /**
     * Creates the campaigns table.
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => '11',
                'unsigned' => true,
                'auto_increment' => true,
                'null' => false,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => false,
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

        $this->forge->addKey('id', true); // Primary key
        $this->forge->createTable('campaigns');
    }

    /**
     * Drops the campaigns table.
     */
    public function down(): void
    {
        $this->forge->dropTable('campaigns');
    }
}
