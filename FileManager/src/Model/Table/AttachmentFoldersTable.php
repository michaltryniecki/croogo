<?php

namespace Croogo\FileManager\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Croogo\Core\Model\Table\CroogoTable;
use Croogo\FileManager\Model\Entity\AttachmentFolder;

/**
 * Logical folders for the attachment library.
 *
 * Folders exist only in the database. Files stay wherever the storage handler
 * wrote them, so moving an attachment or a whole folder never changes a URL.
 *
 * @property \Croogo\FileManager\Model\Table\AttachmentsTable $Attachments
 * @property \Croogo\FileManager\Model\Table\AttachmentFoldersTable $ParentFolders
 * @property \Croogo\FileManager\Model\Table\AttachmentFoldersTable $ChildFolders
 */
class AttachmentFoldersTable extends CroogoTable
{
    public function initialize(array $config): void
    {
        $this->setTable('attachment_folders');
        $this->setDisplayField('name');
        $this->setEntityClass(AttachmentFolder::class);

        $this->addBehavior('Tree');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Croogo/Core.Trackable');

        $this->belongsTo('ParentFolders', [
            'className' => 'Croogo/FileManager.AttachmentFolders',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildFolders', [
            'className' => 'Croogo/FileManager.AttachmentFolders',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Attachments', [
            'className' => 'Croogo/FileManager.Attachments',
            'foreignKey' => 'folder_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notBlank('name', __d('croogo', 'Folder name cannot be empty.'))
            ->maxLength('name', 150)
            ->add('name', 'noSlash', [
                // The breadcrumbs render the path joined with "/", so a slash in
                // a name would read as a folder that does not exist.
                'rule' => fn($value) => !str_contains((string)$value, '/'),
                'message' => __d('croogo', 'Folder name cannot contain "/".'),
            ]);

        $validator
            ->integer('parent_id')
            ->allowEmptyString('parent_id');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // allowMultipleNulls off: two root folders called "Produkty" are just as
        // confusing as two subfolders with the same name.
        $rules->add($rules->isUnique(['parent_id', 'name'], [
            'allowMultipleNulls' => false,
            'message' => __d('croogo', 'A folder with this name already exists here.'),
        ]), ['errorField' => 'name']);

        $rules->add($rules->existsIn('parent_id', 'ParentFolders', [
            'allowNullableNulls' => true,
            'message' => __d('croogo', 'The parent folder does not exist.'),
        ]));

        // TreeBehavior throws on this; a rule turns it into a form error.
        $rules->add(function (EntityInterface $entity) {
            return !$this->isOwnDescendant($entity);
        }, 'notIntoItself', [
            'errorField' => 'parent_id',
            'message' => __d('croogo', 'A folder cannot be moved into itself or one of its subfolders.'),
        ]);

        return $rules;
    }

    /**
     * Trim before validation, so " Produkty" cannot slip past the uniqueness rule.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isDirty('name')) {
            $entity->set('slug', mb_strtolower(Text::slug((string)$entity->get('name'))));
        }
    }

    /**
     * Whether the entity's new parent is the folder itself or sits below it.
     */
    protected function isOwnDescendant(EntityInterface $entity): bool
    {
        $parentId = $entity->get('parent_id');
        if ($entity->isNew() || $parentId === null || $parentId === '') {
            return false;
        }
        if ((int)$parentId === (int)$entity->get('id')) {
            return true;
        }

        $current = $this->find()
            ->select(['lft', 'rght'])
            ->where([$this->aliasField('id') => $entity->get('id')])
            ->first();
        $parent = $this->find()
            ->select(['lft'])
            ->where([$this->aliasField('id') => $parentId])
            ->first();
        if (!$current || !$parent) {
            return false;
        }

        return $parent->lft > $current->lft && $parent->lft < $current->rght;
    }

    /**
     * Normalise a folder id coming from a request; anything but a positive id means the root.
     */
    public static function normalizeId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_string($value) && ctype_digit($value) && (int)$value > 0) {
            return (int)$value;
        }

        return null;
    }

