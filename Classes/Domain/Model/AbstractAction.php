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
use Webmozart\Assert\Assert;

/**
 * AbstractAction
 */
abstract class AbstractAction implements ActionInterface
{
    /**
     * @var string
     */
    protected $icon = 'icon-check-square-o';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param NodeInterface $node
     * @param string $message
     * @param array $options
     * @param string $icon
     */
    public function __construct(NodeInterface $node = null, $message, array $options = [], $icon = null)
    {
        Assert::string($message);
        $this->message = $message;
        Assert::nullOrIsInstanceOf($node, NodeInterface::class);
        $this->node = $node;
        $this->options = $options;
        Assert::nullOrString($icon);
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
