<?php
namespace Ttree\ContentObjectProxy\Manager\Task;

/*
 * This file is part of the Ttree.ContentObjectProxy.Manager package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * ProfileTrait
 */
trait ProfileTrait
{
    /**
     * @param NodeInterface $node
     * @return null|NodeInterface
     */
    protected function getNodeMainActivity(NodeInterface $node)
    {
        $closestActivityQuery = new FlowQuery([$node]);
        /** @var NodeInterface $closestActivity */
        $closestActivity = $closestActivityQuery->closest('[instanceof Ttree.ArchitectesCh:Activity]')->get(0);
        $activities = $node->getProperty('activities');
        if ($closestActivity === null) {
            if (isset($activities[0])) {
                return $activities[0];
            } else {
                return null;
            }
        }
        return $closestActivity;
    }
}
