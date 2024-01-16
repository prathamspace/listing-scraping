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
// Declare $addresses as a global variable
    $addresses = [];

function scrape_data()
{
    global $addresses; // Use the global keyword to access the global variable

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
            // Check if article has an h2 with title attribute
            $h2WithTitle = $article->find('h2[title]', 0);
            if ($h2WithTitle) {
                echo '<h2>Title: ' . $h2WithTitle->plaintext . '</h2>';

                // Find the span tag that is a sibling of h2
                $spanSiblingOfH2 = $h2WithTitle->next_sibling();
                if ($spanSiblingOfH2 && $spanSiblingOfH2->tag === 'span') {
                    // Store span content in the global $addresses array
                    $addresses[] = $spanSiblingOfH2->plaintext;
                    echo 'Address: ' . $spanSiblingOfH2->plaintext . '<br>';
                }
            }

            // Print content of span tags without images
            foreach ($article->find('span:not(:has(img))') as $span) {
                // Exclude span tags with specific classes
                if (!$span->class || !in_array('font-b-xs', explode(' ', $span->class))) {
                    continue;
                }

                // Store span content in the global $addresses array
                $addresses[] = $span->plaintext;
                echo 'Span Content: ' . $span->plaintext . '<br>';
            }

            // Check if article has a div with data-testid="card-description"
            $divWithTestId = $article->find('div[data-testid="card-description"]', 0);
            if ($divWithTestId) {
                echo 'Card Description: ' . $divWithTestId->plaintext . '<br>';
            }

            // Check for the price div
            $priceDiv = $article->find('div.inline.font-t-xs-azo.font-medium', 0);
            if ($priceDiv) {
                echo 'Price: ' . $priceDiv->plaintext . '<br>';
            }
        }

        // Clean up the DOM object
        $dom->clear();
        unset($dom);
    }
}

scrape_data();

echo "<pre>";
print_r($addresses);
add_shortcode('wordpress_pratham_scrape_data', 'scrape_data');