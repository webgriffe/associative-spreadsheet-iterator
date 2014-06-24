<?php
/**
 * Created by PhpStorm.
 * User: andrea
 * Date: 23/06/14
 * Time: 10.42
 */

namespace Webgriffe\AssociativeSpreadsheetIterator;

class ChunkReadFilter implements \PHPExcel_Reader_IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    protected $defaultChunkSize = 100;

    public function __construct($startRow = 0, $chunkSize = null)
    {
        $this->setRows($startRow, $chunkSize);
    }

    public function setRows($startRow, $chunkSize = null)
    {
        if (is_null($chunkSize)) {
            $chunkSize = $this->defaultChunkSize;
        }

        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function getCurrentChunkSize()
    {
        return $this->endRow - $this->startRow;
    }

    public function getDefaultChunkSize()
    {
        return $this->defaultChunkSize;
    }

    public function setDefaultChunkSize($size)
    {
        $this->defaultChunkSize = $size;
    }

    public function getCurrentStartRow()
    {
        return $this->startRow;
    }

    public function getCurrentEndRow()
    {
        return $this->endRow;
    }

    /**
     * {@inheritdoc}
     */
    public function readCell($column, $row, $worksheetName = '')
    {
        return ($row == 1 || ($row >= $this->startRow && $row < $this->endRow));
    }
}
