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

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * AbstractAction
 */
interface ActionInterface
{
    /**
     * @return void
     */
    public function apply();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return NodeInterface
     */
    public function getNode();

    /**
     * @return array
     */
    public function getOptions();
}
