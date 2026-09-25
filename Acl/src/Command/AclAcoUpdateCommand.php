<?php
declare(strict_types=1);

namespace Croogo\Acl\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Croogo\Acl\AclGenerator;

/**
 * Port AclExtrasShell::aco_update (Cake 5: Shell -> Command).
 *
 * Adds ACOs for controllers and actions of the app and the loaded plugins.
 * Usage: bin/cake acl aco_update [-p Plugin]
 */
class AclAcoUpdateCommand extends Command
{
    public static function defaultName(): string
    {
        return 'acl aco_update';
    }

    public static function getDescription(): string
    {
        return __d('croogo', 'Add ACOs for new controllers and actions');
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(static::getDescription())
            ->addOption('plugin', [
                'short' => 'p',
                'help' => __d('croogo', 'Limit to one loaded plugin'),
            ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $generator = new AclGenerator();
        $generator->setShell($io);

        $params = array_filter(['plugin' => $args->getOption('plugin')]);

        return $this->updateAcos($generator, $params) ? static::CODE_SUCCESS : static::CODE_ERROR;
    }

    /**
     * @param \Croogo\Acl\AclGenerator $generator Generator writing to the command output
     * @param array $params AclExtras parameters
     * @return bool
     */
    protected function updateAcos(AclGenerator $generator, array $params): bool
    {
        return $generator->acoUpdate($params) !== false;
    }
}
