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
     * @var int
     */
    protected $highestDataColumn;

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

    public function __construct($fileName, $csvDelimiter = null, $csvEnclosure = null)
    {
        $this->filename = $fileName;
        $this->filter = new ChunkReadFilter();

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
        $this->highestDataColumn = \PHPExcel_Cell::columnIndexFromString($worksheet->getHighestDataColumn());
        $this->rowIterator->rewind();
        $this->header = $this->convertCellIteratorToFilteredArray($this->rowIterator->current()->getCellIterator());
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

        $currentLine = $this->convertCellIteratorToFilteredArray($this->rowIterator->current()->getCellIterator());
        if (count($currentLine) != count($this->header)) {
            throw new \LogicException(
                sprintf(
                    'Cannot fetch CSV row, header columns count do not match. Current row is: %s',
                    print_r($currentLine, true)
                )
            );
        }

        return array_combine($this->header, $currentLine);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        //Key è basato ad 1
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
     * @param \PHPExcel_Worksheet_CellIterator $cellIterator
     * @return array
     */
    private function convertCellIteratorToFilteredArray(\PHPExcel_Worksheet_CellIterator $cellIterator)
    {
        $cellIterator->setIterateOnlyExistingCells(false);
        $array = iterator_to_array($cellIterator);
        $array = array_map(
            function ($cell) {
                /** @var \PHPExcel_Cell $cell */
                return $cell->getValue();
            },
            $array
        );
        $array = array_slice($array, 0, $this->highestDataColumn);
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
        $worksheet = $phpExcel->getSheet(0);
        $this->rowIterator = $worksheet->getRowIterator();
        $this->rowIterator->rewind();
        $this->rowIterator->seek($startRowIndex);

        return $worksheet;
    }
}
