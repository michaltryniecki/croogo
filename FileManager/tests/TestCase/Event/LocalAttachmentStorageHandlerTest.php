<?php
declare(strict_types=1);

namespace Croogo\FileManager\Test\TestCase\Event;

use Cake\TestSuite\TestCase;
use Croogo\FileManager\Event\LocalAttachmentStorageHandler;
use Croogo\FileManager\Model\Table\AssetsTable;
use Croogo\FileManager\Utility\StorageManager;
use FilesystemIterator;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Deleting an asset whose file is shared with another asset row.
 *
 * The storage path is derived from the file's content, so the same file uploaded
 * twice is two rows and one file on disk.
 */
class LocalAttachmentStorageHandlerTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Croogo/FileManager.AttachmentFolders',
        'plugin.Croogo/FileManager.Attachments',
        'plugin.Croogo/FileManager.Assets',
        'plugin.Croogo/FileManager.AssetUsages',
        'plugin.Croogo/FileManager.Users',
    ];

    protected AssetsTable $Assets;

    protected LocalAttachmentStorageHandler $handler;

    protected string $root;

    protected mixed $previousConfig = null;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'storage_handler_test_' . uniqid();
        mkdir($this->root . '/12/34/56', 0777, true);

        $this->previousConfig = StorageManager::config('LocalAttachment');
        StorageManager::config('LocalAttachment', [
            'adapterOptions' => [$this->root],
            'adapterClass' => LocalFilesystemAdapter::class,
            'class' => Filesystem::class,
        ]);

        $this->Assets = $this->getTableLocator()->get('Croogo/FileManager.Assets');
        $this->handler = new LocalAttachmentStorageHandler();
        $this->Assets->getEventManager()->on($this->handler);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $this->Assets->getEventManager()->off($this->handler);
        if ($this->previousConfig) {
            unset($this->previousConfig['object']);
            StorageManager::config('LocalAttachment', $this->previousConfig);
        } else {
            StorageManager::flush('LocalAttachment');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);

        parent::tearDown();
    }

    /**
     * An asset row written straight to the table, without the upload events.
     *
     * @param int $id Asset id
     * @param string $path Path inside the storage root
     * @param int|null $parentId Asset this one is a resized version of
     * @return void
     */
    protected function asset(int $id, string $path, ?int $parentId = null): void
    {
        $this->Assets->getConnection()->insert('assets', [
            'id' => $id,
            'parent_asset_id' => $parentId,
            'foreign_key' => 1,
            'model' => 'Attachments',
            'filename' => basename($path),
            'mime_type' => 'image/png',
            'extension' => 'png',
            'hash' => 'abc',
            'path' => '/assets' . $path,
            'adapter' => 'LocalAttachment',
        ]);
    }

    /**
     * A file inside the storage root.
     *
     * @param string $path Path inside the storage root
     * @return string Absolute path
     */
    protected function file(string $path): string
    {
        $file = $this->root . $path;
        file_put_contents($file, 'png');

        return $file;
    }

    /**
     * The bug: deleting one of two duplicates took the file from the other.
     *
     * @return void
     */
    public function testFileSurvivesWhileAnotherAssetUsesIt(): void
    {
        $file = $this->file('/12/34/56/abc.png');
        $this->asset(1, '/12/34/56/abc.png');
        $this->asset(2, '/12/34/56/abc.png');

        $this->assertTrue($this->Assets->delete($this->Assets->get(1)));

        $this->assertFalse($this->Assets->exists(['id' => 1]));
        $this->assertFileExists($file, 'The duplicate still points at this file');
    }

    /**
     * The guard must not leak files: the last row still takes its file.
     *
     * @return void
     */
    public function testFileGoesWithTheLastAssetThatUsesIt(): void
    {
        $file = $this->file('/12/34/56/abc.png');
        $this->asset(1, '/12/34/56/abc.png');
        $this->asset(2, '/12/34/56/abc.png');

        $this->Assets->delete($this->Assets->get(1));
        $this->assertTrue($this->Assets->delete($this->Assets->get(2)));

        $this->assertFileDoesNotExist($file);
    }

    /**
     * Resized versions are rows of their own and keep following the parent.
     *
     * @return void
     */
    public function testResizedVersionsGoWithTheirParent(): void
    {
        $file = $this->file('/12/34/56/abc.png');
        $thumb = $this->file('/12/34/56/abc.resized-100x57.png');
        $this->asset(1, '/12/34/56/abc.png');
        $this->asset(3, '/12/34/56/abc.resized-100x57.png', 1);

        $this->assertTrue($this->Assets->delete($this->Assets->get(1)));

        $this->assertFalse($this->Assets->exists(['id' => 3]));
        $this->assertFileDoesNotExist($file);
        $this->assertFileDoesNotExist($thumb);
    }

    /**
     * A row whose file is already gone must stay removable from the admin.
     *
     * @return void
     */
    public function testAssetWithoutFileOnDiskCanStillBeDeleted(): void
    {
        $this->asset(1, '/12/34/56/gone.png');

        $this->assertTrue($this->Assets->delete($this->Assets->get(1)));

        $this->assertFalse($this->Assets->exists(['id' => 1]));
    }
}
