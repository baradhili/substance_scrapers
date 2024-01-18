<?php

// The URL from which to fetch the data
$url = "http://www.toxinology.com/fusebox.cfm?fuseaction=main.snakes.results&Common_Names_term=&Family_term=&Incidence_Key_term=&Genus_term=&Species_term=&countries_terms=MAT&region_terms=";

// Use file_get_contents to fetch the HTML content from the URL
$htmlContent = file_get_contents($url);

// Create a new DOMDocument and load the HTML content
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Disable warnings from malformed HTML
$dom->loadHTML($htmlContent);
libxml_clear_errors(); // Clear errors after parsing

// Create a new DOMXPath object
$xpath = new DOMXPath($dom);

// Query the DOM for all <table> elements
$tables = $xpath->query('//table');

// Check if there are at least two tables
if ($tables->length > 1) {
    // Get the second table
    $secondTable = $tables->item(1);

    // Now, find the embedded <table> within the second table
    $embeddedTables = $xpath->query('.//table', $secondTable);

    // Check if there is at least one embedded table
    if ($embeddedTables->length > 0) {
        // Get the first embedded table
        $targetTable = $embeddedTables->item(0);

        // Process the target table
        $rows = $xpath->query('.//tr', $targetTable);
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            foreach ($cells as $cell) {
                echo $cell->nodeValue . "\t"; // Print cell value with a tab delimiter
            }
            echo "\n"; // Newline for each row
        }
    } else {
        echo "Embedded table not found within the second table.\n";
    }
} else {
    echo "Second table not found.\n";
}

?>
