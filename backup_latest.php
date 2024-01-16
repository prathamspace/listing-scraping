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



include_once('simple_html_dom.php');

// Declare $all_data as a global variable
$all_data = [];


  
function scrape_data()
{
    global $all_data; // Use the global keyword to access the global variable

    $url = 'https://www.seniorly.com/assisted-living/california/acton';
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        echo "error";
    } else {
        $html = wp_remote_retrieve_body($response);

        // Load HTML content into a DOM object
        $dom = new \simple_html_dom();
        $dom->load($html);

        // Find and print elements based on specified criteria
        foreach ($dom->find('article') as $article) {
            $data = []; // Create an array to store data for each article

            // Check if article has an h2 with title attribute
            $h2WithTitle = $article->find('h2[title]', 0);
            if ($h2WithTitle) {
                $data['title'] = $h2WithTitle->plaintext;

                // Find the span tag that is a sibling of h2
                $spanSiblingOfH2 = $h2WithTitle->next_sibling();
                if ($spanSiblingOfH2 && $spanSiblingOfH2->tag === 'span') {
                    // Use regular expressions to extract address components
                    
                    die;
                    $addressString = $spanSiblingOfH2->plaintext;
                    echo $addressString;
                    die;
                    // Attempt to extract street, region, country, and zip
                    if (preg_match('/(.*?),\s*(.*?),\s*(.*?)(?:,\s*(.*))?/', $addressString, $matches)) {
                        $data['street'] = trim($matches[1]);
                        $data['region'] = trim($matches[2]);
                        $data['country'] = isset($matches[3]) ? trim($matches[3]) : '';
                        $data['zip'] = isset($matches[4]) ? trim($matches[4]) : '';
                    }
                }
            }

            // Store content of span tags without images
            foreach ($article->find('span:not(:has(img))') as $span) {
                if (!$span->class || !in_array('font-b-xs', explode(' ', $span->class))) {
                    continue;
                }

                $data['description'] = $span->plaintext;
            }

            // Check if article has a div with data-testid="card-description"
            $divWithTestId = $article->find('div[data-testid="card-description"]', 0);
            if ($divWithTestId) {
                $data['description'] = $divWithTestId->plaintext;
            }

            // Check for the price div
            $priceDiv = $article->find('div.inline.font-t-xs-azo.font-medium', 0);
            if ($priceDiv) {
                $data['price'] = $priceDiv->plaintext;
            }

            // Add the data for the current article to the global $all_data array
            $all_data[] = $data;
        }

        // Clean up the DOM object
        $dom->clear();
        unset($dom);
    }
}

scrape_data();

// Output the resulting $all_data array for testing
echo "</pre>";
print_r($all_data);



// print_r($addresses);
add_shortcode('wordpress_pratham_scrape_data', 'scrape_data');