<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeSpreadsheetIterator\Tests;

use Webgriffe\AssociativeSpreadsheetIterator\Iterator;

class XlsxIteratorTest extends IteratorTestCase
{
    public function testIterateShouldReturnProperArray()
    {
        $filePath = __DIR__ . '/Fixtures/test.xlsx';

        $csvIterator = new Iterator($filePath);
        $result = array();
        foreach ($csvIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(
            array(
                array(
                    'sku' => '1234567',
                    '_type' => 'simple',
                    '_attribute_set' => 'Default',
                    '_product_websites' => 'base',
                    'name' => 'My Simple Product 1',
                    'price' => 0.99,
                    'status' => 1,
                    'qty' => 1000,
                ),
                array(
                    'sku' => '1234568',
                    '_type' => 'simple',
                    '_attribute_set' => 'Default',
                    '_product_websites' => 'base',
                    'name' => 'My Simple Product 2',
                    'price' => 0.79,
                    'status' => 1,
                    'qty' => 500,
                ),
            ),
            $result
        );
    }

    public function testIterateShouldFailDueToHeaderAndValuesDifferentColumnCount()
    {
        $filePath = __DIR__ . '/Fixtures/test-different-column-count.xlsx';
        $worksheetIterator = new Iterator($filePath, null, null, 1, null, false);

        $this->setExpectedException('\LogicException');
        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
    }

    public function testIterateShouldNotFailDueToHeaderAndValuesDifferentColumnCount()
    {
        $filePath = __DIR__ . '/Fixtures/test-different-column-count.xlsx';
        $worksheetIterator = new Iterator($filePath);

        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
        $this->assertEquals(
            array(
                array(
                    'column1' => 'there are',
                    'column2' => 'fewer columns than header',
                    'column3' => null,
                ),
            ),
            $result
        );
    }

    public function testFileWithOnlyHeading()
    {
        $filePath = __DIR__ . '/Fixtures/test-only-heading.xlsx';
        $onlyHeadingIterator = new Iterator($filePath);
        $this->assertOnlyHeadingIteratorIteratesOnEmptyArray($onlyHeadingIterator);
    }

    public function testEmptyFile()
    {
        $filePath = __DIR__ . '/Fixtures/test-empty.xlsx';
        $emptyIterator = new Iterator($filePath);
        $this->assertEmptyIteratorIsNotValidAndIteratesOnEmptyArray($emptyIterator);
    }
}