    /**
     * The whole folder tree with the number of attachments directly in each folder.
     *
     * @return array{folders: array<\Croogo\FileManager\Model\Entity\AttachmentFolder>, rootCount: int}
     */
    public function tree(): array
    {
        $counts = $this->Attachments->find()
            ->select([
                'folder_id' => $this->Attachments->aliasField('folder_id'),
                'total' => $this->Attachments->find()->func()->count('*'),
            ])
            ->groupBy([$this->Attachments->aliasField('folder_id')])
            ->disableHydration()
            ->all()
            ->combine(fn($row) => (string)$row['folder_id'], 'total')
            ->toArray();

        $folders = $this->find('threaded')
            ->orderBy([$this->aliasField('name') => 'ASC'])
            ->formatResults(function ($results) use ($counts) {
                return $results->map(function ($folder) use ($counts) {
                    $this->applyCounts($folder, $counts);

                    return $folder;
                });
            })
            ->toArray();

        return [
            'folders' => $folders,
            'rootCount' => (int)($counts[''] ?? 0),
        ];
    }

    protected function applyCounts(AttachmentFolder $folder, array $counts): void
    {
        $folder->set('attachment_count', (int)($counts[(string)$folder->id] ?? 0));
        foreach ($folder->children ?? [] as $child) {
            $this->applyCounts($child, $counts);
        }
    }

    /**
     * Folders from the root down to (and including) $id, for breadcrumbs.
     *
     * @return array<\Croogo\FileManager\Model\Entity\AttachmentFolder>
     */
    public function pathTo(?int $id): array
    {
        if ($id === null) {
            return [];
        }

        return $this->find('path', for: $id)->all()->toList();
    }

    /**
     * Flat `id => indented name` list for selects.
     *
     * @param int|null $excludeId Folder whose subtree must not be offered (moving a folder)
     */
    public function options(?int $excludeId = null): array
    {
        $query = $this->find('treeList', spacer: '— ', valuePath: 'name');
        if ($excludeId !== null) {
            $excluded = $this->find()
                ->select(['lft', 'rght'])
                ->where([$this->aliasField('id') => $excludeId])
                ->first();
            if ($excluded) {
                $query->where(['NOT' => [
                    $this->aliasField('lft') . ' >=' => $excluded->lft,
                    $this->aliasField('rght') . ' <=' => $excluded->rght,
                ]]);
            }
        }

        return $query->toArray();
    }

    /**
     * Delete a folder without deleting anything in it.
     *
     * Attachments and subfolders are moved up to the folder's parent (the root
     * for a top-level folder) and only then is the folder removed. A subfolder
     * whose name is already taken in the parent gets a numeric suffix rather
     * than failing the whole delete.
     */
    public function deleteMovingContents(AttachmentFolder $folder): bool
    {
        return (bool)$this->getConnection()->transactional(function () use ($folder) {
            $parentId = $folder->parent_id;

            $this->Attachments->updateAll(
                ['folder_id' => $parentId],
                ['folder_id' => $folder->id]
            );

            $childIds = $this->find()
                ->select(['id'])
                ->where([$this->aliasField('parent_id') => $folder->id])
                ->orderBy([$this->aliasField('lft') => 'ASC'])
                ->all()
                ->extract('id')
                ->toList();
            foreach ($childIds as $childId) {
                // Re-read every time: each move renumbers lft/rght, and
                // TreeBehavior moves a node using the values on the entity.
                $child = $this->get($childId);
                $child->parent_id = $parentId;
                $child->name = $this->availableName($child->name, $parentId, $child->id);
                if (!$this->save($child)) {
                    return false;
                }
            }

            // Moving the children shifted this node's lft/rght; TreeBehavior
            // deletes by that range, so work from fresh values.
            $fresh = $this->get($folder->id);

            return $this->delete($fresh);
        });
    }

    /**
     * $name, or "$name (2)", "$name (3)"... whichever is free under $parentId.
     */
    public function availableName(string $name, ?int $parentId, ?int $excludeId = null): string
    {
        $taken = $this->find()
            ->select(['name'])
            ->where([$this->aliasField('parent_id') . ' IS' => $parentId])
            ->where($excludeId !== null ? [$this->aliasField('id') . ' !=' => $excludeId] : [])
            ->all()
            ->extract(fn($row) => mb_strtolower($row->name))
            ->toList();

        $candidate = $name;
        $i = 2;
        while (in_array(mb_strtolower($candidate), $taken, true)) {
            $candidate = sprintf('%s (%d)', $name, $i++);
        }

        return $candidate;
    }
}
