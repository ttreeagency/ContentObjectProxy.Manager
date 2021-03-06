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

use Ttree\ContentObjectProxy\Manager\InvalidArgumentException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use Webmozart\Assert\Assert;

/**
 * AddReferenceAction
 */
class AddReferenceAction extends AbstractAction
{
    /**
     * @param NodeInterface $node
     * @param string $message
     * @param array $options
     * @param null|string $icon
     * @throws InvalidArgumentException
     */
    public function __construct(NodeInterface $node, $message, array $options, $icon = null)
    {
        Assert::keyExists($options, 'propertyName');
        Assert::keyExists($options, 'propertyValue');
        Assert::string($options['propertyName']);
        Assert::isInstanceOf($options['propertyValue'], NodeInterface::class);

        parent::__construct($node, $message, $options, $icon);

        $propertyName = $this->getPropertyName();
        $propertyType = $this->node->getNodeType()->getConfiguration(implode('.', ['properties', $propertyName, 'type']));
        if (!$this->node->hasProperty($propertyName) || !in_array($propertyType, ['reference', 'references'])
        ) {
            $message = vsprintf('Current node %s has no property "%s" (%s) or property type (%s) is not of type reference(s)', [
                $this->node->getIdentifier(),
                $propertyName,
                $this->node->getNodeType()->getName(),
                $propertyType,
            ]);
            throw new InvalidArgumentException($message, 1469642194);
        }
    }

    /**
     * @return void
     */
    public function apply()
    {
        $propertyName = $this->getPropertyName();
        $activities = $this->node->getProperty($propertyName);
        array_unshift($activities, $this->options['propertyValue']);
        $this->node->setProperty($propertyName, $activities);

    }

    /**
     * @return string
     */
    protected function getPropertyName()
    {
        return $this->options['propertyName'];
    }
}
