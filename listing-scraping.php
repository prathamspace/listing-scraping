<?php
/**
 * Plugin Name: Listing Scraping
 * Description: A scraping plugin
 * Version: 1.0
 * Author: Pratham Kumar
 */


/**
 * Importing HTML DOM Parser 
 */

include_once('simple_html_dom.php');




/**
 * Check if the request is coming from within WordPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Converting address to Latitude & Longitude
 */

function get_lat_lng_from_address($address)
{

    $api_key = '';
    $address = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] === 'OK' && isset($data['results'][0]['geometry']['location'])) {
            return $data['results'][0]['geometry']['location'];
        } else {
            return false;
        }
    }
}




/**
 * Declare $all_data as a global variable
 */

$all_data = [];

function scrape_data()
{
    global $all_data;

    $url = '';
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

                // Extract address
                $spanSiblingOfH2 = $h2WithTitle->nextSibling;
                while ($spanSiblingOfH2 && $spanSiblingOfH2->nodeType !== XML_ELEMENT_NODE) {
                    $spanSiblingOfH2 = $spanSiblingOfH2->nextSibling;
                }

                if ($spanSiblingOfH2 && $spanSiblingOfH2->tagName === 'span') {
                    $addressString = $spanSiblingOfH2->textContent;

                    // Attempt to extract street, region, country, and zip
                    $damn = explode(', ', $addressString);
                    $data['street'] = $damn[0]; // Assuming the street is always the first part
                    $data['region'] = $damn[1];
                    $data['country'] = '';        // Initialize country as an empty string

                    // Convert address to latitude and longitude
                    $location = get_lat_lng_from_address($addressString);
                    if ($location) {
                        $data['latitude'] = $location['lat'];
                        $data['longitude'] = $location['lng'];
                    }

                    $data['zip'] = '';
                    $data['city'] = $data['street'];
                    // $data['country']='united states';
                    $data['post_status'] = "publish";
                    $data['post_author'] = 1;
                    $data['post_type'] = "gd_place";
                    $data['post_category'] = 77;
                    $data['default_category'] = 2;
                    // Check if there is a third part (country and zip)
                    if (isset($damn[2])) {
                        // Split the third part into country and zip
                        $countryZip = explode(' ', $damn[2]);

                        // Assign values to country and zip
                        $data['country'] = 'united states';
                        $data['zip'] = isset($countryZip[1]) ? $countryZip[1] : '';
                    }
                }
            }

            // Extract description
            $divWithTestId = $xpath->query('.//div[@data-testid="card-description"]', $article)->item(0);
            if ($divWithTestId) {
                // Split the description into lines and take only the first line
                $descriptionLines = explode("\n", $divWithTestId->textContent);
                $data['post_content'] = trim($descriptionLines[0]);
            }

            // Extract price
            $priceDiv = $xpath->query('.//div[contains(@class, "inline") and contains(@class, "font-t-xs-azo") and contains(@class, "font-medium")]', $article)->item(0);
            if ($priceDiv) {
                // Remove "$" and commas from the price and store it as a numeric value
                $price = str_replace(['$', ','], '', $priceDiv->textContent);
                $data['price'] = (int) $price;
            }

            // Check if any value in $data is not empty
            if (!array_filter($data)) {
                // Skip adding empty data to the global array
                continue;
            }

            // Add the data for the current article to the global $all_data array
            $all_data[] = $data;
        }
    }
}

scrape_data();

/** 
 * Log the data on page
 */


 /*
echo "<pre>";
print_r($all_data);
 */


 
/**
 * Output the resulting $all_data array for testing
 */


add_shortcode('wordpress_pratham_scrape_data', 'scrape_data');



echo plugin_dir_path(__FILE__) . "output.csv";

// File path for the CSV file
$csvFilePath = plugin_dir_path(__FILE__) . "output.csv";


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
