<?php

namespace Croogo\FileManager\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Croogo\Core\Croogo;
use Croogo\Core\Model\Table\CroogoTable;
use Psr\Http\Message\UploadedFileInterface;

class AssetsTable extends CroogoTable
{

    public $validate = [
        'file' => 'checkFileUpload'
    ];

    public function initialize(array $config): void
    {
        $this->setTable('assets');

        $this->hasMany('AssetUsages', [
            'className' => 'Croogo/FileManager.AssetUsages',
            'dependent' => true,
        ]);

        $this->belongsTo('Attachments', [
            'className' => 'Croogo/FileManager.Attachments',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                $this->aliasField('model') => 'Attachments',
            ],
        ]);

        $this->addBehavior('CounterCache', [
            'Attachments' => [
                'asset_count' => [
                    $this->aliasField('model') => 'Attachments',
                ],
            ],
        ]);
        $this->addBehavior('Timestamp');
        $this->addBehavior('Search.Search');
        $this->addBehavior('Croogo/Core.Trackable');
    }

    public function validationDefault(Validator $validator): \Cake\Validation\Validator
    {
        $validator
            ->requirePresence('adapter', 'create');

        return $validator;
    }

    /**
     * Cake 5 hands uploads over as UploadedFileInterface objects, while the storage
     * handlers, checkFileUpload() and AttachmentsTable::beforeSave() all read the
     * legacy $_FILES array (`name`, `tmp_name`, `error`...). Convert at the single
     * point every upload passes through instead of teaching each reader both shapes.
     */
    public function beforeMarshal(\Cake\Event\EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFileInterface) {
            $data['file'] = static::uploadToArray($data['file']);
        }
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file.
     * @return array The file in the $_FILES array shape.
     */
    public static function uploadToArray(UploadedFileInterface $file): array
    {
        $tmpName = '';
        if ($file->getError() === UPLOAD_ERR_OK) {
            $tmpName = (string)$file->getStream()->getMetadata('uri');
        }

        return [
            'name' => (string)$file->getClientFilename(),
            'type' => (string)$file->getClientMediaType(),
            'tmp_name' => $tmpName,
            'error' => $file->getError(),
            'size' => (int)$file->getSize(),
        ];
    }

    public function beforeSave(\Cake\Event\EventInterface $event, EntityInterface $entity, ?ArrayObject $options = null)
    {
        $adapter = $entity->get('adapter');
        if (!$entity->filename) {
            $entity->filename = '';
        }
        if (!$entity->path) {
            $entity->path = '';
        }
        $Event = Croogo::dispatchEvent('FileStorage.beforeSave', $this, [
            'record' => $entity,
            'adapter' => $adapter,
        ]);
        if ($Event->isStopped()) {
            return false;
        }

        return true;
    }

    public function beforeDelete(\Cake\Event\EventInterface $event, EntityInterface $entity, ?ArrayObject $options = null)
    {
        $Event = Croogo::dispatchEvent('FileStorage.beforeDelete', $this, [
            'record' => $entity,
        ]);
        if ($Event->isStopped()) {
            return false;
        }

        return true;
    }

    public function checkFileUpload($check)
    {
        switch ($check['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            case UPLOAD_ERR_OK:
                return true;
        }
    }
}
