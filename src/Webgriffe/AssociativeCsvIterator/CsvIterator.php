<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeCsvIterator;

use \Keboola\Csv\CsvFile;

class CsvIterator implements \Iterator
{
    /**
     * @var CsvFile
     */
    protected $csvFile;

    /**
     * @var array
     */
    protected $header;

    public function __construct(
        $fileName,
        $delimiter = CsvFile::DEFAULT_DELIMITER,
        $enclosure = CsvFile::DEFAULT_ENCLOSURE,
        $escapedBy = ""
    ) {
        //Bisogna gestire il caso in cui il costruttore venga chiamato dalla getModelInstance di Magento, che passa
        //solo un array come parametro
        if (is_array($fileName)) {
            $array = $fileName;
            unset($fileName);
            if (!array_key_exists(0, $array)) {
                throw new \InvalidArgumentException("At least the file name must be supplied");
            }
            $fileName = $array[0];
            if (array_key_exists(1, $array)) {
                $delimiter = $array[1];
            }
            if (array_key_exists(2, $array)) {
                $enclosure = $array[2];
            }
            if (array_key_exists(3, $array)) {
                $escapedBy = $array[3];
            }
        }

        $this->csvFile = new CsvFile($fileName, $delimiter, $enclosure, $escapedBy);
        $this->header = $this->csvFile->getHeader();
        $this->csvFile->next();
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

        $currentLine = $this->csvFile->current();
        if (count($currentLine) != count($this->header)) {
            throw new \LogicException(
                sprintf(
                    'Cannot fetch CSV row, header columns count do not match. Current row is: %s',
                    print_r($currentLine, true)
                )
            );
        }

        $this->processMultipleValues($currentLine);

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
        $this->csvFile->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->csvFile->key();
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
        return $this->csvFile->valid();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->csvFile->rewind();
        $this->csvFile->next();
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
