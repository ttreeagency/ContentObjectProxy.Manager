<?php
namespace Ttree\ContentObjectProxy\Manager\Domain\Service;

/*
 * This file is part of the Ttree.ContentObjectProxy.Manager package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ttree\ContentObjectProxy\Manager\Contrat\LabelInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Arrays;

/**
 * ContentProxyableEntityService
 */
class ContentProxyableEntityService extends \TYPO3\TYPO3CR\Domain\Service\ContentProxyableEntityService
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="types")
     */
    protected $types;

    /**
     * @return array
     */
    public function getEntities()
    {
        $entities = parent::getEntities();
        $entities = array_map(function ($currentEntity) {
            // Get label from entity based on LabelInterface
            $entitesWithLabel = self::getEntitiesWithLabel($this->objectManager);
            $matchingEntity = array_filter($entitesWithLabel, function ($entity) use ($currentEntity) {
                return $entity['className'] === $currentEntity['className'];
            });
            if ($matchingEntity !== []) {
                $matchingEntity = array_pop($matchingEntity);
                $currentEntity['label'] = $matchingEntity['label'];
            }

            // Get label from settings
            $label = Arrays::getValueByPath($this->types, [$currentEntity['className'], 'label']);
            if ($label !== null) {
                $currentEntity['label'] = $label;
            }

            return $currentEntity;
        }, $entities);
        return $entities;
    }

    /**
     * Get all proxyable entities
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function getEntitiesWithLabel($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
        $nodeImplementations = $reflectionService->getAllImplementationClassNamesForInterface(LabelInterface::class);
        return array_map(function ($entity) use ($reflectionService) {
            return [
                'className' => $entity,
                'label' => $entity::getLabel()
            ];
        }, $nodeImplementations);
    }
}
