<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractLoadCheckouts extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    abstract protected function getData();

    /**
     * @return string
     */
    abstract protected function getWorkflowName();

    /**
     * @return Checkout
     */
    abstract protected function createCheckout();

    /**
     * @return string
     */
    abstract protected function getCheckoutSourceName();

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->clearPreconditions();
        /** @var AccountUser $accountUser */
        $accountUser = $manager->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
        foreach ($this->getData() as $name => $checkoutData) {
            $checkout = $this->createCheckout();
            $checkout->setAccountUser($accountUser);
            $checkout->setOrganization($accountUser->getOrganization());
            $source = new CheckoutSource();
            /** @var CheckoutSourceEntityInterface $sourceEntity */
            $sourceEntity = $this->getReference($checkoutData['source']);
            $this->container->get('property_accessor')->setValue(
                $source,
                $this->getCheckoutSourceName(),
                $sourceEntity
            );
            $checkout->setPaymentMethod($checkoutData['checkout']['payment_method']);
            $checkout->setSource($source);
            $manager->persist($checkout);
            $this->setReference($name, $checkout);
        }

        $manager->flush();
    }

    protected function clearPreconditions()
    {
        $workflowDefinition = $this->manager
            ->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->findOneBy(['name' => $this->getWorkflowName()]);
        $config = $workflowDefinition->getConfiguration();
        $config['transition_definitions']['__start___definition']['preconditions'] = [];
        $workflowDefinition->setConfiguration($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData',
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData'
        ];
    }
}
