<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RemoveCurrentUserVerificationFieldsPlugin
{
    /** @var ImsConfig */
    private ImsConfig $imsConfig;

    /**
     * @param ImsConfig $imsConfig
     */
    public function __construct(
        ImsConfig $imsConfig
    ) {
        $this->imsConfig = $imsConfig;
    }

    /**
     * @param Form $subject
     * @param callable $proceed
     * @param AbstractElement $element
     * @param bool $after
     */
    public function aroundAddElement(Form $subject, callable $proceed, AbstractElement $element, $after = false)
    {
        if ($this->imsConfig->enabled() !== true) {
            return $proceed($element, $after);
        }

        if ($element->getId() !== 'current_user_verification_fieldset') {
            return $proceed($element, $after);
        }
    }
}
