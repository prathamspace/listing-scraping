
$string = "3960 Woburn Court, Palmdale, CA 93551";

// Split the string into parts using commas
$parts = explode(', ', $string);

// Extract and store each part in a specific key of the $data array
$data = array(
    'street'  => $parts[0], // Assuming the street is always the first part
    'region'  => $parts[1],
    'country' => '',        // Initialize country as an empty string
    'zip'     => ''
);

// Check if there is a third part (country and zip)
if (isset($parts[2])) {
    // Split the third part into country and zip
    $countryZip = explode(' ', $parts[2]);
    
    // Assign values to country and zip
    $data['country'] = $countryZip[0];
    $data['zip']     = isset($countryZip[1]) ? $countryZip[1] : '';
}
echo "<pre>";
// Display the resulting array
print_r($data);



// die;
