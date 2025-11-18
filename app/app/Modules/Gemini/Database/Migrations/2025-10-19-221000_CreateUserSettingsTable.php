<?php declare(strict_types=1);

namespace App\Modules\Gemini\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the user_settings table to store user-specific application settings,
 * starting with the state of the Gemini assistant mode.
 */
class CreateUserSettingsTable extends Migration
{
    /**
     * Creates the user_settings table with columns for user ID and assistant mode status.
     *
     * This method defines the schema for the new table, including a foreign key relationship
     * to the 'users' table with cascading deletes to maintain data integrity. A unique constraint
     * on 'user_id' ensures that each user has only one settings entry.
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true, // Ensures one settings row per user
            ],
            'assistant_mode_enabled' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'null'    => false,
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
        $this->forge->createTable('user_settings');
    }

    /**
     * Drops the user_settings table.
     *
     * This method is called when the migration is rolled back. It safely removes the
     * user_settings table from the database.
     *
     * @return void
     */
    public function down(): void
    {
        $this->forge->dropTable('user_settings');
    }
}
