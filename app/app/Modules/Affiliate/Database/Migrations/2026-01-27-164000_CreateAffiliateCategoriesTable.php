<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to create the affiliate_categories table.
 * 
 * This table stores categories for organizing affiliate links.
 */
class CreateAffiliateCategoriesTable extends Migration
{
    /**
     * Create the affiliate_categories table.
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->createTable('affiliate_categories');
    }

    /**
     * Drop the affiliate_categories table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->forge->dropTable('affiliate_categories');
    }
}
