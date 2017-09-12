<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Flancer32\FixPr9621\Fw\DB\Query;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * This class is copy of \Magento\Framework\DB\Query\BatchIterator (version 2.1.8)
 * with fixed 'calculateBatchSize()' method to prevent rows missing in re-index.
 *
 * https://github.com/magento/magento2/pull/9621
 *
 */
class BatchIterator implements BatchIteratorInterface
{
    /**
     * @var int
     */
    private $batchSize;
    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @var string
     */
    private $correlationName;
    /**
     * @var Select
     */
    private $currentSelect;
    /**
     * @var bool
     */
    private $isValid = true;
    /**
     * @var int
     */
    private $iteration = 0;
    /**
     * @var int
     */
    private $minValue = 0;
    /**
     * @var string
     */
    private $rangeField;
    /**
     * @var string
     */
    private $rangeFieldAlias;
    /**
     * @var Select
     */
    private $select;

    /**
     * Initialize dependencies.
     *
     * @param Select $select
     * @param int $batchSize
     * @param string $correlationName
     * @param string $rangeField
     * @param string $rangeFieldAlias
     */
    public function __construct(
        Select $select,
        $batchSize,
        $correlationName,
        $rangeField,
        $rangeFieldAlias
    )
    {
        $this->batchSize = $batchSize;
        $this->select = $select;
        $this->correlationName = $correlationName;
        $this->rangeField = $rangeField;
        $this->rangeFieldAlias = $rangeFieldAlias;
        $this->connection = $select->getConnection();
    }

    /**
     * Calculate batch size for select.
     *
     * @param Select $select
     * @return int
     */
    private function calculateBatchSize(Select $select)
    {
        $wrapperSelect = $this->connection->select();
        $wrapperSelect->from(
            $select,
            [
                new \Zend_Db_Expr('MAX(' . $this->rangeFieldAlias . ') as max'),
                new \Zend_Db_Expr('COUNT(*) as cnt')
            ]
        );
        $row = $this->connection->fetchRow($wrapperSelect);

        /* we need collect all records with ID=$upTo over LIMIT ($this->batchSize) */
        $upTo = $row['max'];
        $selectTailed = $this->initSelectObject();
        /* reset LIMIT and set upper bound for selection */
        $selectTailed->limit(null);
        $selectTailed->where(
            $this->connection->quoteIdentifier($this->correlationName)
            . '.' . $this->connection->quoteIdentifier($this->rangeField)
            . ' <= ?',
            $upTo
        );
        /* get total rows limited by "<=$upTo" only */
        $wrapperTailed = $this->connection->select();
        $wrapperTailed->from(
            $selectTailed,
            [new \Zend_Db_Expr('COUNT(*) as cnt')]
        );
        $rowTailed = $this->connection->fetchRow($wrapperTailed);

        /* $rowTailed[cnt] should be greater or equal to $row[cnt] */
        $totalFound = $rowTailed['cnt'];
        $select->limit($totalFound);

        $this->minValue = $upTo;
        return intval($totalFound);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (null == $this->currentSelect) {
            $this->currentSelect = $this->initSelectObject();
            $itemsCount = $this->calculateBatchSize($this->currentSelect);
            $this->isValid = $itemsCount > 0;
        }
        return $this->currentSelect;
    }

    /**
     * Initialize select object.
     *
     * @return \Magento\Framework\DB\Select
     */
    private function initSelectObject()
    {
        $object = clone $this->select;
        $object->where(
            $this->connection->quoteIdentifier($this->correlationName)
            . '.' . $this->connection->quoteIdentifier($this->rangeField)
            . ' > ?',
            $this->minValue
        );
        $object->limit($this->batchSize);
        /**
         * Reset sort order section from origin select object
         */
        $object->order($this->correlationName . '.' . $this->rangeField . ' ' . \Magento\Framework\DB\Select::SQL_ASC);
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iteration;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (null == $this->currentSelect) {
            $this->current();
        }
        $select = $this->initSelectObject();
        $itemsCountInSelect = $this->calculateBatchSize($select);
        $this->isValid = $itemsCountInSelect > 0;
        if ($this->isValid) {
            $this->iteration++;
            $this->currentSelect = $select;
        } else {
            $this->currentSelect = null;
        }
        return $this->currentSelect;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->minValue = 0;
        $this->currentSelect = null;
        $this->iteration = 0;
        $this->isValid = true;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->isValid;
    }
}