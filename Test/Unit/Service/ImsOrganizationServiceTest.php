<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Service;

use Magento\AdminAdobeIms\Exception\AdobeImsOrganizationAuthorizationException;
use Magento\AdobeImsApi\Api\ConfigInterface;
use Magento\AdobeImsApi\Api\GetOrganizationsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

class ImsOrganizationServiceTest extends TestCase
{
    private const VALID_ORGANIZATION_ID = '12121212ABCD1211AA11ABCD';
    private const INVALID_ORGANIZATION_ID = '12121212ABCD1211AA11XXXX';

    /**
     * @var GetOrganizationsInterface
     */
    private $imsOrganizationService;

    /**
     * @var ConfigInterface
     */
    private $imsConfigMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->imsConfigMock = $this->createMock(ConfigInterface::class);

        $this->imsOrganizationService = $objectManagerHelper->getObject(
            GetOrganizationsInterface::class,
            [
                'imsConfig' => $this->imsConfigMock
            ]
        );
    }

    public function testCheckOrganizationMembershipThrowsExceptionWhenProfileNotAssignedToOrg()
    {
        $this->imsConfigMock
            ->method('getOrganizationId')
            ->willReturn('');

        $this->expectException(AdobeImsOrganizationAuthorizationException::class);
        $this->expectExceptionMessage('Can\'t check user membership in organization.');

        $this->imsOrganizationService->checkOrganizationMembership('my_token');
    }
}
