<?php
// Function to fetch the page
function fetch_page($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development/testing!
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
    }
    curl_close($ch);
    return $output;
}

// Function to parse the HTML table and extract drug details
function parse_table($html)
{
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // XPath query to find each row of the table
    $rows = $xpath->query("//table[@id='drugs-table']/tbody/tr");
    if ($rows === false) {
        echo "XPath query failed.\n";
    } else {
        echo "Found " . $rows->length . " rows.\n";
    }

    $drugs = [];
    foreach ($rows as $row) {
 
        // Extracting each cell in the row
        $nameNode = $xpath->query(".//td[contains(@class, 'name-value')]//a", $row)->item(0);
        if ($nameNode === null) {
            echo "Name node not found.\n";
            continue;
        }
        $name = $nameNode->nodeValue;
        $url = 'https://go.drugbank.com' . $nameNode->getAttribute('href'); // Construct the full URL
        $drug_id = basename($url); // Extracting DrugBank ID from URL

        $weight = $xpath->query(".//td[@class='weight-value']", $row)->item(0)->nodeValue;
        $parts = explode(' ', trim($weight));
        $structure = end($parts);
        $description = $xpath->query(".//td[@class='description-value']", $row)->item(0)->nodeValue;
        $categories = $xpath->query(".//td[@class='categories-value']", $row)->item(0)->nodeValue;

        $drugs[] = [
            'name' => trim($name),
            'drug_id' => trim($drug_id),
            'structure' => strip_tags(trim($structure)),
            'description' => trim($description),
            'categories' => trim($categories),
            'url' => $url
        ];
    }
    return $drugs;
}

// Main loop for pagination
$base_url = "https://go.drugbank.com/drugs";
$params = [
    'approved' => '1',
    'c' => 'name',
    'ca' => '0',
    'd' => 'up',
    'eu' => '1',
    'experimental' => '1',
    'illicit' => '1',
    'investigational' => '1',
    'nutraceutical' => '1',
    'page' => 1, // Start with page 1
    'us' => '0',
    'withdrawn' => '1'
];

$all_drugs = [];

// Fetch the first page to get the total number of drugs
$query = http_build_query($params);
$html = fetch_page("$base_url?$query");
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

// Extract the total number of drugs
$totalDrugsNode = $xpath->query("//div[@class='page_info']/b[last()]")->item(0);
if ($totalDrugsNode === null) {
    echo "Total drugs node not found.\n";
    exit;
}
$totalDrugs = intval($totalDrugsNode->nodeValue);
echo "Total number of drugs: $totalDrugs\n";

// Assuming 25 drugs per page (adjust this number if it's different)
$drugsPerPage = 25;
$totalPages = ceil($totalDrugs / $drugsPerPage);
echo "Total number of pages: $totalPages\n";

for ($page = 1; $page <= $totalPages; $page++) { // Example: Scrape first 10 pages
    $params['page'] = $page;
    $query = http_build_query($params);
    echo "Fetching URL: $base_url?$query\n";
    $html = fetch_page("$base_url?$query");
    if (empty($html)) {
        echo "Failed to fetch the page or the page is empty.\n";
    } else {
        echo "Page fetched successfully.\n";
    }

    echo "Parsing the page...\n";
    $drugs = parse_table($html);

    $all_drugs = array_merge($all_drugs, $drugs);

    // Sleep to prevent overwhelming the server
    echo ("snooze");
    sleep(10);
}

// Output the results
// Convert the $all_drugs array to a JSON-formatted string
$json_data = json_encode($all_drugs, JSON_PRETTY_PRINT);

// Output the JSON string
echo $json_data;

// Optionally, save the JSON data to a file
file_put_contents('drugs_data.json', $json_data);
?>