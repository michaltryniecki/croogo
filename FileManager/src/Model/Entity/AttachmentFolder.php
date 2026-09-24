<?php

namespace Croogo\FileManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * A logical folder in the attachment library.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rght
 * @property string $name
 * @property string|null $slug
 * @property int|null $attachment_count Direct attachments, set by AttachmentFoldersTable::tree()
 */
class AttachmentFolder extends Entity
{
    protected array $_accessible = [
        'parent_id' => true,
        'name' => true,
    ];
}
