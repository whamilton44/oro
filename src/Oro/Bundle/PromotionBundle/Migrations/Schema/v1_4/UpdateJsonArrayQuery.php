<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\MySQL57Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Update field database type for oro_promotion_applied.config_options and oro_promotion_applied.promotion_data fields
 * on mysql 5.7 to use native JSON
 */
class UpdateJsonArrayQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Convert a column with "DC2Type:json_array" type to "JSON" type on MySQL >= 5.7.8 and Doctrine 2.7'
        );
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySQL57Platform) {
            $updateSql = "ALTER TABLE oro_promotion_applied ".
                "CHANGE config_options config_options JSON NOT NULL COMMENT '(DC2Type:json_array)',
                CHANGE promotion_data promotion_data JSON NOT NULL COMMENT '(DC2Type:json_array)'";

            $this->logQuery($logger, $updateSql);
            if (!$dryRun) {
                $this->connection->executeStatement($updateSql);
            }
        }
    }
}
