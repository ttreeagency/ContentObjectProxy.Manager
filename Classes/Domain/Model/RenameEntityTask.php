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

use Ttree\ContentObjectProxy\Manager\Contract\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Controller\Module\ContentObjectProxyController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Service\ContentObjectProxyService;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * RenameEntityTask
 */
class RenameEntityTask implements EntityBasedTaskInterface
{
    /**
     * @var ContentObjectProxyService
     * @Flow\Inject
     */
    protected $contentObjectProxyService;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Rename';
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return 'icon-edit';
    }

    /**
     * @return string
     */
    public function getButtonClass()
    {
        return 'neos-button neos-button-warning';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Rename entity';
    }

    /**
     * @param object $currentEntity
     * @param array $data
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @param \Closure $callback
     * @return ActionStack
     */
    public function execute($currentEntity, array $data, Context $context, ContentObjectProxyController $controller, \Closure $callback = null)
    {
        $this->contentObjectProxyService->inWorkspace($context->getWorkspaceName(), function () use ($currentEntity, $data, $controller) {
            $updated = false;
            foreach ($data as $propertyName => $propertyValue) {
                $currentValue = ObjectAccess::getProperty($currentEntity, $propertyName);
                if ($currentValue !== $propertyValue) {
                    $controller->addFlashMessage('Property "%s" update from "%s" to "%s"', '', Message::SEVERITY_NOTICE, [$propertyName, $currentValue, $propertyValue]);
                    ObjectAccess::setProperty($currentEntity, $propertyName, $propertyValue);
                    $updated = true;
                }
            }
            if ($updated) {
                $this->persistenceManager->update($currentEntity);
                $this->persistenceManager->persistAll();
            }
        });
    }
}
