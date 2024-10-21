<?php
require(__DIR__.'/../csv.php');


// gettings arguments
if (!is_array($argv) || ($i = count($argv)) < 3) {
    $script_name = basename($argv[0]);
    echo <<<END
Usage: $script_name [source_file.csv] [target_file.json]

END;
  exit;
}


// GO!
// TODO: get $columns_to_export from the command-line arguments

if ($data_arr = @Csv::read($argv[1], [2 => ['lat', 'float'], 3 => ['lng', 'float'], 4 => ['r', 'float']], // columns 2, 3 and 4.

// Alternatively use null's instead of property names to get the set of pure arrays instead of objects with the 'key: value' pairs:
//if ($data_arr = @Csv::read($argv[1], [2 => [null, 'float'], 3 => [null, 'float'], 4 => [null, 'float']], // columns 2, 3 and 4.

        // we could do this in post-processing with array_map(), but this is more memory-efficient way...
        function(&$row, $row_index) {
            if ($row_index < 1) {
                // skip 1st header line
                return false;
            }

            // Just example of converting some value....
            // ...turn Longitude for the Western Hemisphere into negative values
            if (isset($row[3])) {
                $row[3] = (float)$row[3];
                if ($row[3] >= 180) {
                    $row[3] = 180 - $row[3];
                }
            }

            return true;
        })) {

    file_put_contents($argv[2], json_encode($data_arr, JSON_UNESCAPED_UNICODE));
}
