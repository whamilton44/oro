<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration, DatabasePlatformAwareInterface, RenameExtensionAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroPriceRuleTable($schema);
        $this->createOroPriceRuleChTriggerTable($schema);
        $this->createOroPriceRuleLexemeTable($schema);

        /** Foreign keys generation **/
        $this->addOroPriceRuleForeignKeys($schema, $queries);
        $this->addOroPriceRuleChTriggerForeignKeys($schema, $queries);
        $this->addOroPriceRuleLexemeForeignKeys($schema);

        $this->updateProductPriceTable($schema);
        $this->updatePriceListTable($schema);

        $this->alterOroPriceAttributeTable($schema, $queries);

        $this->renameColumnsAndTables($schema, $queries);
        $this->renameIndexes($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function alterOroPriceAttributeTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_attribute_pl');
        $table->addColumn('field_name', 'string', ['length' => 255, 'notnull' => false]);
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_attribute_pl SET field_name = LOWER(name)'
            )
        );
        $postSchema = clone $schema;
        $postSchema->getTable('orob2b_price_attribute_pl')
            ->changeColumn('field_name', ['notnull' => true]);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);
        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }
    }

    /**
     * Create oro_price_rule table
     *
     * @param Schema $schema
     */
    protected function createOroPriceRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('rule_condition', 'text', ['notnull' => false]);
        $table->addColumn('rule', 'text', ['notnull' => true]);
        $table->addColumn('priority', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_rule_ch_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroPriceRuleChTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_rule_ch_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_rule_lexeme table
     *
     * @param Schema $schema
     */
    protected function createOroPriceRuleLexemeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_rule_lexeme');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('class_name', 'string', ['length' => 255]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('relation_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_price_rule foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOroPriceRuleForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_price_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_price_rule',
            'oro_product_unit',
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_rule_ch_trigger foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOroPriceRuleChTriggerForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_price_rule_ch_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_price_rule_ch_trigger',
            'oro_product',
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_rule_lexeme foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceRuleLexemeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_rule_lexeme');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_rule'),
            ['price_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function updateProductPriceTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_product');
        $table->addColumn('price_rule_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_rule'),
            ['price_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function updatePriceListTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list');
        $table->addColumn('product_assignment_rule', 'text', ['notnull' => false]);
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();
        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameColumnsAndTables(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE14F8DA267');
        $extension->renameColumn($schema, $queries, $notes, 'price_list_895c1635_id', 'price_list_9919ee5_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_price_list',
            ['price_list_9919ee5_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\PricingBundle\Entity\PriceList',
            'price_list_895c1635',
            'price_list_9919ee5',
            RelationType::MANY_TO_ONE
        ));

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_price_list', 'oro_price_list');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_currency', 'oro_price_list_currency');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_to_acc_group', 'oro_price_list_to_acc_group');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_to_account', 'oro_price_list_to_account');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_to_website', 'oro_price_list_to_website');
        $extension->renameTable($schema, $queries, 'orob2b_price_product', 'oro_price_product');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_combined', 'oro_price_list_combined');
        $extension->renameTable($schema, $queries, 'orob2b_price_product_combined', 'oro_price_product_combined');
        $extension->renameTable($schema, $queries, 'orob2b_price_product_minimal', 'oro_price_product_minimal');
        $extension->renameTable($schema, $queries, 'orob2b_plist_curr_combined', 'oro_plist_curr_combined');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_acc_fb', 'oro_price_list_acc_fb');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_acc_gr_fb', 'oro_price_list_acc_gr_fb');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_website_fb', 'oro_price_list_website_fb');
        $extension->renameTable($schema, $queries, 'orob2b_cmb_price_list_to_acc', 'oro_cmb_price_list_to_acc');
        $extension->renameTable($schema, $queries, 'orob2b_cmb_plist_to_acc_gr', 'oro_cmb_plist_to_acc_gr');
        $extension->renameTable($schema, $queries, 'orob2b_cmb_price_list_to_ws', 'oro_cmb_price_list_to_ws');
        $extension->renameTable($schema, $queries, 'orob2b_cmb_pl_to_pl', 'oro_cmb_pl_to_pl');
        $extension->renameTable($schema, $queries, 'orob2b_prod_price_ch_trigger', 'oro_prod_price_ch_trigger');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_schedule', 'oro_price_list_schedule');
        $extension->renameTable($schema, $queries, 'orob2b_cpl_activation_rule', 'oro_cpl_activation_rule');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_ch_trigger', 'oro_price_list_ch_trigger');
        $extension->renameTable($schema, $queries, 'orob2b_price_attribute_pl', 'oro_price_attribute_pl');
        $extension->renameTable($schema, $queries, 'orob2b_product_attr_currency', 'oro_product_attr_currency');
        $extension->renameTable($schema, $queries, 'orob2b_price_attribute_price', 'oro_price_attribute_price');
        $extension->renameTable($schema, $queries, 'orob2b_price_list_to_product', 'oro_price_list_to_product');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function renameIndexes(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $schema->getTable('orob2b_price_product')->dropIndex('orob2b_pricing_price_list_uidx');
        $schema->getTable('orob2b_price_product_combined')->dropIndex('orob2b_combined_price_uidx');
        $schema->getTable('orob2b_price_product_minimal')->dropIndex('orob2b_minimal_price_uidx');
        $schema->getTable('orob2b_price_list_acc_fb')->dropIndex('orob2b_price_list_acc_fb_unq');
        $schema->getTable('orob2b_price_list_acc_gr_fb')->dropIndex('orob2b_price_list_acc_gr_fb_unq');
        $schema->getTable('orob2b_price_list_website_fb')->dropIndex('orob2b_price_list_website_fb_unq');
        $schema->getTable('orob2b_cmb_price_list_to_acc')->dropIndex('orob2b_cpl_to_acc_ws_unq');
        $schema->getTable('orob2b_cmb_plist_to_acc_gr')->dropIndex('orob2b_cpl_to_acc_gr_ws_unq');
        $schema->getTable('orob2b_cmb_price_list_to_ws')->dropIndex('orob2b_cpl_to_ws_unq');
        $schema->getTable('orob2b_cmb_pl_to_pl')->dropIndex('b2b_cmb_pl_to_pl_cmb_prod_sort_idx');
        $schema->getTable('orob2b_prod_price_ch_trigger')->dropIndex('orob2b_changed_product_price_list_unq');
        $schema->getTable('orob2b_price_attribute_price')->dropIndex('orob2b_pricing_price_attribute_uidx');
        $schema->getTable('orob2b_price_list_to_product')->dropIndex('orob2b_price_list_to_product_uidx');

        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_product',
            ['product_id', 'price_list_id', 'quantity', 'unit_code', 'currency'],
            'oro_pricing_price_list_uidx'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_product_combined',
            ['product_id', 'combined_price_list_id', 'quantity', 'unit_code', 'currency'],
            'oro_combined_price_uidx'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_product_minimal',
            ['product_id', 'combined_price_list_id', 'currency'],
            'oro_minimal_price_uidx'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_list_acc_fb',
            ['account_id', 'website_id'],
            'oro_price_list_acc_fb_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_list_acc_gr_fb',
            ['account_group_id', 'website_id'],
            'oro_price_list_acc_gr_fb_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_list_website_fb',
            ['website_id'],
            'oro_price_list_website_fb_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_cmb_price_list_to_acc',
            ['account_id', 'website_id'],
            'oro_cpl_to_acc_ws_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_cmb_plist_to_acc_gr',
            ['account_group_id', 'website_id'],
            'oro_cpl_to_acc_gr_ws_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_cmb_price_list_to_ws',
            ['website_id'],
            'oro_cpl_to_ws_unq'
        );
        $extension->addIndex(
            $schema,
            $queries,
            'oro_cmb_pl_to_pl',
            ['combined_price_list_id', 'sort_order'],
            'cmb_pl_to_pl_cmb_prod_sort_idx'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_prod_price_ch_trigger',
            ['product_id', 'price_list_id'],
            'oro_changed_product_price_list_unq'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_attribute_price',
            ['product_id', 'price_attribute_pl_id', 'quantity', 'unit_code', 'currency'],
            'oro_pricing_price_attribute_uidx'
        );
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_price_list_to_product',
            ['product_id', 'price_list_id'],
            'oro_price_list_to_product_uidx'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
