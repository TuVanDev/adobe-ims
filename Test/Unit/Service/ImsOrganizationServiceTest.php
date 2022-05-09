<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Service;

use Magento\AdminAdobeIms\Exception\AdobeImsOrganizationAuthorizationException;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\AdminAdobeIms\Service\ImsOrganizationService;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

class ImsOrganizationServiceTest extends TestCase
{
    private const VALID_ORGANIZATION_ID = '12121212ABCD1211AA11ABCD';
    private const INVALID_ORGANIZATION_ID = '12121212ABCD1211AA11XXXX';

    /**
     * @var ImsOrganizationService
     */
    private $imsOrganizationService;

    /**
     * @var ImsConfig
     */
    private $adminImsConfigMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->adminImsConfigMock = $this->createMock(ImsConfig::class);

        $this->imsOrganizationService = $objectManagerHelper->getObject(
            ImsOrganizationService::class,
            [
                'adminImsConfig' => $this->adminImsConfigMock
            ]
        );
    }

    public function testCheckOrganizationAllocationReturnsTrueWhenProfileAssignedToOrg()
    {
        $this->adminImsConfigMock
            ->method('getOrganizationId')
            ->willReturn(self::VALID_ORGANIZATION_ID);

        $this->assertEquals(
            true,
            $this->imsOrganizationService->checkOrganizationAllocation('my_token')
        );
    }

    public function testCheckOrganizationAllocationThrowsExceptionWhenProfileNotAssignedToOrg()
    {
        $this->adminImsConfigMock
            ->method('getOrganizationId')
            ->willReturn('');

        $this->expectException(AdobeImsOrganizationAuthorizationException::class);
        $this->expectExceptionMessage('User is not assigned to defined organization.');

        $this->imsOrganizationService->checkOrganizationAllocation('my_token');
    }
}
