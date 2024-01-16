<?php
/**
 * Plugin Name: WordPress Pratham
 * Description: A scraping plugin
 * Version: 1.0
 * Author: Pratham Kumar
 */

// Check if the request is coming from within WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/** */

include_once('simple_html_dom.php');

// Declare $all_data as a global variable
$all_data = [];


function scrape_data()
{
    global $all_data;

    $url = 'https://www.seniorly.com/assisted-living/california/acton';
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        echo "error";
    } else {
        $html = wp_remote_retrieve_body($response);

        // Use DOMDocument to parse HTML
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Use DOMXPath to query HTML elements
        $xpath = new DOMXPath($dom);

        // Find and print elements based on specified criteria
        foreach ($xpath->query('//article') as $article) {
            $data = [];

            // Extract title
            $h2WithTitle = $xpath->query('.//h2[@title]', $article)->item(0);
            if ($h2WithTitle) {
                $data['post_title'] = $h2WithTitle->textContent;
            }

            // Extract address
            $spanSiblingOfH2 = $h2WithTitle->nextSibling;
            while ($spanSiblingOfH2 && $spanSiblingOfH2->nodeType !== XML_ELEMENT_NODE) {
                $spanSiblingOfH2 = $spanSiblingOfH2->nextSibling;
            }

            if ($spanSiblingOfH2 && $spanSiblingOfH2->tagName === 'span') {
                $addressString = $spanSiblingOfH2->textContent;

                // Attempt to extract street, region, country, and zip
                    $damn= explode(', ', $addressString);
                    $data['street']  = $damn[0]; // Assuming the street is always the first part
                    $data['region']  = $damn[1];
                    $data['country'] = '';        // Initialize country as an empty string
                    $data['zip'   ]  = '';
                
                
                // Check if there is a third part (country and zip)
                if (isset($damn[2])) {
                    // Split the third part into country and zip
                    $countryZip = explode(' ', $damn[2]);
                    
                    // Assign values to country and zip
                    $data['country'] = $countryZip[0];
                    $data['zip']     = isset($countryZip[1]) ? $countryZip[1] : '';
                }
            }

            // Extract description
            $divWithTestId = $xpath->query('.//div[@data-testid="card-description"]', $article)->item(0);
            if ($divWithTestId) {
                $data['post_content'] = $divWithTestId->textContent;
            }

            // Extract price
            $priceDiv = $xpath->query('.//div[contains(@class, "inline") and contains(@class, "font-t-xs-azo") and contains(@class, "font-medium")]', $article)->item(0);
            if ($priceDiv) {
                $data['price'] = $priceDiv->textContent;
            }
            $data['city']=$data['street'];
            $data['post_status']="publish";
            $data['post_author']=1;
            $data['post_type']="gd_place";
            $data['post_category']=2;
            $data['default_category']=2;

            // Add the data for the current article to the global $all_data array
            $all_data[] = $data;
        }
    }
}


scrape_data();

// Output the resulting $all_data array for testing
echo "<pre>";
// print_r($all_data);
echo "</pre>";

add_shortcode('wordpress_pratham_scrape_data', 'scrape_data');



echo plugin_dir_path(__FILE__)."output.csv" ;

// File path for the CSV file
$csvFilePath = plugin_dir_path(__FILE__)."output.csv" ;


// CSV delimiter (you can change it if needed)
$delimiter = ',';

// Open the file in write mode
$file = fopen($csvFilePath, 'w');

// Write the header row
$header = array_keys($all_data[0]);
fputcsv($file, $header, $delimiter);

// Maintain a list of unique titles to check for duplicates
$uniqueTitles = array();

// Write the data rows
foreach ($all_data as $row) {
    // Check for duplicates based on the 'title' field
    if (!in_array($row['post_title'], $uniqueTitles)) {
        // Add the title to the list of unique titles
        $uniqueTitles[] = $row['post_title'];

        // Write the data to the CSV file
        fputcsv($file, $row, $delimiter);
    }
}

// Close the file
fclose($file);

echo "CSV file has been created successfully at: $csvFilePath";
