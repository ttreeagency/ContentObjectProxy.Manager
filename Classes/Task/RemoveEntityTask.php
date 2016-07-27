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

use Ttree\ContentObjectProxy\Manager\Contract\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Controller\Module\ContentObjectProxyController;
use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionStack;
use Ttree\ContentObjectProxy\Manager\Domain\Model\AddReferenceAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\Blocker;
use Ttree\ContentObjectProxy\Manager\Domain\Model\CreateActivityNodeAndMoveAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\MoveNodeAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\RemoveEntityAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\RemoveNodeAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\RemoveReferenceAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\SetMainActivityAction;
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
                    $actionStack->addAction(new RemoveNodeAction(
                            $node,
                            vsprintf('Node "%s" can be safely removed, no children nodes', [$node->getLabel()]))
                    );
                } else {
                    /** @var NodeInterface $filteredNode */
                    foreach ($filteredNodes as $filteredNode) {
                        $this->moveNode($filteredNode, $actionStack);
                    }
                }
            }
        }
        if (!$actionStack->hasBlockers() && $actionStack->countActions() === 0) {
            $className = TypeHandling::getTypeForValue($currentEntity);
            $actionStack->addAction(new RemoveEntityAction(
                vsprintf('Entity %s of type "%s" can be safely remove, not used in the content repository', [
                    $identifier,
                    $className
                ]),
                [
                    'objectType' => $className,
                    'identifier' => $identifier
                ]
            ));
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
            $actionStack->addBlocker(new Blocker($node, vsprintf('Not enough references (%d)', [count($activities)])));
            return;
        }
        $parentNode = $node->getParent();
        /** @var NodeInterface $currentMainActivity */
        $currentMainActivity = $activities[0];
        /** @var NodeInterface $targetMainActivity */
        $targetMainActivity = $activities[1];
        $targetExistQuery = new FlowQuery([$parentNode]);
        $targetExistQuery = $targetExistQuery->siblings(sprintf('[uriPathSegment="%s"]', $targetMainActivity->getProperty('uriPathSegment')));
        /** @var NodeInterface $targetParentNode */
        $targetParentNode = $targetExistQuery->get(0);
        if ($targetParentNode === null) {
            $parentNode = $parentNode->getParent();
            $actionStack->addAction(new CreateActivityNodeAndMoveAction(
                $node,
                vsprintf('Node "%s" (%s) need to be create bellow "%s" (%s), before moving the current node', [
                    $targetMainActivity->getLabel(),
                    $targetMainActivity->getProperty('uriPathSegment'),
                    $parentNode->getLabel(),
                    $parentNode->getProperty('uriPathSegment'),
                ]),
                [
                    'target' => $targetMainActivity,
                    'parentNode' => $parentNode
                ]
            ));
        } else {
            $actionStack->addAction(new MoveNodeAction(
                $node,
                vsprintf('Node "%s" (%s) can be moved bellow "%s" (%s)', [
                    $node->getLabel(),
                    $node->getProperty('uriPathSegment'),
                    $targetParentNode->getLabel(),
                    $targetParentNode->getProperty('uriPathSegment'),
                ]),
                [
                    'target' => $targetParentNode
                ]
            ));
        }
        // todo check if it's egal to the current activity !!!!
        $mainActivity = $this->getNodeMainActivity($node);
        $actionStack->addAction(new RemoveReferenceAction(
            $node,
            vsprintf('Activity reference "%s" can be unset', [
                $currentMainActivity->getLabel()
            ]),
            [
                'propertyName' => 'activities',
                'propertyValue' => $mainActivity
            ]
        ));
        $actionStack->addAction(new AddReferenceAction(
            $node,
            vsprintf('Main activity can be set to "%s" (%s)', [
                $targetMainActivity->getLabel(),
                $targetMainActivity->getProperty('uriPathSegment')
            ]),
            [
                'propertyName' => 'activities',
                'propertyValue' => $targetMainActivity
            ]
        ));
    }

    /**
     * @param NodeInterface $node
     * @return null|NodeInterface
     */
    protected function getNodeMainActivity(NodeInterface $node)
    {
        $closestActivityQuery = new FlowQuery([$node]);
        /** @var NodeInterface $closestActivity */
        $closestActivity = $closestActivityQuery->closest('[instanceof Ttree.ArchitectesCh:Activity]')->get(0);
        $activities = $node->getProperty('activities');
        if ($closestActivity === null) {
            if (isset($activities[0])) {
                return $activities[0];
            } else {
                return null;
            }
        }
        return $closestActivity;
    }
}
