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
     * @var integer
     */
    protected $actionCounter = 0;

    /**
     * @var array
     */
    protected $blockers = [];

    /**
     * @param ActionInterface $action
     * @return $this
     */
    public function addAction(ActionInterface $action)
    {
        $this->actions[] = $action;
        $this->actionCounter++;
        return $this;
    }

    /**
     * @param Blocker $blocker
     * @return $this
     */
    public function addBlocker(Blocker $blocker)
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
     * @return integer
     */
    public function countActions()
    {
        return $this->actionCounter;
    }

    /**
     * @return array
     */
    public function getBlockers()
    {
        $blockers = $this->blockers;
        /**
         * @var integer $key
         * @var Blocker $blocker
         */
        foreach ($blockers as $key => $blocker) {
            $label[$key] = strtolower($blocker->getNode()->getLabel());
            $nodeType[$key] = $blocker->getNodeType();
        }
        array_multisort($nodeType, SORT_NATURAL, $label, SORT_NATURAL, $blockers);
        return $blockers;
    }

}
