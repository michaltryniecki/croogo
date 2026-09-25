<?php
declare(strict_types=1);

namespace Croogo\Acl\Command;

use Croogo\Acl\AclGenerator;

/**
 * Port AclExtrasShell::aco_sync (Cake 5: Shell -> Command).
 *
 * Like `acl aco_update`, and also removes ACOs of controllers and actions
 * that no longer exist.
 * Usage: bin/cake acl aco_sync [-p Plugin]
 */
class AclAcoSyncCommand extends AclAcoUpdateCommand
{
    public static function defaultName(): string
    {
        return 'acl aco_sync';
    }

    public static function getDescription(): string
    {
        return __d('croogo', 'Add ACOs for new controllers and actions, remove stale ones');
    }

    /**
     * @inheritDoc
     */
    protected function updateAcos(AclGenerator $generator, array $params): bool
    {
        $generator->acoSync($params);

        return true;
    }
}
