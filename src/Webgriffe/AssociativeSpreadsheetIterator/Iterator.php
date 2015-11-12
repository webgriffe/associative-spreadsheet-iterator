<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeSpreadsheetIterator;

class Iterator implements \SeekableIterator
{
    /**
     * @var \PHPExcel_Worksheet_RowIterator
     */
    protected $rowIterator;

    /**
     * @var array
     */
    protected $header;

    /**
     * @var string
     */
    protected $highestDataColumnName;

    /**
     * @var int
     */
    protected $highestRow;

    /**
     * @var ChunkReadFilter
     */
    protected $filter;

    /**
     * @var \PHPExcel_Reader_IReader
     */
    protected $reader;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var integer
     */
    protected $sheetNumber;

    /**
     * @var bool
     */
    protected $fillMissingColumns;

    /**
     * @var bool
     */
    protected $removeCellsNotMatchingHeaderColumns;

    public function __construct(
        $fileName,
        $csvDelimiter = null,
        $csvEnclosure = null,
        $sheetNumber = 1,
        $chunkSize = null,
        $fillMissingColumns = true,
        $removeCellsNotMatchingHeaderColumns = true
    ) {
        $this->filename = $fileName;
        $this->sheetNumber = $sheetNumber;
        $this->fillMissingColumns = $fillMissingColumns;
        $this->removeCellsNotMatchingHeaderColumns = $removeCellsNotMatchingHeaderColumns;
        //@todo: Allow disabling chunked read
        $this->filter = new ChunkReadFilter(0, $chunkSize);

        $this->reader = \PHPExcel_IOFactory::createReaderForFile($this->filename);
        if ($this->reader instanceof \PHPExcel_Reader_CSV) {
            if ($csvDelimiter) {
                $this->reader->setDelimiter($csvDelimiter);
            }
            if ($csvEnclosure) {
                $this->reader->setEnclosure($csvEnclosure);
            }
        }
        $this->reader->setReadFilter($this->filter);
        $worksheet = $this->reloadIterator(1);
        $this->highestDataColumnName = $worksheet->getHighestDataColumn();
        $this->highestRow = $worksheet->getHighestRow();
        $this->rowIterator->rewind();
        $this->header = $this->getFilteredArrayForCurrentRow();
        foreach ($this->header as $headerIndex => &$columnHeader) {
            if (is_null($columnHeader) || $columnHeader === '') {
                $columnHeader = $headerIndex;
            } else {
                $columnHeader = trim($columnHeader);
            }
        }
        $this->rowIterator->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @throws \LogicException
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (empty($this->header)) {
            throw new \LogicException('Cannot fetch CSV row, header is not set.');
        }

        $currentLine = $this->getFilteredArrayForCurrentRow();
        $headerSize = count($this->header);

        if (count($currentLine) != $headerSize ||
            count(array_intersect_key($currentLine, $this->header)) != $headerSize) {
            throw new \LogicException(
                sprintf(
                    'Cannot fetch CSV row, header columns do not match rows of current row. '.
                    'Header is: %s Current row is: %s',
                    print_r($this->header, true),
                    print_r($currentLine, true)
                )
            );
        }

        assert('count($currentLine) === count($this->header)');

        //Generate che current row. Matche the header and the current row using the keys of both arrays
        $result = array();
        foreach ($this->header as $index => $columnHeading) {
            if (!array_key_exists($index, $currentLine)) {
                throw new \LogicException(
                    sprintf('Column mismatch: header contains column key %s, while current row does not', $index)
                );
            }
            $result[$columnHeading] = $currentLine[$index];
        }
        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        //Key is 1-based
        if ($this->rowIterator->key() >= ($this->filter->getCurrentEndRow() - 1)) {
            $this->reloadIterator($this->filter->getCurrentEndRow());
        } else {
            $this->rowIterator->next();
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->rowIterator->key() - 1;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->rowIterator->valid();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->reloadIterator(1);

        $this->rowIterator->rewind();
        $this->rowIterator->next();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Seeks to a position
     * @link http://php.net/manual/en/seekableiterator.seek.php
     * @param int $position <p>
     * The position to seek to.
     * </p>
     * @return void
     */
    public function seek($position)
    {
        $innerPosition = $position + 1;
        if ($innerPosition < $this->filter->getCurrentStartRow() ||
            $innerPosition >= $this->filter->getCurrentEndRow()
        ) {
            $this->reloadIterator($innerPosition);
        } else {
            $this->rowIterator->seek($innerPosition);
        }
    }

    /**
     * @return int
     */
    public function getHighestRow()
    {
        return $this->highestRow;
    }

    /**
     * @return array
     */
    private function getFilteredArrayForCurrentRow()
    {
        /** @var \PHPExcel_Worksheet_CellIterator $cellIterator */
        $cellIterator = $this->rowIterator->current()->getCellIterator('A', $this->highestDataColumnName);
        $isHeaderRow = !is_array($this->header) || count($this->header) == 0;
        $array = array();
        /** @var \PHPExcel_Cell $cell */
        $cellArray = iterator_to_array($cellIterator);
        if (!$isHeaderRow && $this->removeCellsNotMatchingHeaderColumns) {
            //Remove every cell that is not in a column mapped by the header
            $cellArray = array_intersect_key($cellArray, $this->header);
        }
        foreach ($cellArray as $key => $cell) {
            if ($cell->getDataType() == \PHPExcel_Cell_DataType::TYPE_NULL) {
                //Remove all empty cells. We'll add them back later if necessary
                continue;
            }
            // TODO add a flag to indicate whether to use calculated value or plain value and test it
            /** @var \PHPExcel_Cell $cell */
            if ($cell->getDataType() != \PHPExcel_Cell_DataType::TYPE_FORMULA) {
                $array[$key] = $cell->getCalculatedValue();
                continue;
            }

            //Compute formulas with the cache disabled
            $calculation = \PHPExcel_Calculation::getInstance(
                $cell->getWorksheet()->getParent()
            );
            $calculation->disableCalculationCache();
            $result = $calculation->calculateCellValue($cell, true);

            if (is_array($result)) {
                while (is_array($result)) {
                    $result = array_pop($result);
                }
            }
            $array[$key] = $result;
        }

        //Should we add missing values?
        if (!$isHeaderRow && $this->fillMissingColumns) {
            foreach ($this->header as $index => $value) {
                if (!array_key_exists($index, $array)) {
                    $array[$index] = null;
                }
            }
        }

        return $array;
    }

    /**
     * @param $startRowIndex
     * @return \PHPExcel_Worksheet
     */
    private function reloadIterator($startRowIndex)
    {
        $this->filter->setRows($startRowIndex);
        $phpExcel = $this->reader->load($this->filename);
        $worksheet = $phpExcel->getSheet($this->sheetNumber - 1);
        $this->rowIterator = $worksheet->getRowIterator();
        $this->rowIterator->rewind();
        try {
            $this->rowIterator->seek($startRowIndex);
        } catch (\PHPExcel_Exception $ex) {
            //Edge case: the data source ends right at the end of this chunk, so the new chunk is empty and the seek
            //operation fails. Set the end to -1 to make the iterator invalid from now on
            $this->rowIterator->resetEnd(-1);
        }

        return $worksheet;
    }
}
