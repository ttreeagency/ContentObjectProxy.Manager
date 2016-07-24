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

use TYPO3\Flow\Annotations as Flow;

/**
 * BatchTaskInterface
 */
interface LabelInterface
{
    /**
     * @return string
     */
    public static function getLabel();
}
