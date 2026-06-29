<?php

namespace Croogo\Core\Shell;

use Cake\Console\Shell;

/**
 * Base class for Croogo Shell
 *
 * @package Croogo.Console
 */
class AppShell extends Shell
{

    /**
     * Convenience method for out() that encloses message between <info /> tag
     */
    public function info($message = null, int $newlines = 1, int $level = Shell::NORMAL): ?int
    {
        return $this->out('<info>' . $message . '</info>', $newlines, $level);
    }

    /**
     * Convenience method for out() that encloses message between <warning /> tag
     */
    public function warn($message = null, int $newlines = 1): int
    {
        return (int)$this->out('<warning>' . $message . '</warning>', $newlines, Shell::NORMAL);
    }

    /**
     * Convenience method for out() that encloses message between <success /> tag
     */
    public function success($message = null, int $newlines = 1, int $level = Shell::NORMAL): ?int
    {
        return $this->out('<success>' . $message . '</success>', $newlines, $level);
    }
}
