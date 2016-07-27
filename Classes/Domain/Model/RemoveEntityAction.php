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

use Ttree\ContentObjectProxy\Manager\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Webmozart\Assert\Assert;

/**
 * RemoveEntityAction
 */
class RemoveEntityAction extends AbstractAction
{
    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param string $message
     * @param array $options
     * @param null|string $icon
     */
    public function __construct($message, array $options, $icon = null)
    {
        Assert::keyExists($options, 'objectType');
        Assert::keyExists($options, 'identifier');
        Assert::string($options['objectType']);
        Assert::string($options['identifier']);

        parent::__construct(null, $message, $options, $icon);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function apply()
    {
        $entity = $this->persistenceManager->getObjectByIdentifier($this->options['identifier'], $this->options['objectType']);
        if ($entity === null) {
            throw new Exception(sprintf('Entity %s (%s) not found', $this->options['identifier'], $this->options['objectType']), 1469641125);
        }
        $this->persistenceManager->remove($entity);
    }
}
