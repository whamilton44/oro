<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\StartFromShoppingListTransition;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\EmptyMatrixGridInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartFromShoppingListTransitionTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private ManagerRegistry|MockObject $registry;
    private IsWorkflowStartFromShoppingListAllowed|MockObject $isWorkflowStartFromShoppingListAllowed;
    private StartShoppingListCheckoutInterface|MockObject $startShoppingListCheckout;
    private ContextAccessor $contextAccessor;
    private EmptyMatrixGridInterface|MockObject $editableMatrixGrid;

    private StartFromShoppingListTransition $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->isWorkflowStartFromShoppingListAllowed = $this->createMock(
            IsWorkflowStartFromShoppingListAllowed::class
        );
        $this->startShoppingListCheckout = $this->createMock(StartShoppingListCheckoutInterface::class);
        $this->contextAccessor = new ContextAccessor();
        $this->editableMatrixGrid = $this->createMock(EmptyMatrixGridInterface::class);

        $this->transition = new StartFromShoppingListTransition(
            $this->actionExecutor,
            $this->registry,
            $this->isWorkflowStartFromShoppingListAllowed,
            $this->startShoppingListCheckout,
            $this->contextAccessor,
            $this->editableMatrixGrid
        );
    }

    /**
     * @dataProvider preConditionsDataProvider
     */
    public function testIsPreConditionAllowed(
        array $lineItemsArray,
        bool $isStartAllowedByListeners,
        bool $isAllowedForAny,
        bool $isAclAllowed,
        bool $expected
    ): void {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $shoppingList = $this->createMock(ShoppingList::class);
        $checkout = $this->createMock(Checkout::class);
        $workflowResult = new WorkflowResult(['shoppingList' => $shoppingList]);
        $workflowData = new WorkflowData();

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($workflowResult);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $lineItems = new ArrayCollection($lineItemsArray);
        $shoppingList->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->editableMatrixGrid->expects($this->any())
            ->method('hasEmptyMatrix')
            ->with($shoppingList)
            ->willReturn(true);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap(
                [
                    [
                        'acl_granted',
                        ['CHECKOUT_CREATE', $shoppingList],
                        null,
                        null,
                        $isAclAllowed
                    ],
                    [
                        ExtendableCondition::NAME,
                        [
                            'events' => ['extendable_condition.shopping_list_start'],
                            'eventData' => [
                                'checkout' => $checkout,
                                'shoppingList' => $shoppingList
                            ]
                        ],
                        null,
                        null,
                        $isStartAllowedByListeners
                    ]
                ]
            );

        $this->isWorkflowStartFromShoppingListAllowed->expects($this->any())
            ->method('isAllowedForAny')
            ->willReturn($isAllowedForAny);

        $this->assertSame($expected, $this->transition->isPreConditionAllowed($workflowItem));
    }

    public function preConditionsDataProvider(): array
    {
        return [
            'no line items' => [
                'lineItemsArray' => [],
                'isStartAllowedByListeners' => true,
                'isAllowedForAny' => true,
                'isAclAllowed' => true,
                'expected' => false
            ],
            'not allowed by listeners' => [
                'lineItemsArray' => [$this->createMock(LineItem::class)],
                'isStartAllowedByListeners' => false,
                'isAllowedForAny' => true,
                'isAclAllowed' => true,
                'expected' => false
            ],
            'not allowed by isWorkflowStartFromShoppingListAllowed' => [
                'lineItemsArray' => [$this->createMock(LineItem::class)],
                'isStartAllowedByListeners' => true,
                'isAllowedForAny' => false,
                'isAclAllowed' => true,
                'expected' => false
            ],
            'not allowed by ACL' => [
                'lineItemsArray' => [$this->createMock(LineItem::class)],
                'isStartAllowedByListeners' => true,
                'isAllowedForAny' => true,
                'isAclAllowed' => false,
                'expected' => false
            ],
            'allowed' => [
                'lineItemsArray' => [$this->createMock(LineItem::class)],
                'isStartAllowedByListeners' => true,
                'isAllowedForAny' => true,
                'isAclAllowed' => true,
                'expected' => true
            ],
        ];
    }

    public function testIsPreConditionNotAllowedWhenShoppingListIsNullNoInitContext(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData();

        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($workflowResult);
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->assertFalse($this->transition->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionShoppingListFromInContext(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $checkout = $this->createMock(Checkout::class);

        $initContext = ['entityClass' => ShoppingList::class, 'entityId' => 1];
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData([
            'init_context' => $initContext
        ]);

        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($workflowResult);
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(ShoppingList::class, 1)
            ->willReturn($shoppingList);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $this->assertFalse($this->transition->isPreConditionAllowed($workflowItem));
    }

    public function testExecute(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $newWorkflowItem = $this->createMock(WorkflowItem::class);
        $shoppingList = $this->createMock(ShoppingList::class);
        $workflowResult = new WorkflowResult(['shoppingList' => $shoppingList]);
        $workflowData = new WorkflowData();

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($workflowResult);

        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($workflowData);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true)
            ->willReturn([
                'workflowItem' => $newWorkflowItem,
                'checkout' => $checkout,
                'redirectUrl' => 'http://example.com'
            ]);

        $workflowItem->expects($this->once())
            ->method('merge')
            ->with($workflowItem);

        $this->transition->execute($workflowItem);

        $this->assertSame($checkout, $workflowData->offsetGet('checkout'));
        $this->assertSame('http://example.com', $workflowResult->offsetGet('redirectUrl'));
    }
}
