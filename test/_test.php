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

if ($data_arr = @Csv::read($argv[1], [2 => ['lat', 'float'], 3 => ['lng', 'float'], 4 => ['r', 'float']],
        // we could do this is in post-processing with array_map(), but this is more memory-efficient way...
        function(&$row, $row_index) {
            if ($row_index < 1) {
                // skip 1st header line
                return false;
            }

            // convert value
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
