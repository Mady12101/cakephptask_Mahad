<?php
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Psy\Shell as PsyShell;

// Function to perform XOR-based encryption
function simpleEncrypt($text) {
    $key = 42; // Secret key for XOR
    $encrypted = '';
    for ($i = 0; $i < strlen($text); $i++) {
        $encrypted .= chr(ord($text[$i]) ^ $key);
    }
    return base64_encode($encrypted);
}

// Function to perform XOR-based decryption
function simpleDecrypt($encryptedText) {
    $key = 42; // Secret key for XOR
    $encryptedText = base64_decode($encryptedText);
    $decrypted = '';
    for ($i = 0; $i < strlen($encryptedText); $i++) {
        $decrypted .= chr(ord($encryptedText[$i]) ^ $key);
    }
    return $decrypted;
}

// Function to fetch and store data from the API
function fetchAndStoreData() {
    $apiUrl = 'https://api.adgatemedia.com/v3/offers/?aff=48864&api_key=155efa664a706f295fb446570041d707&wall_code=o6qb';
    $jsonData = file_get_contents($apiUrl);
    $response = json_decode($jsonData, true);

    if (isset($response['status']) && $response['status'] === 'success') {
        $data = $response['data'];
    }
    // Connect to your database (Replace placeholders with actual database info)
    $connection = new mysqli('localhost', 'mahad', '1234mahad', 'mysql');
    if ($data !== null) {
        foreach ($data as $item) {
            $name = $connection->real_escape_string($item['name']);
            $requirements = $connection->real_escape_string($item['requirements']);
            $description = $connection->real_escape_string($item['description']);
            $ecpc = (float) $item['epc'];
            $clickUrl = $connection->real_escape_string($item['click_url']);

            // Encrypt sensitive data using the simple encryption technique
            $encryptedName = simpleEncrypt($name);
            $encryptedRequirements = simpleEncrypt($requirements);
            $encryptedDescription = simpleEncrypt($description);

            $query = "INSERT INTO api_data (encrypted_name, encrypted_requirements, encrypted_description,ecpc, click_url) 
                      VALUES ('$encryptedName', '$encryptedRequirements', '$encryptedDescription',$ecpc, '$clickUrl')";
            $connection->query($query);
        }
    } else {
        echo "API data is empty or null.";
    }

    $connection->close();
    echo "API data fetched and stored.";
}

// Main execution
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    try {
        fetchAndStoreData();
        echo "Data fetched and stored successfully.";
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>API Data Display</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/yadcf@0.9.5/jquery.dataTables.yadcf.js"></script>
	




<!-- Include DataTables Search Plugins -->


	<!-- Include jQuery -->

<!-- Include DataTables -->

<!-- Include jQuery UI (for Autocomplete) -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

</head>
<body>

<h1>API Data Display</h1>

<!-- Fetch API data button -->
<a href="?action=fetch">Fetch and Store API Data</a>

<!-- Display API data using DataTables -->
<table id="apiDataTable" class="display">
    <thead>
        <tr>
            <th data-orderable="false">Name</th>
            <th data-orderable="false">Requirements</th>
            <th data-orderable="false">Description</th>
            <th>ECPC</th> <!-- New column for ECPC -->
            <th data-orderable="false">Click URL</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Connect to your database (Replace placeholders with actual database info)
        $connection = new mysqli('localhost', 'mahad', '1234mahad', 'mysql');

        // Retrieve and display data (Replace with your actual database retrieval logic)
        $result = $connection->query("SELECT * FROM api_data");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . simpleDecrypt($row['encrypted_name']) . "</td>";
            echo "<td>" . simpleDecrypt($row['encrypted_requirements']) . "</td>";
            echo "<td>" . simpleDecrypt($row['encrypted_description']) . "</td>";
            echo "<td>" . $row['ecpc'] . "</td>"; // Display ECPC value
            echo "<td><a href='" . $row['click_url'] . "' target='_blank'>Open</a></td>";
            echo "</tr>";
        }
        
        $connection->close();
        ?>
    </tbody>
</table>
<script>
$(document).ready(function() {
	  $('#apiDataTable_filter input[type="search"]').css('display', 'none');

}
</script>
<script>
$(document).ready(function() {
  var table = $('#apiDataTable').DataTable();
   
  // Add an input field for autocomplete
  $('<input type="text" id="autocompleteInput" placeholder="Search with Auto-Suggest">')
    .appendTo("#apiDataTable_filter")
    .autocomplete({
      source: function(request, response) {
        // Get unique values from the DataTable's first column (index 0)
        var searchData = table
          .column(0, { search: "applied" })
          .data()
          .unique()
          .sort()
          .toArray();
        var term = request.term.toLowerCase();
        var suggestions = [];

        // Filter unique values to find matches
        searchData.forEach(function(value) {
          if (value.toLowerCase().indexOf(term) !== -1) {
            suggestions.push(value);
          }
        });

        response(suggestions);
      },
      select: function(event, ui) {
        // When an item is selected, perform a DataTables search
        table.column(0).search(ui.item.value).draw();
      },
    });
});
</script>



</body>
</html>
