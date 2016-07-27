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

use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionStack;
use Ttree\ContentObjectProxy\Manager\Domain\Service\ContentProxyableEntityService;
use Ttree\ContentObjectProxy\Manager\InvalidArgumentException;
use Ttree\ContentObjectProxy\Manager\Service\TaskRunnerService;
use Ttree\ContentObjectProxy\Manager\Service\TaskService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Reflection\ObjectAccess;
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
     * @var TaskRunnerService
     * @Flow\Inject
     */
    protected $taskRunnerService;

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

        $taskObject = $this->taskService->getBatchTaskByIdentifier($task);
        $context = $this->createContentContext($this->userService->getPersonalWorkspaceName());

        $result = $taskObject->execute($currentEntity, $context, $this);

        $this->view->assignMultiple($result);

        $this->view->assign('actions', $this->taskService->getAllEntityBasedTasks());

        $this->addFlashMessage('Task "%s" executed with sucess', '', Message::SEVERITY_OK, [$taskObject->getLabel()]);
    }

    /**
     * @param string $currentEntity
     * @param string $currentAction
     * @param string $identifier
     * @param string $currentLabel
     */
    public function wizardAction($currentEntity, $currentAction, $identifier, $currentLabel)
    {
        $entity = $this->persistenceManager->getObjectByIdentifier($identifier, $currentEntity);

        $label = $this->taskService->getActionLabel($currentEntity, $currentAction);
        $wizard = $this->taskService->getActionWizard($currentEntity, $currentAction);
        $options = $this->taskService->getActionOptions($currentEntity, $currentAction);

        if (isset($options['editableProperties'])) {
            $editableProperties = array_map(function ($propertyName) use ($entity) {
                return [
                    'label' => ucfirst($propertyName),
                    'name' => $propertyName,
                    'value' => ObjectAccess::getProperty($entity, $propertyName)
                ];
            }, $options['editableProperties']);
            $this->view->assign('editableProperties', $editableProperties);
        }

        $this->view->assignMultiple([
            'currentEntity' => $currentEntity,
            'currentAction' => $currentAction,
            'identifier' => $identifier,
            'options' => $options,
            'entity' => $entity,
            'wizard' => $wizard,
            'label' => $label,
            'currentLabel' => $currentLabel,
        ]);
    }

    /**
     * @return void
     */
    protected function initializeRunAction()
    {
        $data = $this->request->getArgument('data');
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (is_array($data)) {
                $this->request->setArgument('data', $data);
            }
        }
    }

    /**
     * @param string $currentEntity
     * @param string $currentAction
     * @param string $identifier
     * @param string $currentLabel
     * @param array $data
     * @param boolean $apply
     */
    public function runAction($currentEntity, $currentAction, $identifier, $currentLabel, array $data = [], $apply = false)
    {
        $options = $this->taskService->getActionOptions($currentEntity, $currentAction);
        $taskObject = $this->taskService->getEntityBasedTaskByIdentifier($currentAction);

        $data = array_map('trim', $data);
        $validRequest = true;
        if (isset($options['uniqueProperty'])) {
            $query = $this->persistenceManager->createQueryForType($currentEntity);
            $nextValue = $data[$options['uniqueProperty']];
            $query->matching($query->equals($options['uniqueProperty'], $nextValue));
            if ($query->count() > 0) {
                $this->addFlashMessage('Property "%s" must be unique, value "%s" is used by a other entity', 'Task failed', Message::SEVERITY_ERROR, [
                    $options['uniqueProperty'],
                    $nextValue
                ]);
                $validRequest = false;
            }
        }

        if ($validRequest) {
            try {
                $entity = $this->persistenceManager->getObjectByIdentifier($identifier, $currentEntity);
                $result = $taskObject->execute($entity, $data, $this->createContentContext($this->userService->getPersonalWorkspaceName()), $this);
                $processedEntity['nodes'] = $result;
                if (!$result instanceof ActionStack) {
                    $this->forward('index', null, null, ['currentEntity' => $currentEntity]);
                } else {
                    $blocked = $result->hasBlockers();
                    if ($blocked) {
                        $this->addFlashMessage('Task "%s" is blocked, check the blockers bellow, solve them and reload this page', '', Message::SEVERITY_WARNING, [$taskObject->getLabel()]);
                    }
                    $this->view->assignMultiple([
                        'actionStack' => $result,
                        'currentEntity' => $currentEntity,
                        'currentAction' => $currentAction,
                        'currentActionLabel' => $taskObject->getLabel(),
                        'currentLabel' => $currentLabel,
                        'identifier' => $identifier,
                        'blocked' => $blocked,
                        'data' => json_encode($data),
                    ]);
                    if ($apply === true) {
                        try {
                            $this->taskRunnerService->apply($result);
                            $this->addFlashMessage('Action plan executed', '', Message::SEVERITY_OK);
                            $this->persistenceManager->persistAll();
                            $result = $taskObject->execute($entity, $data, $this->createContentContext($this->userService->getPersonalWorkspaceName()), $this);
                            $this->view->assign('actionStack', $result);
                        } catch (\Exception $exception) {
                            $this->addFlashMessage('Action plan failed with message: ' . $exception->getMessage(), '', Message::SEVERITY_ERROR);
                        }
                    }
                }
            } catch (InvalidArgumentException $exception) {
                $this->addFlashMessage('Task "%s" failed with message: ' . $exception->getMessage(), '', Message::SEVERITY_ERROR, [$taskObject->getLabel()]);
                $this->forward('wizard', null, null, [
                    'currentEntity' => $currentEntity,
                    'currentAction' => $currentAction,
                    'currentLabel' => $currentLabel,
                    'identifier' => $identifier
                ]);
            }
        } else {
            $this->addFlashMessage('Task "%s" is not valid', '', Message::SEVERITY_ERROR, [$taskObject->getLabel()]);
        }
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
