<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class Result extends \ArrayObject
{
    const TOTAL = 'total';
    const SHIPPING = 'shipping';
    const UNIT = 'unit';
    const TAXES = 'taxes';

    /**
     * @return ResultElement
     */
    public function getTotal()
    {
        return $this->getOffset(self::TOTAL, new ResultElement());
    }

    /**
     * @return ResultElement
     */
    public function getShipping()
    {
        return $this->getOffset(self::SHIPPING, new ResultElement());
    }

    /**
     * @return ResultElement
     */
    public function getUnit()
    {
        return $this->getOffset(self::UNIT, new ResultElement());
    }

    /**
     * @return TaxResultElement[]
     */
    public function getTaxes()
    {
        return $this->getOffset(self::TAXES, []);
    }

    /**
     * @param string $offset
     * @param null $default
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        return $default;
    }

    /** {@inheritdoc} */
    public function serialize()
    {
        $this->offsetUnset(self::TAXES);

        parent::serialize();
    }
}
