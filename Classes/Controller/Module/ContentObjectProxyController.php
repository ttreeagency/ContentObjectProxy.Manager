<?php
namespace Ttree\ContentObjectProxy\Manager\Controller\Module;

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
use TYPO3\Flow\Error\Message;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContentProxyableEntityService;

/**
 * ContentObjectProxyController
 */
class ContentObjectProxyController extends AbstractModuleController
{
    use CreateContentContextTrait;

    /**
     * @var ContentProxyableEntityService
     * @Flow\Inject
     */
    protected $contentProxyProxyableEntityService;

    /**
     * @var ContentContextFactory
     * @Flow\Inject
     */
    protected $contextFactory;

    /**
     * @var UserService
     * @Flow\Inject
     */
    protected $userService;

    /**
     * Dashboard Action
     * @param string $currentEntity
     */
    public function indexAction($currentEntity = null)
    {
        $this->assignAvailableEntities($currentEntity);
    }

    /**
     * @param string $currentEntity
     */
    public function syncAction($currentEntity)
    {
        $this->assignAvailableEntities($currentEntity);

        $processedEntities = [];
        $context = $this->createContentContext('live');
        $this->contentProxyProxyableEntityService->synchronizeAll($currentEntity, $context, function (NodeInterface $node, $entity, $updated) use (&$processedEntities) {
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

        $this->addFlashMessage('%d entities of type "%s" synchronized', '', Message::SEVERITY_OK, [
            count($processedEntities),
            $currentEntity
        ]);
        if ($updateNodesCounter > 0) {
            $this->addFlashMessage('%d/%d nodes updated', '', Message::SEVERITY_NOTICE, [
                $updateNodesCounter,
                $processedNodesCounter
            ]);
        }

        $this->view->assignMultiple([
            'updateNodesCounter' => $updateNodesCounter,
            'processedNodesCounter' => $processedNodesCounter,
            'currentEntity' => $currentEntity,
            'processedEntities' => $processedEntities,
        ]);
    }

    /**
     * @param string $currentEntity
     */
    public function mergeAction($currentEntity)
    {
        $this->addFlashMessage('Entity "%s" merged', '', Message::SEVERITY_OK, [$currentEntity]);

        $this->redirect('index', null, null, ['currentEntity' => $currentEntity]);
    }

    /**
     * @param string $currentEntity
     */
    public function removeAction($currentEntity)
    {
        $this->addFlashMessage('Entity "%s" removed', '', Message::SEVERITY_OK, [$currentEntity]);

        $this->redirect('index', null, null, ['currentEntity' => $currentEntity]);
    }

    /**
     * @param string $currentEntity
     */
    public function renameAction($currentEntity)
    {
        $this->addFlashMessage('Entity "%s" renamed', '', Message::SEVERITY_OK, [$currentEntity]);

        $this->redirect('index', null, null, ['currentEntity' => $currentEntity]);
    }

    /**
     * @param $currentEntity
     */
    protected function assignAvailableEntities($currentEntity)
    {
        $entities = $this->contentProxyProxyableEntityService->getEntities();
        $availableEntities = array_map(function ($entity) use ($currentEntity) {
            $className = $entity['className'];
            return [
                'name' => $className,
                'current' => $className === $currentEntity
            ];
        }, $entities);

        $this->view->assignMultiple([
            'entities' => $availableEntities,
            'currentEntity' => $currentEntity
        ]);
    }
}
