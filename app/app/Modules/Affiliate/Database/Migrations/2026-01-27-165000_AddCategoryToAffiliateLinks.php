<?php

declare(strict_types=1);

namespace App\Modules\Affiliate\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to add category_id to affiliate_links table.
 * 
 * This creates a relationship between affiliate links and categories.
 */
class AddCategoryToAffiliateLinks extends Migration
{
    /**
     * Add category_id column to affiliate_links table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->forge->addColumn('affiliate_links', [
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'title',
            ],
        ]);

        // Add index for better query performance
        $this->forge->addKey('category_id', false, false, 'affiliate_links');

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
     * Remove category_id column from affiliate_links table.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE affiliate_links DROP FOREIGN KEY fk_affiliate_links_category');

        // Drop the column
        $this->forge->dropColumn('affiliate_links', 'category_id');
    }
}
