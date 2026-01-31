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
        $this->forge->addKey('created_at');
        $this->forge->createTable('affiliate_categories');

        // Add foreign key constraint with SET NULL on delete
        $this->db->query('
            ALTER TABLE affiliate_links
            ADD CONSTRAINT fk_affiliate_links_category
            FOREIGN KEY (category_id)
            REFERENCES affiliate_categories(id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ');
    }

    /**
     * Drop the affiliate_categories table.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE affiliate_links DROP FOREIGN KEY fk_affiliate_links_category');

        $this->forge->dropTable('affiliate_categories');
    }
}
