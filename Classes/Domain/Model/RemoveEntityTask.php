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

use Ttree\ContentObjectProxy\Manager\Controller\Module\ContentObjectProxyController;
use Ttree\ContentObjectProxy\Manager\Exception;
use Ttree\ContentObjectProxy\Manager\InvalidArgumentException;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * RemoveEntityTask
 */
class RemoveEntityTask implements EntityBasedTaskInterface
{
    /**
     * @var NodeDataRepository
     * @Flow\Inject
     */
    protected $nodeDataRepository;

    /**
     * @var NodeFactory
     * @Flow\Inject
     */
    protected $nodeFactory;

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
        return 'Remove';
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return 'icon-trash';
    }

    /**
     * @return string
     */
    public function getButtonClass()
    {
        return 'neos-button neos-button-danger';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Remove entity';
    }

    /**
     * @param object $currentEntity
     * @param array $data
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @param \Closure $callback
     * @return ActionStack
     * @throws InvalidArgumentException
     */
    public function execute($currentEntity, array $data, Context $context, ContentObjectProxyController $controller, \Closure $callback = null)
    {
        if (!isset($data['confirm']) || $data['confirm'] != true) {
            throw new InvalidArgumentException('Missing manual confirmation', 1469373603);
        }
        $identifier = $this->persistenceManager->getIdentifierByObject($currentEntity);
        $nodes = array_map(function ($nodeData) use ($context) {
            return $this->nodeFactory->createFromNodeData($nodeData, $context);
        }, $this->nodeDataRepository->findByContentObjectProxy($identifier, $context->getWorkspace()));

        $actionStack = new ActionStack();
        if (count($nodes) > 0) {
            /** @var NodeInterface $node */
            foreach ($nodes as $node) {
                $query = new FlowQuery([$node]);
                $filteredNodes = $query->find('[instanceof Ttree.ArchitectesCh:BaseEnterprise]')->get();
                if (count($filteredNodes) === 0) {
                    $actionStack->stackAction([
                        'action' => 'removeNode',
                        'message' => vsprintf('Node "%s" can be safely removed, no children nodes', [
                            $node->getLabel()
                        ]),
                        'node' => $node,
                    ]);
                } else {
                    /** @var NodeInterface $filteredNode */
                    foreach ($filteredNodes as $filteredNode) {
                        $this->moveNode($filteredNode, $actionStack);
                    }
                }
            }
        }
        if (!$actionStack->hasBlockers()) {
            $className = TypeHandling::getTypeForValue($currentEntity);
            $actionStack->stackAction([
                'action' => 'removeEntity',
                'message' => vsprintf('Entity %s (%s) can be safely remove, not used in the content repository', [
                    $identifier,
                    $className
                ]),
                'entity' => [
                    'className' => $className,
                    'identifier' => $identifier
                ]
            ]);
        }
        return $actionStack;
    }

    /**
     * @param NodeInterface $node
     * @param ActionStack $actionStack
     * @throws Exception
     */
    protected function moveNode(NodeInterface $node, ActionStack $actionStack)
    {
        if (!$node->getNodeType()->isOfType('Ttree.ArchitectesCh:BaseEnterprise')) {
            throw new Exception('Unable to process nodes of the given type: ' . $node->getNodeType()->getName(), 1469377342);
        }
        $activities = $node->getProperty('activities');
        if (count($activities) < 2) {
            $actionStack->stackBlocker([
                'message' => vsprintf('Not enough references (%d)', [count($activities)]),
                'node' => $node,
            ]);
            return;
        }
        $parentNode = $node->getParent();
        /** @var NodeInterface $currentMainActivity */
        $currentMainActivity = $activities[0];
        /** @var NodeInterface $targetMainActivity */
        $targetMainActivity = $activities[1];
        $targetExistQuery = new FlowQuery([$parentNode]);
        $targetExistQuery = $targetExistQuery->siblings(sprintf('[uriPathSegment="%s"]', $targetMainActivity->getProperty('uriPathSegment')));
        if ($targetExistQuery->get(0) === null) {
            $targetParentNode = null;
            $actionStack->stackAction([
                'action' => 'createActivityNode',
                'message' => vsprintf('Parent node "%s" (%s) need to be create bellow "%s" (%s)', [
                    $targetMainActivity->getLabel(),
                    $targetMainActivity->getProperty('uriPathSegment'),
                    $parentNode->getParent()->getLabel(),
                    $parentNode->getParent()->getProperty('uriPathSegment'),
                ]),
                'node' => $node,
                'target' => $targetMainActivity,
                'parentNode' => $targetParentNode
            ]);
        } else {
            $targetParentNode = $targetExistQuery->get(0);
        }
        $actionStack->stackAction([
            'action' => 'setMainActivity',
            'message' => vsprintf('Main activity can be set to "%s" (%s), previously "%s" (%s)', [
                $targetMainActivity->getLabel(),
                $targetMainActivity->getProperty('uriPathSegment'),
                $currentMainActivity->getLabel(),
                $currentMainActivity->getProperty('uriPathSegment')
            ]),
            'node' => $node,
            'target' => $targetMainActivity,
            'parentNode' => $targetParentNode
        ]);
        $actionStack->stackAction([
            'action' => 'removeReference',
            'message' => vsprintf('Activity reference "%s" can be unset', [
                $currentMainActivity->getLabel()
            ]),
            'node' => $node,
            'propertyName' => 'activities',
            'propertyValue' => $currentMainActivity
        ]);
    }

    /**
     * @param NodeInterface $node
     * @return string
     */
    protected function getNodeLabel(NodeInterface $node)
    {
        return $node->getLabel() . ' ' . $node->getPath() . ' ' . $node->getNodeType()->getName();
    }

}
