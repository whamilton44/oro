<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

class ProductSearchForUnauthenticatedEnabledTest extends ProductSearchForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
