<?php

use Migrations\AbstractMigration;

/**
 * Logical folders for the attachment library.
 *
 * Folders live in the database only: moving an attachment between folders
 * changes `attachments.folder_id` and nothing on disk, so asset paths (and the
 * URLs already embedded in content) stay put. Attachments without a folder sit
 * in the root, which is why existing rows need no data migration.
 *
 * Timestamps are `created`/`modified` (+ `_by`) to match what
 * FileManagerSyncTimestampFields left on `attachments`, so the Timestamp and
 * Trackable behaviors work without configuration.
 */
class FileManagerAttachmentFolders extends AbstractMigration
{
    public function up(): void
    {
        $this->table('attachment_folders')
            ->addColumn('parent_id', 'integer', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('lft', 'integer', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('rght', 'integer', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('name', 'string', [
                'null' => false, 'length' => 150,
            ])
            ->addColumn('slug', 'string', [
                'null' => true, 'default' => null, 'length' => 150,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('created_by', 'integer', [
                'null' => true, 'default' => null,
            ])
            ->addColumn('modified_by', 'integer', [
                'null' => true, 'default' => null,
            ])
            ->addIndex(['parent_id'], ['name' => 'ix_attachment_folders_parent_id'])
            ->addIndex(['lft', 'rght'], ['name' => 'ix_attachment_folders_lft_rght'])
            ->create();

        $this->table('attachments')
            ->addColumn('folder_id', 'integer', [
                'null' => true, 'default' => null, 'after' => 'id',
            ])
            ->addIndex(['folder_id'], ['name' => 'ix_attachments_folder_id'])
            ->update();
    }

    public function down(): void
    {
        $this->table('attachments')
            ->removeIndexByName('ix_attachments_folder_id')
            ->removeColumn('folder_id')
            ->update();

        $this->table('attachment_folders')->drop()->save();
    }
}
