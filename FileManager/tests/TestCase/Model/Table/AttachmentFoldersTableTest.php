<?php

namespace Croogo\FileManager\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Croogo\FileManager\Model\Table\AttachmentFoldersTable;
use Croogo\FileManager\Model\Table\AttachmentsTable;
use InvalidArgumentException;

/**
 * Folder tree of the attachment library.
 *
 * Fixture tree:
 *
 *   (root)        attachments 1, 2
 *   Produkty (1)  attachment 3
 *     Głowice (2) attachment 4
 *       Mindray (3) attachments 5, 6
 *     Aparaty (4)
 *   Strony (5)
 */
class AttachmentFoldersTableTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Croogo/FileManager.AttachmentFolders',
        'plugin.Croogo/FileManager.Attachments',
        'plugin.Croogo/FileManager.Assets',
        'plugin.Croogo/FileManager.AssetUsages',
        'plugin.Croogo/FileManager.Users',
    ];

    protected AttachmentFoldersTable $Folders;

    protected AttachmentsTable $Attachments;

    public function setUp(): void
    {
        parent::setUp();
        $this->Folders = $this->getTableLocator()->get('Croogo/FileManager.AttachmentFolders');
        $this->Attachments = $this->getTableLocator()->get('Croogo/FileManager.Attachments');
    }

    protected function folderOf(int $attachmentId): ?int
    {
        return $this->Attachments->get($attachmentId)->folder_id;
    }

    /**
     * Nested-set invariants: lft/rght are exactly 1..2n, every node sits inside
     * its parent, and a node's range contains only its own descendants.
     */
    protected function assertTreeIsValid(): void
    {
        $nodes = $this->Folders->find()->select(['id', 'parent_id', 'lft', 'rght'])
            ->disableHydration()->all()->indexBy('id')->toArray();

        $bounds = [];
        foreach ($nodes as $node) {
            $this->assertLessThan($node['rght'], $node['lft'], "Node {$node['id']}");
            $bounds[] = $node['lft'];
            $bounds[] = $node['rght'];
        }
        sort($bounds);
        $this->assertSame(range(1, 2 * count($nodes)), $bounds, 'lft/rght are not 1..2n');

        $isAncestor = function ($ancestorId, $node) use ($nodes) {
            while ($node['parent_id'] !== null) {
                if ($node['parent_id'] === $ancestorId) {
                    return true;
                }
                $node = $nodes[$node['parent_id']];
            }

            return false;
        };
        foreach ($nodes as $a) {
            foreach ($nodes as $b) {
                if ($a['id'] === $b['id']) {
                    continue;
                }
                $inside = $b['lft'] > $a['lft'] && $b['rght'] < $a['rght'];
                $this->assertSame(
                    $isAncestor($a['id'], $b),
                    $inside,
                    "Node {$b['id']} vs {$a['id']}"
                );
            }
        }
    }

    public function testCreateNestedFolders(): void
    {
        $a = $this->Folders->saveOrFail($this->Folders->newEntity(['name' => 'Galeria']));
        $b = $this->Folders->saveOrFail($this->Folders->newEntity(['name' => 'Konferencje', 'parent_id' => $a->id]));
        $c = $this->Folders->saveOrFail($this->Folders->newEntity(['name' => ' 2026 ', 'parent_id' => $b->id]));

        $this->assertSame('2026', $c->name, 'Name is trimmed');
        $this->assertSame('konferencje', $b->slug);
        $path = array_map(fn($f) => $f->name, $this->Folders->pathTo($c->id));
        $this->assertSame(['Galeria', 'Konferencje', '2026'], $path);
        $this->assertTreeIsValid();
    }

    public function testNameIsUniqueWithinParentOnly(): void
    {
        $sibling = $this->Folders->newEntity(['name' => 'Aparaty', 'parent_id' => 1]);
        $this->assertFalse($this->Folders->save($sibling));
        $this->assertArrayHasKey('name', $sibling->getErrors());

        $rootDuplicate = $this->Folders->newEntity(['name' => 'Strony']);
        $this->assertFalse($this->Folders->save($rootDuplicate), 'Two root folders with one name');

        $padded = $this->Folders->newEntity(['name' => '  Strony ']);
        $this->assertFalse($this->Folders->save($padded), 'Whitespace does not make a new name');

        $elsewhere = $this->Folders->newEntity(['name' => 'Aparaty', 'parent_id' => 5]);
        $this->assertNotFalse($this->Folders->save($elsewhere), 'Same name under another parent is fine');
    }

    public function testNameValidation(): void
    {
        $this->assertNotEmpty($this->Folders->newEntity(['name' => ''])->getErrors());
        $this->assertNotEmpty($this->Folders->newEntity(['name' => 'a/b'])->getErrors());
    }

    public function testParentMustExist(): void
    {
        $folder = $this->Folders->newEntity(['name' => 'Sierota', 'parent_id' => 999]);
        $this->assertFalse($this->Folders->save($folder));
        $this->assertArrayHasKey('parent_id', $folder->getErrors());
    }

    public function testMoveFolderWithSubtree(): void
    {
        // Głowice (with Mindray inside) goes under Strony.
        $glowice = $this->Folders->get(2);
        $glowice = $this->Folders->patchEntity($glowice, ['parent_id' => 5]);
        $this->Folders->saveOrFail($glowice);

        $path = array_map(fn($f) => $f->name, $this->Folders->pathTo(3));
        $this->assertSame(['Strony', 'Głowice', 'Mindray'], $path);
        // Attachments follow their folder without being touched.
        $this->assertSame(3, $this->folderOf(5));
        $this->assertSame(2, $this->folderOf(4));
        $this->assertTreeIsValid();
    }

    public function testCannotMoveFolderIntoItsOwnSubtree(): void
    {
        foreach ([1, 3] as $target) {
            $produkty = $this->Folders->get(1);
            $produkty = $this->Folders->patchEntity($produkty, ['parent_id' => $target]);
            $this->assertFalse($this->Folders->save($produkty), "Moved into $target");
            $this->assertArrayHasKey('parent_id', $produkty->getErrors());
        }
        $this->assertTreeIsValid();
    }

    public function testDeleteMovesContentsToParent(): void
    {
        $glowice = $this->Folders->get(2);
        $this->assertTrue($this->Folders->deleteMovingContents($glowice));

        $this->assertFalse($this->Folders->exists(['id' => 2]));
        // Its file goes to Produkty, its subfolder too - with the files inside.
        $this->assertSame(1, $this->folderOf(4));
        $this->assertSame(1, $this->Folders->get(3)->parent_id);
        $this->assertSame(3, $this->folderOf(5));
        $this->assertSame(3, $this->folderOf(6));
        $this->assertSame(6, $this->Attachments->find()->count(), 'No attachment deleted');
        $this->assertTreeIsValid();
    }

    public function testDeleteTopLevelFolderMovesContentsToRoot(): void
    {
        $this->assertTrue($this->Folders->deleteMovingContents($this->Folders->get(1)));

        $this->assertNull($this->folderOf(3));
        $this->assertNull($this->Folders->get(2)->parent_id);
        $this->assertNull($this->Folders->get(4)->parent_id);
        $this->assertSame(2, $this->folderOf(4));
        $this->assertTreeIsValid();
    }

    public function testDeleteRenamesSubfolderWhoseNameIsTaken(): void
    {
        // Produkty/Strony would clash with the root's Strony once moved up.
        $this->Folders->saveOrFail($this->Folders->newEntity(['name' => 'Strony', 'parent_id' => 1]));

        $this->assertTrue($this->Folders->deleteMovingContents($this->Folders->get(1)));

        $rootNames = $this->Folders->find()
            ->where(['parent_id IS' => null])
            ->all()
            ->extract('name')
            ->toList();
        sort($rootNames);
        $this->assertSame(['Aparaty', 'Głowice', 'Strony', 'Strony (2)'], $rootNames);
        $this->assertTreeIsValid();
    }

    public function testTreeCounts(): void
    {
        $tree = $this->Folders->tree();

        $this->assertSame(2, $tree['rootCount']);
        $byName = [];
        $walk = function ($folders) use (&$walk, &$byName) {
            foreach ($folders as $folder) {
                $byName[$folder->name] = $folder->attachment_count;
                $walk($folder->children ?? []);
            }
        };
        $walk($tree['folders']);
        $this->assertSame(
            ['Produkty' => 1, 'Aparaty' => 0, 'Głowice' => 1, 'Mindray' => 2, 'Strony' => 0],
            $byName
        );
        $this->assertSame(['Produkty', 'Strony'], array_map(fn($f) => $f->name, $tree['folders']));
    }

    public function testOptionsExcludeSubtree(): void
    {
        $this->assertCount(5, $this->Folders->options());
        // Głowice and Mindray are not valid targets when moving Głowice.
        $this->assertSame([1, 4, 5], array_keys($this->Folders->options(2)));
    }

    public function testNormalizeId(): void
    {
        $this->assertNull(AttachmentFoldersTable::normalizeId(null));
        $this->assertNull(AttachmentFoldersTable::normalizeId(''));
        $this->assertNull(AttachmentFoldersTable::normalizeId('root'));
        $this->assertSame(3, AttachmentFoldersTable::normalizeId('3'));
        $this->assertSame(3, AttachmentFoldersTable::normalizeId(3));
        $this->assertNull(AttachmentFoldersTable::normalizeId(['3']));
        $this->assertNull(AttachmentFoldersTable::normalizeId('3abc'));
        $this->assertNull(AttachmentFoldersTable::normalizeId('0'));
    }

    public function testFindInFolder(): void
    {
        $ids = fn(?int $folder) => $this->Attachments->find('inFolder', folder: $folder)
            ->orderBy(['Attachments.id' => 'ASC'])
            ->all()
            ->extract('id')
            ->toList();

        $this->assertSame([1, 2], $ids(null));
        $this->assertSame([5, 6], $ids(3));
        $this->assertSame([], $ids(4));
    }

    public function testMoveAttachmentsToFolder(): void
    {
        $this->assertSame(2, $this->Attachments->moveToFolder([1, 2], 4));
        $this->assertSame(4, $this->folderOf(1));
        $this->assertSame(4, $this->folderOf(2));

        $this->assertSame(1, $this->Attachments->moveToFolder(['5'], null));
        $this->assertNull($this->folderOf(5));
        $this->assertSame(3, $this->folderOf(6), 'Unselected attachments stay');
    }

    public function testMoveAttachmentsToMissingFolderFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Attachments->moveToFolder([1], 999);
    }

    public function testAttachmentFolderMustExist(): void
    {
        $attachment = $this->Attachments->get(1);
        $attachment = $this->Attachments->patchEntity($attachment, ['folder_id' => 999]);
        $this->assertFalse($this->Attachments->save($attachment));

        $attachment = $this->Attachments->patchEntity($attachment, ['folder_id' => 5]);
        $this->assertNotFalse($this->Attachments->save($attachment));
        $this->assertSame(5, $this->folderOf(1));
    }
}
