<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class AccountTaxCodeRepository extends AbstractTaxCodeRepository
{
    /**
     * @param Account $account
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccount(Account $account)
    {
        if (!$account->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT, $account);
    }

    /**
     * @param CustomerGroup $accountGroup
     *
     * @return AccountTaxCode|null
     */
    public function findOneByAccountGroup(CustomerGroup $accountGroup)
    {
        if (!$accountGroup->getId()) {
            return null;
        }

        return $this->findOneByEntity(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $accountGroup);
    }
}
