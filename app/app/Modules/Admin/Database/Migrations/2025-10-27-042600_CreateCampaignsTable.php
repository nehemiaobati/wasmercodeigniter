<?php

namespace App\Modules\Admin\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\Forge;

/**
 * Create Campaigns Table Migration
 */
class CreateCampaignsTable extends Migration
{
    /**
     * Creates the campaigns table.
     */
    public function up(): void
    {
        // Campaigns Table
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
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'pending', 'sending', 'completed', 'paused', 'retry_mode'],
                'default' => 'draft'
            ],
            'last_processed_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sent_count'        => ['type' => 'INT', 'default' => 0],
            'error_count'       => ['type' => 'INT', 'default' => 0],
            'total_recipients'  => ['type' => 'INT', 'default' => 0],
            'stop_at_count'     => ['type' => 'INT', 'default' => 0],
            'quota_increment'   => ['type' => 'INT', 'default' => 0],
            'max_user_id'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'quota_hit_at'      => ['type' => 'DATETIME', 'null' => true],
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
        $this->forge->addKey('status');
        $this->forge->addKey('quota_hit_at');
        $this->forge->addKey('created_at');
        $this->forge->createTable('campaigns');


        // Campaign Logs Table
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'campaign_id'    => ['type' => 'INT', 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['sent', 'failed']],
            'error_message'  => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('campaign_id', 'campaigns', 'id', 'CASCADE', 'CASCADE');
        // Composite index
        $this->forge->addKey(['campaign_id', 'status']);
        $this->forge->createTable('campaign_logs');
    }

    /**
     * Drops the campaigns table.
     */
    public function down(): void
    {
        $this->forge->dropTable('campaign_logs');
        $this->forge->dropTable('campaigns');
    }
}
