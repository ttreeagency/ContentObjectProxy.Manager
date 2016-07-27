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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Blocker
 */
class Blocker
{
    /**
     * @var string
     */
    protected $icon = 'icon-exclamation-circle';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @param NodeInterface $node
     * @param string $message
     * @param string $icon
     */
    public function __construct(NodeInterface $node = null, $message, $icon = null)
    {
        $this->message = $message;
        $this->node = $node;
        $this->icon = $icon ?: $this->icon;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return string
     */
    public function getNodeType()
    {
        return $this->node ? $this->node->getNodeType()->getLabel() : null;
    }
}
