Associative Spreadsheet Iterator
================================

This PHP library allows you to iterate over a spreadsheet in an associative way. Every iterated row is indexed by column name. All formats supported by [phpoffice/phpexcel](https://github.com/PHPOffice/PHPExcel) are supported.

For example, given a spreadsheet like this:

<table>
<thead>
<tr>
<th>Name</th>
<th>Description</th>
<th>Price</th>
<th>Stock</th>
</tr>
</thead>
<tbody>
<tr>
<td>RaspberryPi</td>
<td>Raspberry PI Modell B, 512 MB</td>
<td>37.05</td>
<td>12</td>
</tr>
<tr>
<td>SanDisk Ultra SDHC</td>
<td>SanDisk Ultra SDHC 8 GB 30 MB/s Classe 10</td>
<td>6.92</td>
<td>54</td>
</tr>
</tbody>
</table>

You can iterate over this and get every row as associative array like this:

```
array(
    array(
        'Name' => 'RaspberryPi',
        'Description' => 'Raspberry PI Modell B, 512 MB',
        'Price' => 37.05,
        'Stock' => 12,
    ),
    array(
        'Name' => 'SanDisk Ultra SDHC',
        'Description' => 'SanDisk Ultra SDHC 8 GB 30 MB/s Classe 10',
        'Price' => 6.92,
        'Stock' => 54,
    ),
),
```

Installation
------------

You can install this library with **Composer**:

	$ composer require webgriffe/associative-spreadsheet-iterator @stable
	
Usage
-----

Simply inlcude Composer's autoloader and instantiate the iterator passing the file path:

```
<?php

require 'vendor/autoload.php'

$file = '/path/to/spreadsheet.xlsx';
$spreadsheetIterator = new Webgriffe\AssociativeSpreadsheetIterator\Iterator($file);

foreach ($iterator as $row) {
	// $row is an associative array indexed by column name
}
```