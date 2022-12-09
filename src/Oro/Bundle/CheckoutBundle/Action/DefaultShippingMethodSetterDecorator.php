<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

/**
 * Set shipping methods for checkout from source entity.
 */
class DefaultShippingMethodSetterDecorator
{
    private DefaultShippingMethodSetter $defaultShippingMethodSetter;

    public function __construct(DefaultShippingMethodSetter $defaultShippingMethodSetter)
    {
        $this->defaultShippingMethodSetter = $defaultShippingMethodSetter;
    }

    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }
        $sourceEntity = $checkout->getSourceEntity();

        if ($sourceEntity instanceof PreConfiguredShippingMethodConfigurationInterface
            && $sourceEntity->getShippingMethod()
            && $sourceEntity->getShippingMethodType()
        ) {
            $checkout->setShippingMethod($sourceEntity->getShippingMethod());
            $checkout->setShippingMethodType($sourceEntity->getShippingMethodType());
        } else {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
    }
}
