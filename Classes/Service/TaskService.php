<?php
namespace Ttree\ContentObjectProxy\Manager\Service;

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
use Ttree\ContentObjectProxy\Manager\Contract\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Contract\TaskInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Arrays;

/**
 * TaskService
 *
 * @Flow\Scope("singleton")
 */
class TaskService
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="types")
     */
    protected $types;

    /**
     * Return all batch task
     *
     * @return array
     */
    public function getAllBatchTasks()
    {
        return self::getBatchTasks($this->objectManager);
    }

    /**
     * Return all entity based task
     *
     * @return array
     */
    public function getAllEntityBasedTasks()
    {
        return self::getEntityBasedTasks($this->objectManager);
    }

    /**
     * @param string $identifier
     * @return BatchTaskInterface
     * @throws Exception
     */
    public function getBatchTaskByIdentifier($identifier)
    {
        $tasks = self::getBatchTasks($this->objectManager);
        $tasks = array_filter($tasks, function ($task) use ($identifier) {
            return $task['__className'] === $identifier;
        });
        if (count($tasks) === 0) {
            throw new Exception('Invalid task identifier: ' . $identifier, 1468482110);
        }
        return $this->objectManager->get(array_pop($tasks)['__className']);
    }

    /**
     * @param string $identifier
     * @return \Ttree\ContentObjectProxy\Manager\Contract\EntityBasedTaskInterface
     * @throws Exception
     */
    public function getEntityBasedTaskByIdentifier($identifier)
    {
        $tasks = self::getEntityBasedTasks($this->objectManager);
        $tasks = array_filter($tasks, function ($task) use ($identifier) {
            return $task['__className'] === $identifier;
        });
        if (count($tasks) === 0) {
            throw new Exception('Invalid task identifier: ' . $identifier, 1468482110);
        }
        return $this->objectManager->get(array_pop($tasks)['__className']);
    }

    /**
     * Get all tasks
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function getEntityBasedTasks(ObjectManagerInterface $objectManager)
    {
        return self::_getTask($objectManager, EntityBasedTaskInterface::class);
    }

    /**
     * Get all tasks
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function getBatchTasks(ObjectManagerInterface $objectManager)
    {
        return self::_getTask($objectManager, BatchTaskInterface::class);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getLabel($identifier)
    {
        return Arrays::getValueByPath($this->types, [$identifier, 'label']);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getActions($identifier)
    {
        return Arrays::getValueByPath($this->types, [$identifier, 'actions']);
    }

    /**
     * @param string $identifier
     * @param string $actionIdentifier
     * @return string
     */
    public function getActionOptions($identifier, $actionIdentifier)
    {
        $actions = $this->getActions($identifier);
        return Arrays::getValueByPath($actions, [$actionIdentifier, 'options']);
    }

    /**
     * @param string $identifier
     * @param string $actionIdentifier
     * @return string
     */
    public function getActionLabel($identifier, $actionIdentifier)
    {
        $actions = $this->getActions($identifier);
        return (string)Arrays::getValueByPath($actions, [$actionIdentifier, 'label']);
    }

    /**
     * @param string $identifier
     * @param string $actionIdentifier
     * @return string
     */
    public function getActionWizard($identifier, $actionIdentifier)
    {
        $actions = $this->getActions($identifier);
        return (string)Arrays::getValueByPath($actions, [$actionIdentifier, 'wizard']);
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $interface
     * @return array
     */
    protected static function _getTask($objectManager, $interface)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
        $nodeImplementations = $reflectionService->getAllImplementationClassNamesForInterface($interface);
        $result = array_map(function ($entity) use ($reflectionService, $objectManager) {
            /** @var \Ttree\ContentObjectProxy\Manager\Contract\TaskInterface $task */
            $task = $objectManager->get($entity);
            $properties = ObjectAccess::getGettableProperties($task);
            $properties['__className'] = $entity;
            return $properties;
        }, $nodeImplementations);

        usort($result, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return $result;
    }
}
