<?php
namespace Ttree\ContentObjectProxy\Manager\Task;

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
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContentProxyableEntityService;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * RefreshAllEntitiesTask
 */
class RefreshAllEntitiesTask implements BatchTaskInterface
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
        return 'Refresh all entities';
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return 'icon-refresh';
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
        return 'Synchronize all proxyable properties from doctrine entities to the content repository';
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
        $this->contentProxyProxyableEntityService->synchronizeAllByClassName($currentEntity, $context, function (NodeInterface $node, $entity, $updated) use (&$processedEntities) {
            $identifier = $this->persistenceManager->getIdentifierByObject($entity);
            if (!isset($processedEntities[$identifier])) {
                $processedEntities[$identifier] = [
                    'entity' => $entity,
                    'label' => $node->getLabel(),
                    'identifier' => $identifier,
                    'updatedNodes' => [],
                    'nodes' => [],
                ];
            }
            $result = [
                'path' => $node->getPath(),
                'type' => $node->getNodeType()->getName(),
                'identifier' => $node->getIdentifier()
            ];
            if ($updated) {
                $processedEntities[$identifier]['updatedNodes'][] = $result;
            } else {
                $processedEntities[$identifier]['nodes'][] = $result;
            }
        });

        $updateNodesCounter = $processedNodesCounter = 0;

        $processedEntities = array_map(function ($entity) use (&$updateNodesCounter, &$processedNodesCounter) {
            $entity['updateNodesCounter'] = count($entity['updatedNodes']);
            $updateNodesCounter += $entity['updateNodesCounter'];
            $entity['processedNodesCounter'] = count($entity['nodes']);
            $processedNodesCounter += $entity['processedNodesCounter'];
            return $entity;
        }, $processedEntities);

        $processedEntities = array_values($processedEntities);

        usort($processedEntities, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        $controller->addFlashMessage('%d entities of type "%s" synchronized', '', Message::SEVERITY_OK, [
            count($processedEntities),
            $currentEntity
        ]);
        if ($updateNodesCounter > 0) {
            $controller->addFlashMessage('%d/%d nodes updated', '', Message::SEVERITY_NOTICE, [
                $updateNodesCounter,
                $processedNodesCounter
            ]);
        }

        return [
            'updateNodesCounter' => $updateNodesCounter,
            'processedNodesCounter' => $processedNodesCounter,
            'currentEntity' => $currentEntity,
            'processedEntities' => $processedEntities,
        ];
    }
}
