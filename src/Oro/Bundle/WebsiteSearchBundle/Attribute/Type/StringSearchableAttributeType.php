<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Attribute type provides metadata for string attribute for search index.
 */
class StringSearchableAttributeType extends AbstractSearchableAttributeType
{
    #[\Override]
    protected function getFilterStorageFieldTypeMain(FieldConfigModel $attribute): string
    {
        return Query::TYPE_TEXT;
    }

    #[\Override]
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        return Query::TYPE_TEXT;
    }

    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_STRING;
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    #[\Override]
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }

    #[\Override]
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }

    #[\Override]
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }
}
