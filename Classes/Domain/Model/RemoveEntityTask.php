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
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * RemoveEntityTask
 */
class RemoveEntityTask implements EntityBasedTaskInterface
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Remove entity';
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
     * @param Context $context
     * @param ContentObjectProxyController $controller
     * @return array
     */
    public function execute($currentEntity, Context $context, ContentObjectProxyController $controller)
    {
        return [];
    }
}
