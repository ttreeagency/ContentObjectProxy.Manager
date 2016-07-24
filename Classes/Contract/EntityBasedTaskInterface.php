<?php
namespace Ttree\ContentObjectProxy\Manager\Contract;

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
use Ttree\ContentObjectProxy\Manager\Domain\Model\ActionStack;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * EntityBasedTaskInterface
 */
interface EntityBasedTaskInterface extends TaskInterface
{
    /**
     * @param string $currentEntity
     * @param array $data
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @param \Closure $callback
     * @return ActionStack
     */
    public function execute($currentEntity, array $data, Context $context, ContentObjectProxyController $controller, \Closure $callback = null);
}
