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
    const DEFAULT_CHUNK_SIZE = 100;

    private $startRow = 0;
    private $endRow = 0;

    protected $chunkSize = self::DEFAULT_CHUNK_SIZE;

    public function __construct($startRow = 0, $chunkSize = null)
    {
        $this->setRows($startRow, $chunkSize);
    }

    public function setRows($startRow, $chunkSize = null)
    {
        if (!is_null($chunkSize)) {
            $this->chunkSize = $chunkSize;
        }

        $this->startRow = $startRow;
        $this->endRow = $startRow + $this->chunkSize;
    }

    public function getCurrentChunkSize()
    {
        return $this->chunkSize;
    }

    public function getDefaultChunkSize()
    {
        return self::DEFAULT_CHUNK_SIZE;
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
