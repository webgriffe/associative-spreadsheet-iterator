<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeSpreadsheetIterator\Tests;

use org\bovigo\vfs\vfsStream;
use Webgriffe\AssociativeSpreadsheetIterator\Iterator;

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

    protected function assertOnlyHeadingIteratorIteratesOnEmptyArray(Iterator $onlyHeadingIterator)
    {
        $this->assertFalse($onlyHeadingIterator->valid());

        $result = array();
        foreach ($onlyHeadingIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }

    protected function assertEmptyIteratorIsNotValidAndIteratesOnEmptyArray(Iterator $emptyIterator)
    {
        $this->assertFalse($emptyIterator->valid());

        $result = array();
        foreach ($emptyIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }

    public function testMultipleSheet()
    {
        // TODO testMultipleSheet
        $this->markTestIncomplete();
    }

    public function testHeadersNameWhenHeaderNotPresent()
    {
        // TODO testHeadersNameWhenHeaderNotPresent
        $this->markTestIncomplete();
    }
}
