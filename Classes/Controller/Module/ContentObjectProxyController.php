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

use Ttree\ContentObjectProxy\Manager\Domain\Service\ContentProxyableEntityService;
use Ttree\ContentObjectProxy\Manager\Service\TaskService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Service\UserService;

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
     * @var TaskService
     * @Flow\Inject
     */
    protected $taskService;

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
        $this->view->assign('tasks', $this->taskService->getAllBatchTasks());
    }

    /**
     * Dashboard Action
     * @param string $currentEntity
     * @param string $task
     */
    public function executeAction($currentEntity, $task)
    {
        $this->assignAvailableEntities($currentEntity);

        $taskObject = $this->taskService->getByIdentifier($task);
        $context = $this->createContentContext($this->userService->getPersonalWorkspaceName());

        $result = $taskObject->execute($currentEntity, $context, $this);

        $this->view->assignMultiple($result);

        $this->view->assign('actions', $this->taskService->getAllEntityBasedTasks());

        $this->addFlashMessage('Task "%s" excuted with sucess', '', Message::SEVERITY_OK, [$taskObject->getLabel()]);
    }

    /**
     * @param string $currentEntity
     * @param string $identifier
     * @param array $options
     */
    public function wizardAction($currentEntity, $identifier, array $options = [])
    {

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
                'label' => isset($entity['label']) ? $entity['label'] : $className,
                'className' => $className,
                'current' => $className === $currentEntity
            ];
        }, $entities);

        usort($availableEntities, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        $this->view->assignMultiple([
            'entities' => $availableEntities,
            'currentEntity' => $currentEntity
        ]);
    }
}
