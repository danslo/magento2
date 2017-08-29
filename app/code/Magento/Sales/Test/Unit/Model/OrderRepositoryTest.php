<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Model\ResourceModel\Metadata;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var Metadata|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadata;

    /**
     * @var SearchResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchResultFactory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessor;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $className = \Magento\Sales\Model\ResourceModel\Metadata::class;
        $this->metadata = $this->createMock($className);

        $className = \Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory::class;
        $this->searchResultFactory = $this->createPartialMock($className, ['create']);
        $this->collectionProcessor = $this->createMock(
            \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
        );
        $orderExtensionFactoryMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository = $this->objectManager->getObject(
            \Magento\Sales\Model\OrderRepository::class,
            [
                'metadata' => $this->metadata,
                'searchResultFactory' => $this->searchResultFactory,
                'collectionProcessor' => $this->collectionProcessor,
                'orderExtensionFactory' => $orderExtensionFactoryMock
            ]
        );
    }

    public function testGetList()
    {
        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $collectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $itemsMock = $this->getMockBuilder(OrderInterface::class)->disableOriginalConstructor()->getMock();

        $extensionAttributes = $this->createPartialMock(
            \Magento\Sales\Api\Data\OrderExtension::class,
            ['getShippingAssignments']
        );
        $shippingAssignmentBuilder = $this->createMock(
            \Magento\Sales\Model\Order\ShippingAssignmentBuilder::class
        );
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock);
        $itemsMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->any())
            ->method('getShippingAssignments')
            ->willReturn($shippingAssignmentBuilder);

        $this->searchResultFactory->expects($this->once())->method('create')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('getItems')->willReturn([$itemsMock]);

        $this->assertEquals($collectionMock, $this->orderRepository->getList($searchCriteriaMock));
    }

    public function testSave()
    {
        $mapperMock = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderEntity = $this->createMock(\Magento\Sales\Model\Order::class);
        $extensionAttributes = $this->createPartialMock(
            \Magento\Sales\Api\Data\OrderExtension::class,
            ['getShippingAssignments']
        );
        $shippingAssignment = $this->getMockBuilder(\Magento\Sales\Model\Order\ShippingAssignment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipping'])
            ->getMock();
        $shippingMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipping::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddress', 'getMethod'])
            ->getMock();
        $orderEntity->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $orderEntity->expects($this->once())->method('getIsNotVirtual')->willReturn(true);
        $extensionAttributes
            ->expects($this->any())
            ->method('getShippingAssignments')
            ->willReturn([$shippingAssignment]);
        $shippingAssignment->expects($this->once())->method('getShipping')->willReturn($shippingMock);
        $shippingMock->expects($this->once())->method('getAddress');
        $shippingMock->expects($this->once())->method('getMethod');
        $this->metadata->expects($this->once())->method('getMapper')->willReturn($mapperMock);
        $mapperMock->expects($this->once())->method('save');
        $orderEntity->expects($this->any())->method('getEntityId')->willReturn(1);
        $this->orderRepository->save($orderEntity);
    }
}
