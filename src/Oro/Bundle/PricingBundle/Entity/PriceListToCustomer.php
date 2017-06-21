<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\Customer;

/**
 * @ORM\Table(name="oro_price_list_to_customer", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="oro_price_list_to_customer_unique_key",
 *          columns={"customer", "priceList", "website"}
 *     )
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository")
 */
class PriceListToCustomer extends BasePriceListRelation
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $customer;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }
}
