<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeCsvIterator\Tests;


use org\bovigo\vfs\vfsStream;
use Webgriffe\AssociativeCsvIterator\CsvIterator;

class IteratorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $csvContent
     * @return string
     */
    protected function setUpVirtualFileAndGetPath($csvContent)
    {
        $structure = array(
            'directory' => array(
                'my_file.csv' => $csvContent,
            ),
        );
        vfsStream::setup();
        vfsStream::create($structure);
        return vfsStream::url('root/directory/my_file.csv');
    }

    protected function assertDifferentHeaderAndValuesColumnCountIteratorFails(CsvIterator $differentColumnCountIterator)
    {
        $this->setExpectedException('\LogicException', 'Cannot fetch CSV row, header columns count do not match.');

        $differentColumnCountIterator->rewind();
        $differentColumnCountIterator->valid();
        $differentColumnCountIterator->current();
    }

    protected function assertOnlyHeadingIteratorIteratesOnEmptyArray(CsvIterator $onlyHeadingIterator)
    {
        $this->assertFalse($onlyHeadingIterator->valid());

        $result = array();
        foreach ($onlyHeadingIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }

    protected function assertEmptyIteratorIsNotValidAndIteratesOnEmptyArray(CsvIterator $emptyIterator)
    {
        $this->assertFalse($emptyIterator->valid());

        $result = array();
        foreach ($emptyIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }
}