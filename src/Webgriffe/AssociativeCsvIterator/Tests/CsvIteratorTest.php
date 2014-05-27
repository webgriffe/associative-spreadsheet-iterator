<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeCsvIterator\Test;

use org\bovigo\vfs\vfsStream;
use Webgriffe\AssociativeCsvIterator\CsvIterator;

class CsvIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterateShouldReturnProperArray()
    {
        $csvContent = <<<CSV
"sku";"_type";"_attribute_set";"_product_websites";"name";"price";"status";"qty"
"1234567";"simple";"Default";"base";"My Simple Product 1";0.99;1;1000
"1234568";"simple";"Default";"base";"My Simple Product 2";0.79;1;500
CSV;

        $filePath = $this->setupCsvFileAndGetPath($csvContent);

        $csvIterator = new CsvIterator($filePath, ';');
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
        $filePath = $this->setupCsvFileAndGetPath($csvContent);

        $csvIterator = new CsvIterator($filePath);

        $this->setExpectedException('\LogicException', 'Cannot fetch CSV row, header columns count do not match.');

        $csvIterator->rewind();
        $csvIterator->valid();
        $csvIterator->current();
    }

    public function testIterateShouldProcessMultipleValuesCell()
    {
        $csvContent = <<<CSV
"column1","column2"
"there are","multiple|values"
CSV;
        $filePath = $this->setupCsvFileAndGetPath($csvContent);
        $csvIterator = new CsvIterator($filePath);

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

        $filePath = $this->setupCsvFileAndGetPath($csvContent);
        $csvIterator = new CsvIterator($filePath);

        $this->assertFalse($csvIterator->valid());

        $result = array();
        foreach ($csvIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }

    public function testEmptyFile()
    {
        $filePath = $this->setupCsvFileAndGetPath('');
        $csvIterator = new CsvIterator($filePath);

        $this->assertFalse($csvIterator->valid());

        $result = array();
        foreach ($csvIterator as $row) {
            $result[] = $row;
        }

        $this->assertEquals(array(), $result);
    }

    /**
     * @param $csvContent
     * @return string
     */
    private function setupCsvFileAndGetPath($csvContent)
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
}
