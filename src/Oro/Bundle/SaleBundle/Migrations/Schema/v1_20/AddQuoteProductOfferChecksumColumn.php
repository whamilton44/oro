<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "checksum" column for {@see QuoteProductOffer::$checksum} field.
 */
class AddQuoteProductOfferChecksumColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_sale_quote_prod_offer');
        if (!$table->hasColumn('checksum')) {
            $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        }
    }
}
