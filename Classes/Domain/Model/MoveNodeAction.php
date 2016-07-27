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
 * MoveNodeAction
 */
class MoveNodeAction extends AbstractAction
{
    /**
     * @var string
     */
    protected $icon = 'icon-cut';

    /**
     * @param NodeInterface $node
     * @param string $message
     * @param array $options
     * @param null|string $icon
     */
    public function __construct(NodeInterface $node, $message, array $options, $icon = null)
    {
        Assert::keyExists($options, 'target');
        Assert::isInstanceOf($options['target'], NodeInterface::class);

        parent::__construct($node, $message, $options, $icon);
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->node->moveInto($this->getTargetNode());
    }

    /**
     * @return NodeInterface
     */
    protected function getTargetNode()
    {
        return $this->options['target'];
    }
}
