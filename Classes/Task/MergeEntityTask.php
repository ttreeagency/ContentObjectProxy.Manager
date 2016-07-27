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

use Ttree\ArchitectesCh\Domain\Model\Activity;
use Ttree\ContentObjectProxy\Manager\Contract\EntityBasedTaskInterface;
use Ttree\ContentObjectProxy\Manager\Controller\Module\ContentObjectProxyController;
use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionStack;
use Ttree\ContentObjectProxy\Manager\Domain\Model\AddReferenceAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\CreateActivityNodeAndMoveAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\MoveNodeAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\RemoveNodeAction;
use Ttree\ContentObjectProxy\Manager\Domain\Model\RemoveReferenceAction;
use Ttree\ContentObjectProxy\Manager\Exception;
use Ttree\ContentObjectProxy\Manager\InvalidArgumentException;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * MergeEntityTask
 */
class MergeEntityTask implements EntityBasedTaskInterface
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
        return 'Merge';
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return 'icon-code-fork';
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
        return 'Merge entities';
    }

    /**
     * @param Activity $currentEntity
     * @param array $data
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @param \Closure $callback
     * @return ActionStack
     * @throws InvalidArgumentException
     */
    public function execute($currentEntity, array $data, Context $context, ContentObjectProxyController $controller, \Closure $callback = null)
    {
        if (!isset($data['target']) || trim($data['target']) === '') {
            throw new InvalidArgumentException('Missing merge target', 1469397458);
        }
        $targetReferenceActivity = $context->getNodeByIdentifier($data['target']);
        if ($targetReferenceActivity === null) {
            throw new InvalidArgumentException('Missing target reference activity', 1469397459);
        }
        /** @var Activity $targetActivity */
        $targetActivity = $targetReferenceActivity->getContentObject();
        if ($targetActivity === null) {
            throw new InvalidArgumentException('Invalid target reference activity, missing content object', 1469397460);
        }

        $actionStack = new ActionStack();

        $sourceIdentifier = $this->persistenceManager->getIdentifierByObject($currentEntity);

        $nodes = array_map(function ($nodeData) use ($context) {
            return $this->nodeFactory->createFromNodeData($nodeData, $context);
        }, $this->nodeDataRepository->findByContentObjectProxy($sourceIdentifier, $context->getWorkspace()));

        array_map(function (NodeInterface $node) use ($actionStack, $targetActivity, $currentEntity) {
            $this->moveNode($node, $actionStack, $targetActivity, $currentEntity);
        }, $nodes);

        return $actionStack;
    }

    /**
     * @param NodeInterface $node
     * @param ActionStack $actionStack
     * @param Activity $targetActivity
     * @param Activity $currentEntity
     * @throws Exception
     */
    protected function moveNode(NodeInterface $node, ActionStack $actionStack, Activity $targetActivity, Activity $currentEntity)
    {
        if (!$node->getNodeType()->isOfType('Ttree.ArchitectesCh:Activity')) {
            throw new Exception('Unable to process nodes of the given type: ' . $node->getNodeType()->getName(), 1469398784);
        }

        if ($this->hasChildren($node)) {
            array_map(function (NodeInterface $node) use ($actionStack, $targetActivity, $currentEntity) {
                $activities = $node->getProperty('activities');

                $parentNode = $node->getParent();
                $targetExistQuery = new FlowQuery([$parentNode]);
                $targetExistQuery = $targetExistQuery->siblings(sprintf('[uriPathSegment="%s"]', $targetActivity->getUriPathSegment()));

                /** @var NodeInterface $targetMainActivity */
                $targetMainActivity = $targetExistQuery->get(0);
                if ($targetMainActivity === null) {
                    // If the activity do not exist in the current position
                    $targetParentNode = null;
                    $parentNode = $parentNode->getParent();
                    $actionStack->addAction(new CreateActivityNodeAndMoveAction(
                        $node,
                        vsprintf('Node "%s" (%s) need to be create bellow "%s" (%s) before moving the node', [
                            $targetActivity->getName(),
                            $targetActivity->getUriPathSegment(),
                            $parentNode->getLabel(),
                            $parentNode->getProperty('uriPathSegment'),
                        ]),
                        [
                            'target' => $targetActivity,
                            'parentNode' => $parentNode
                        ]
                    ));
                } else {
                    // Move node to the existing activity node
                    $actionStack->addAction(new MoveNodeAction(
                        $node,
                        vsprintf('Node can be moved bellow "%s" (%s)', [
                            $targetMainActivity->getLabel(),
                            $targetMainActivity->getProperty('uriPathSegment'),
                        ]),
                        [
                            'target' => $targetMainActivity
                        ]
                    ));

                    // Check if the activity references need to be updated
                    $attachedActivities = array_filter($activities, function (NodeInterface $node) use ($targetMainActivity) {
                        return $node->getLabel() === $targetMainActivity->getLabel();
                    });
                    if (count($attachedActivities) === 0) {
                        $actionStack->addAction(new AddReferenceAction(
                            $node,
                            vsprintf('Activity reference "%s" must be set', [
                                $targetMainActivity->getLabel()
                            ]),
                            [
                                'propertyName' => 'activities',
                                'propertyValue' => $targetMainActivity
                            ]
                        ));
                    }
                }

                // Remove previous activity from reference
                $attachedActivities = array_filter($activities, function (NodeInterface $node) use ($currentEntity) {
                    return $node->getLabel() === $currentEntity->getName();
                });

                if (count($attachedActivities) !== 0) {
                    /** @var NodeInterface $currentMainActivity */
                    $currentMainActivity = $attachedActivities[0];
                    $actionStack->addAction(new RemoveReferenceAction(
                        $node,
                        vsprintf('Activity reference "%s" can be unset', [
                            $currentMainActivity->getLabel()
                        ]),
                        [
                            'propertyName' => 'activities',
                            'propertyValue' => $currentMainActivity
                        ]
                    ));
                }
            }, $node->getChildNodes('TYPO3.Neos:Document'));
        } else {
            $actionStack->addAction(new RemoveNodeAction(
                $node,
                vsprintf('Node "%s" can be safely removed, no children nodes', [
                    $node->getLabel()
                ])
            ));
        }
    }

    protected function hasChildren(NodeInterface $node)
    {
        return count($node->getChildNodes('TYPO3.Neos:Document')) > 0;
    }
}
