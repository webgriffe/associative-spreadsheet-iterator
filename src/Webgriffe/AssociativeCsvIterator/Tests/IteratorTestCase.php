<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com>
 */

namespace Webgriffe\AssociativeCsvIterator\Tests;


use org\bovigo\vfs\vfsStream;

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
}