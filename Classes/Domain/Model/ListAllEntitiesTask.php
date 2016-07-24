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

use Ttree\ContentObjectProxy\Manager\Contract\BatchTaskInterface;
use Ttree\ContentObjectProxy\Manager\Controller\Module\ContentObjectProxyController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Service\ContentProxyableEntityService;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * RefreshAllEntitiesTask
 */
class ListAllEntitiesTask implements BatchTaskInterface
{
    /**
     * @var ContentProxyableEntityService
     * @Flow\Inject
     */
    protected $contentProxyProxyableEntityService;

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
        return 'List all entities';
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return 'icon-list';
    }

    /**
     * @return string
     */
    public function getButtonClass()
    {
        return 'neos-button neos-button-primary';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'List all proxyable properties from doctrine entities to the content repository';
    }

    /**
     * @param string $currentEntity
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @return array
     */
    public function execute($currentEntity, Context $context, ContentObjectProxyController $controller)
    {
        $processedEntities = [];
        foreach ($this->contentProxyProxyableEntityService->findAllByClassName($currentEntity) as $entity) {
            $identifier = $this->persistenceManager->getIdentifierByObject($entity);
            $processedEntities[] = [
                'entity' => $entity,
                'label' => method_exists($entity, '__toString') ? (string)$entity : '[implement __toString method please]',
                'identifier' => $identifier,
            ];
        }

        usort($processedEntities, function($a, $b) {
            return strcasecmp(trim($a['label']), trim($b['label']));
        });

        return [
            'currentEntity' => $currentEntity,
            'processedEntities' => $processedEntities,
        ];
    }
}
