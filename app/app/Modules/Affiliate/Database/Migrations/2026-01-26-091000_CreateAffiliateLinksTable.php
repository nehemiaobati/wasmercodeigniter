<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to create the affiliate_links table.
 * 
 * This table stores Amazon affiliate links with their short codes
 * for redirect functionality and click tracking.
 */
class CreateAffiliateLinksTable extends Migration
{
    /**
     * Create the affiliate_links table.
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
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'short_url' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'full_url' => [
                'type' => 'TEXT',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'click_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'active',
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
        $this->forge->createTable('affiliate_links');
    }

    /**
     * Drop the affiliate_links table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->forge->dropTable('affiliate_links');
    }
}
