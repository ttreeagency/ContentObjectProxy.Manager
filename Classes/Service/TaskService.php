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

use Ttree\ContentObjectProxy\Manager\Domain\Model\BatchTaskInterface;
use Ttree\ContentObjectProxy\Manager\Domain\Model\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Domain\Model\TaskInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;

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
     * Return all batch task
     *
     * @return array
     */
    public function getAllBatchTasks()
    {
        return self::getBatchTask($this->objectManager);
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
     * @return BatchTaskInterface|EntityBasedTaskInterface
     * @throws Exception
     */
    public function getByIdentifier($identifier)
    {
        $tasks = self::getBatchTask($this->objectManager);
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
    protected static function getBatchTask(ObjectManagerInterface $objectManager)
    {
        return self::_getTask($objectManager, BatchTaskInterface::class);
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
            /** @var TaskInterface $task */
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
