<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to create the affiliate_click_logs table.
 * 
 * This table stores detailed click tracking data for affiliate links,
 * including IP addresses, referrers, user agents, and timestamps.
 */
class CreateAffiliateClickLogsTable extends Migration
{
    /**
     * Create the affiliate_click_logs table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'affiliate_link_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'referrer' => [
                'type'       => 'VARCHAR',
                'constraint' => '500',
                'null'       => true,
            ],
            'clicked_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('affiliate_link_id');
        $this->forge->addKey('clicked_at');

        // Add foreign key constraint
        $this->forge->addForeignKey(
            'affiliate_link_id',
            'affiliate_links',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('affiliate_click_logs');
    }

    /**
     * Drop the affiliate_click_logs table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->forge->dropTable('affiliate_click_logs');
    }
}
