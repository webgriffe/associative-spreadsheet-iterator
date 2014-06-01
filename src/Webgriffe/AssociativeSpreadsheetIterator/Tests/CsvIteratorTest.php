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
        $csvContent = <<<CSV
"column1","column2","column3"
"there are","fewer columns than header"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $worksheetIterator = new Iterator($filePath);
        $this->assertDifferentHeaderAndValuesColumnCountIteratorFails($worksheetIterator);
    }

    public function testIterateShouldProcessMultipleValuesCell()
    {
        $csvContent = <<<CSV
"column1","column2"
"there are","multiple|values"
CSV;
        $filePath = $this->setUpVirtualFileAndGetPath($csvContent);
        $csvIterator = new Iterator($filePath);

        $result = array();
        foreach ($csvIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(
            array(
                array(
                    'column1' => 'there are',
                    'column2' => array('multiple', 'values'),
                )
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
}
