<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeSpreadsheetIterator;


class Iterator implements \Iterator
{
    /**
     * @var \PHPExcel_Worksheet_RowIterator
     */
    protected $rowIterator;

    /**
     * @var array
     */
    protected $header;

    public function __construct($fileName, $csvDelimiter = null, $csvEnclosure = null) {
        $reader = \PHPExcel_IOFactory::createReaderForFile($fileName);
        if ($reader instanceof \PHPExcel_Reader_CSV) {
            if ($csvDelimiter) {
                $reader->setDelimiter($csvDelimiter);
            }
            if ($csvEnclosure) {
                $reader->setEnclosure($csvEnclosure);
            }
        }
        $phpExcel = $reader->load($fileName);
        $this->rowIterator = $phpExcel->getSheet(0)->getRowIterator();
        $this->rowIterator->rewind();
        $this->header = iterator_to_array($this->rowIterator->current()->getCellIterator());
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

        $currentLine = iterator_to_array($this->rowIterator->current()->getCellIterator());
        $currentLine = array_map(
            function (\PHPExcel_Cell $cell) {
                return $cell->getValue();
            },
            $currentLine
        );
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
        $this->rowIterator->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->rowIterator->key();
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
        $this->rowIterator->rewind();
        $this->rowIterator->next();
    }

    /**
     * @param $currentLine array
     */
    private function processMultipleValues(&$currentLine)
    {
        foreach ($currentLine as &$cell) {
            if (strpos($cell, '|') !== false) {
                $cell = explode('|', $cell);
            }
        }
    }
}
