<?php
namespace Ttree\ContentObjectProxy\Manager\Domain\Model;

/*
 * This file is part of the Ttree.ContentObjectProxy.Manager package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * ActionStack
 */
class ActionStack
{
    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @var array
     */
    protected $blockers = [];

    /**
     * @param array $action
     * @return $this
     */
    public function stackAction(array $action)
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @param array $blocker
     * @return $this
     */
    public function stackBlocker(array $blocker)
    {
        $this->blockers[] = $blocker;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasBlockers()
    {
        return count($this->blockers) > 0;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return array
     */
    public function getBlockers()
    {
        return $this->blockers;
    }

}
