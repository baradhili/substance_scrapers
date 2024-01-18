<?php

// Read the drugs_data.json file
$drugsJson = file_get_contents('drugs_data.json');
$drugsData = json_decode($drugsJson, true);

echo ("loaded file");
// Open a file for writing
$outputFile = 'mixture-Products.json';
$fileHandle = fopen($outputFile, 'w');

// Write the opening bracket for the JSON array
fwrite($fileHandle, "{\n");

foreach ($drugsData as $index => $drug) {
    $drugId = $drug['drug_id']; // Assuming 'drug_id' is the key in your JSON structure
    $baseUrl = "https://go.drugbank.com/drugs/$drugId/products.json?group=mixtures";

    echo ("fetching drug $drugId");
    // Parameters for paging
    $start = 0;
    $length = 5; // Number of records per page

    // Placeholder for all products for the current drug
    $allProducts = [];

    do {
        // Construct the URL with the current 'start' and 'length' parameters
        $url = $baseUrl . "&start=$start&length=$length&search[value]=&search[regex]=false&_=" . (1705457488903 + $start);

        // Fetch the JSON data from the URL
        $jsonData = file_get_contents($url);
        echo ("fetched data $start \n");

        // Decode the JSON data into a PHP object
        $dataObject = json_decode($jsonData);

        // Add the current batch of products to the allProducts array
        foreach ($dataObject->data as $product) {
            // Load the HTML content of the country information field
            $countryInfoHtml = $product[7];
            $dom = new DOMDocument();
            @$dom->loadHTML($countryInfoHtml);
            $xpath = new DOMXPath($dom);

            // Query the DOM for the 'title' attribute in the 'img' tag
            $titleNode = $xpath->query("//img/@title")->item(0);
            $country = $titleNode ? $titleNode->nodeValue : "Not found";

            // Split the dosage into numeric part and unit part based on the first space
            $dosageParts = explode(' ', $product[2], 2);
            $dosage = $dosageParts[0] ?? ''; // The numeric part of the dosage
            $dosageUnits = $dosageParts[1] ?? ''; // The unit part of the dosage

            // Load the HTML content of the ingredients field
            $ingredientsHtml = $product[1];
            $dom = new DOMDocument();
            @$dom->loadHTML($ingredientsHtml);
            $xpath = new DOMXPath($dom);

            // Load the HTML content of the ingredients field
            $ingredientsHtml = $product[1];
            // echo("ingredient html - $ingredientsHtml \n");
            $dom = new DOMDocument();
            @$dom->loadHTML($ingredientsHtml);
            $xpath = new DOMXPath($dom);

            // Extract ingredients and their dosages
            $ingredientNodes = $xpath->query("//a");
            $ingredients = [];
            foreach ($ingredientNodes as $node) {
                $ingredientName = $node->nodeValue;
                $dosageNode = $xpath->query("following-sibling::span[@class='text-muted']", $node)->item(0); // Get the following sibling span with class 'text-muted'
                $dosageText = $dosageNode ? $dosageNode->textContent : ''; // Get the text content of the node
                $dosageText = trim(str_replace(['(', ')'], '', $dosageText)); // Remove parentheses and trim spaces
                $dosageParts = explode(' ', $dosageText, 2); // Split the text by space
                $dosage = $dosageParts[0] ?? '';
                $dosageUnits = $dosageParts[1] ?? '';
        
                $ingredients[] = [
                    'name' => $ingredientName,
                    'dosage' => $dosage,
                    'dosageUnits' => $dosageUnits
                ];
            }

            // Only add product information if the country is "EU"
            if ($country === "EU") {
            $allProducts[] = [
                'name' => $product[0],
                'ingredients' => $ingredients,
                'form' => $product[2],
                'route' => $product[3],
                'manufacturer' => $product[4],
                'approval_date' => $product[5],
                'expiry_date' => strip_tags($product[6]),
                'country' => $country
            ];
            // print_r($allProducts);
            }
        }

        // Prepare for the next page
        $start += $length;

        // Continue until all records are fetched
        echo ("snoozing");
        sleep(rand(10, 100));
    } while ($start < $dataObject->recordsTotal);

    // Convert the allProducts array to JSON
    $jsonOutput = json_encode([$drugId => $allProducts], JSON_PRETTY_PRINT);

    // Write the JSON to the file
    fwrite($fileHandle, ($index > 0 ? ",\n" : "") . $jsonOutput);
    echo ("written to file \n");

    // Optional: Free memory
    unset($allProducts);
}

// Write the closing bracket for the JSON object
fwrite($fileHandle, "\n}");

// Close the file
fclose($fileHandle);

echo "Data written to $outputFile\n";

?>