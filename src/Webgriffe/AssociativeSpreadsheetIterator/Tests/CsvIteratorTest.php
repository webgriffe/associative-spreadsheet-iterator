<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeSpreadsheetIterator\Tests;

use Webgriffe\AssociativeSpreadsheetIterator\Iterator;

class CsvIteratorTest extends IteratorTestCase
{
    public function testIterateShouldReturnProperArray()
    {
        $csvContent = <<<CSV
"sku";"_type";"_attribute_set";"_product_websites";"name";"price";"status";"qty"
"1234567";"simple";"Default";"base";"My Simple Product 1";0.99;1;1000
"1234568";"simple";"Default";"base";"My Simple Product 2";0.79;1;500
CSV;

        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);

        $csvIterator = new Iterator($filePath, ';');
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
        $this->markTestSkipped();

        $csvContent = <<<CSV
"column1","column2","column3"
"there are","fewer columns than header"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $worksheetIterator = new Iterator($filePath);

        $this->setExpectedException('\LogicException');
        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
    }

    public function testIterateShouldNotFailDueToHeaderAndValuesDifferentColumnCount()
    {
        $csvContent = <<<CSV
"column1","column2","column3"
"there are","fewer columns than header"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
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

    public function testIterateShouldFailDueToTooLongValues()
    {
        $this->markTestSkipped();

        $csvContent = <<<CSV
"column1","column2","column3"
"there are","more","columns","than header"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $worksheetIterator = new Iterator($filePath);

        $this->setExpectedException('\LogicException');
        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
    }

    public function testIterateShouldNotFailDueToTooLongValues()
    {
        $csvContent = <<<CSV
"column1","column2","column3"
"there are","more","columns","than header"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $worksheetIterator = new Iterator($filePath);

        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
        $this->assertEquals(
            array(
                array(
                    'column1' => 'there are',
                    'column2' => 'more',
                    'column3' => 'columns',
                ),
            ),
            $result
        );
    }

    public function testIterateShouldNotFailDueToEmptyCells()
    {
        $csvContent = <<<CSV
"column1","column2","column3"
"one",,"three"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $worksheetIterator = new Iterator($filePath);

        $result = array();
        foreach ($worksheetIterator as $row) {
            $result[] = $row;
        }
        $this->assertEquals(
            array(
                array(
                    'column1' => 'one',
                    'column2' => null,
                    'column3' => 'three',
                ),
            ),
            $result
        );
    }

    public function testFileWithOnlyHeading()
    {
        $csvContent = <<<CSV
"column1","column2"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $onlyHeadingIterator = new Iterator($filePath);
        $this->assertOnlyHeadingIteratorIteratesOnEmptyArray($onlyHeadingIterator);
    }

    public function testEmptyFile()
    {
        $filePath = $this->setUpVirtualFileAndGetPath('');
        $emptyIterator = new Iterator($filePath);
        $this->assertEmptyIteratorIsNotValidAndIteratesOnEmptyArray($emptyIterator);
    }

    public function chunkChangeDataProvider()
    {
        return array(
            array(98),
            array(99),
            array(100),
            array(101),
            array(102),
            array(202),
        );
    }

    /**
     * @dataProvider chunkChangeDataProvider
     */
    public function testChunkChange($rowNumber)
    {
        $csvContent = $this->getContent($rowNumber);
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $csvIterator = new Iterator($filePath, ';');

        //Eseguilo due volte per testare che anche il reset funzioni correttamente
        for ($i = 0; $i < 2; ++$i) {
            $count = 0;
            $csvIterator->rewind();
            while ($csvIterator->valid()) {
                ++$count;
                $this->assertEquals(array('col1' => "val1-$count", 'col2' => "val2-$count"), $csvIterator->current());
                $this->assertEquals($count, $csvIterator->key());
                $csvIterator->next();
            }
            $this->assertEquals($rowNumber, $count);
        }
    }

    public function seekChunkChangeDataProvider()
    {
        return array(
            array(1),
            array(42),
            array(98),
            array(99),
            array(100),
            array(101),
            array(102),
            array(198),
            array(199),
            array(200),
            array(201),
            array(202, false),
        );
    }

    /**
     * @dataProvider seekChunkChangeDataProvider
     */
    public function testSeekChunkChange($jumpTo, $moreValidElements = true)
    {
        $csvContent = $this->getContent(202);
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $csvIterator = new Iterator($filePath, ';');

        $csvIterator->rewind();

        $csvIterator->seek($jumpTo);
        $this->assertTrue($csvIterator->valid());
        $this->assertEquals(array('col1' => "val1-$jumpTo", 'col2' => "val2-$jumpTo"), $csvIterator->current());
        $this->assertEquals($jumpTo, $csvIterator->key());
        $csvIterator->next();
        if ($moreValidElements) {
            $next = $jumpTo+1;
            $this->assertTrue($csvIterator->valid());
            $this->assertEquals(array('col1' => "val1-$next", 'col2' => "val2-$next"), $csvIterator->current());
            $this->assertEquals($next, $csvIterator->key());
            return;
        }

        $this->assertFalse($csvIterator->valid());
        $csvIterator->rewind();
        $this->assertTrue($csvIterator->valid());
        $this->assertEquals(array('col1' => "val1-1", 'col2' => "val2-1"), $csvIterator->current());
        $this->assertEquals(1, $csvIterator->key());
    }

    /**
     * @param $rowNumber
     * @return string
     */
    private function getContent($rowNumber)
    {
        $csvContent = <<<CSV
"col1";"col2"
CSV;

        for ($i = 1; $i <= $rowNumber; ++$i) {
            $csvContent .= "\nval1-$i;val2-$i";
        }
        return $csvContent;
    }
}
