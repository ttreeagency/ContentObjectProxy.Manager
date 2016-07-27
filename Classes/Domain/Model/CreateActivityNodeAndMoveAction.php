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

use Ttree\ArchitectesCh\Domain\Model\Activity;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use Webmozart\Assert\Assert;

/**
 * CreateActivityNodeAndMove
 */
class CreateActivityNodeAndMoveAction extends AbstractAction
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @param NodeInterface $node
     * @param string $message
     * @param array $options
     * @param null|string $icon
     */
    public function __construct(NodeInterface $node, $message, array $options, $icon = null)
    {
        Assert::keyExists($options, 'target');
        Assert::keyExists($options, 'parentNode');
        Assert::isInstanceOf($options['target'], NodeInterface::class);
        Assert::isInstanceOf($options['parentNode'], NodeInterface::class);

        parent::__construct($node, $message, $options, $icon);
    }

    /**
     * @return void
     */
    public function apply()
    {
        $parentNode = $this->getPartentNode();
        /** @var Activity $activity */
        $activity = $this->getTargetNode()->getContentObject();

        $query = new FlowQuery([$parentNode]);
        $filter = sprintf('[instanceof Ttree.ArchitectesCh:Activity][uriPathSegment = "%s"]', $activity->getUriPathSegment());
        $activityNode = $query->children($filter)->get(0);
        if ($activityNode === null) {
            // Create activity node
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType('Ttree.ArchitectesCh:Activity'));

            $template->setContentObject($activity);
            $template->setProperty('title', $activity->getTitle());
            $template->setProperty('uriPathSegment', $activity->getUriPathSegment());

            $activityNode = $parentNode->createNodeFromTemplate($template);
        }

        // Move node
        $this->node->moveInto($activityNode);
    }

    /**
     * @return NodeInterface
     */
    protected function getPartentNode()
    {
        return $this->options['parentNode'];
    }

    /**
     * @return NodeInterface
     */
    protected function getTargetNode()
    {
        return $this->options['target'];
    }
}
