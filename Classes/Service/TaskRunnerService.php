<?php
namespace Ttree\ContentObjectProxy\Manager\Service;

/*
 * This file is part of the Ttree.ContentObjectProxy.Manager package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionInterface;
use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionStack;
use Ttree\ContentObjectProxy\Manager\Exception;
use TYPO3\Flow\Annotations as Flow;

/**
 * TaskService
 *
 * @Flow\Scope("singleton")
 */
class TaskRunnerService
{
    /**
     * @param ActionStack $stack
     * @throws Exception
     */
    public function apply(ActionStack $stack)
    {
        if ($stack->hasBlockers()) {
            throw new Exception('Unable to run a stack that contains blocker', 1469643388);
        }
        /** @var ActionInterface $action */
        foreach ($stack->getActions() as $action) {
            $action->apply();
        }
    }
}
