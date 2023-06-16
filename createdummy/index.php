<?php

ini_set('memory_limit', '-1');

function get_usage_in_kb(){
    echo memory_get_usage()/1024.0 . " kb \n";
}

// Load the PhpSpreadsheet library
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generateRandomDate($startYear, $endYear) {
  $startTimestamp = strtotime($startYear . '-01-01');
  $endTimestamp = strtotime($endYear . '-12-31');
  $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
  $randomDate = date('ymd', $randomTimestamp);
  return $randomDate;
}

function generateRandomString() {
  $date = generateRandomDate(1950, 2005);
  $randomNumber = str_pad(mt_rand(1, 14), 2, "0", STR_PAD_LEFT);
  $randomDigits = rand(1000, 9999);
  $randomString = $date . $randomNumber . $randomDigits;
  return $randomString;
}

// Example usage
// echo generateRandomString();

// Define the number of rows
$numRows = 10000000;

// Get the highest row number
$highestRow2 = $numRows;

// Define the chunk size
$chunkSize2 = 10000;

// Loop through the rows in chunks
for ($startRow2 = 1; $startRow2 <= $highestRow2; $startRow2 += $chunkSize2) {
    $endRow2 = $startRow2 + $chunkSize2 - 1;
    if ($endRow2 > $highestRow2) {
        $endRow2 = $highestRow2;
    }

  // Set the data for each row
  for ($i2 = $startRow2-1; $i2 < $endRow2; $i2++) {
      $data[] = generateRandomString();
  }
}

// get_usage_in_kb();

unset($highestRow2);
unset($chunkSize2);
unset($startRow2);
unset($endRow2);
unset($i2);

// get_usage_in_kb();

// echo memory_get_peak_usage(TRUE)/1024, ' kb', "\n";

// Generate the data
/*
for ($i = 1; $i <= $numRows; $i++) {
    $data[] = generateRandomString(); // add the prefix to the number and store in the data array
}
*/

/*
// Define the prefix
$prefix = '011';

// Define the number of rows
$numRows = 800000;

// Generate the data
for ($i = 1; $i <= $numRows; $i++) {
    $randomNumber = rand(10000000, 99999999); // generate a random 8-digit number
    $data[] = $prefix . $randomNumber; // add the prefix to the number and store in the data array
}

// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '012' . substr($data[$index], 3); // update the prefix
}
// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '013' . substr($data[$index], 3); // update the prefix
}
// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '016' . substr($data[$index], 3); // update the prefix
}
// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '017' . substr($data[$index], 3); // update the prefix
}
// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '018' . substr($data[$index], 3); // update the prefix
}
// Update the prefix for some rows
for ($i = 0; $i < 50000; $i++) {
    $index = rand(0, $numRows - 1); // choose a random index
    $data[$index] = '019' . substr($data[$index], 3); // update the prefix
}
*/

// Create a new Excel file
$spreadsheet = new Spreadsheet();

// Set the active worksheet
$worksheet = $spreadsheet->getActiveSheet();

// Set the column header
$worksheet->setCellValue('B1', 'ic');

// Get the highest row number
// $highestRow = $worksheet->getHighestRow();
$highestRow = $numRows;

// Define the chunk size
$chunkSize = 10000;

// Loop through the rows in chunks
for ($startRow = 1; $startRow <= $highestRow; $startRow += $chunkSize) {
    $endRow = $startRow + $chunkSize - 1;
    if ($endRow > $highestRow) {
        $endRow = $highestRow;
    }
/*
    // Build the range of rows to process
    $range = 'B' . $startRow . ':B' . $endRow;

    // Get the values for the range
    $columnValues = $worksheet->rangeToArray($range, null, true, true, true);

    // Process the search query for each row in the range
    foreach ($columnValues as $row => $cell) {
        $columnValue = $cell['B'];

        // Build the MySQL query
        $sql = "SELECT * FROM your_table WHERE column_name = '" . $conn->real_escape_string($columnValue) . "'";

        // Execute the query
        $result = $conn->query($sql);

        // Process the result
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // do something with the data
            }
        }
    }
*/
	// Set the data for each row
	for ($i = $startRow-1; $i < $endRow; $i++) {
	    $row = $i + 2; // add 1 to account for the header row
	    $worksheet->setCellValue('B' . $row, $data[$i]);
	}
}

// Save the Excel file
$writer = new Xlsx($spreadsheet);
$writer->save('dummy_data_' . time() . '.xlsx');

?>