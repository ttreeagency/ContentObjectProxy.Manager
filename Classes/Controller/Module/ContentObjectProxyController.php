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

use Ttree\ContentObjectProxy\Manager\Domain\Model\BatchTaskInterface;
use Ttree\ContentObjectProxy\Manager\Domain\Model\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Service\TaskService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Service\UserService;
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
        $this->view->assign('tasks', $this->taskService->getAll());
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
        if ($taskObject instanceof BatchTaskInterface) {
            $this->forward('executeBatchTask', null, null, [
                'currentEntity' => $currentEntity,
                'task' => $task
            ]);
        } elseif ($taskObject instanceof EntityBasedTaskInterface) {
            $this->forward('executeEntityBasedTask', null, null, [
                'currentEntity' => $currentEntity,
                'task' => $task
            ]);
        }
    }

    /**
     * @param string $currentEntity
     * @param string $task
     */
    public function executeBatchTaskAction($currentEntity, $task)
    {
        $taskObject = $this->taskService->getByIdentifier($task);
        $context = $this->createContentContext('live');
        $this->view->assignMultiple($taskObject->execute($currentEntity, $context, $this));
        $this->addFlashMessage('Task "%s" excuted with sucess', '', Message::SEVERITY_OK, [$taskObject->getLabel()]);
    }

    /**
     * @param string $currentEntity
     * @param string $task
     */
    public function executeEntityBasedTaskAction($currentEntity, $task)
    {
        $taskObject = $this->taskService->getByIdentifier($task);
        $context = $this->createContentContext('live');
        $this->view->assignMultiple($taskObject->execute($currentEntity, $context, $this));
        $this->addFlashMessage('Task "%s" excuted with sucess', '', Message::SEVERITY_OK, [$taskObject->getLabel()]);
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
