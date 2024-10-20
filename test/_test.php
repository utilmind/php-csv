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

if ($data_arr = @Csv::read($argv[1], [2 => ['lng', 'float'], 3 => ['lat', 'float'], 4 => ['rad', 'int']])) {
    file_put_contents($argv[2], json_encode($data_arr, JSON_UNESCAPED_UNICODE));
}