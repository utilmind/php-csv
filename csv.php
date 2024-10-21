<?php
/**
 * CSV to JSON convertor
 *
 * @copyright   Copyright (c) 2024, https://github.com/utilmind/
 * @license     MIT License (https://opensource.org/licenses/MIT)
 * @version     1.0.0
 * @since       8.0 (PHP 8.0+ required)
 */

class Csv {
    /**
     * Read CSV file into associative array, that can be encoded into JSON.
     *
     * @param   string  $source_csv_filename    CSV file name to read from the local file system OR remote URL. (@see $filename parameter of `fopen()` function.)
     * @param   array   $columns_to_export      The map of associations.
     *                                          E.g. 1 => 'title', 2 => ['longitude','float'], 3 => ['latitude', 'float'], 4 => ['zoom', 'int'], etc.
     *                                          If the type is omitted, it's considered as 'string'.
     * @param   int | callable  $skip_garbage   Either integer value to start from specified row (e.g. set 1 to skip the top row with header, or set it to 0 if CSV doesn't have any header)
     *                                          ...OR set up custom function, that can verify whether the line is garbage (e.g. if the value in some column has unexpected data type) and skip it.
     *                                          Callback accepts 2 parameters: $csv_row and $row_index. (* Feel free to modify the data row, using it as &$csv_row in the callback.)
     *                                          Callback should return true to skip the certain line.
     * @param   string  $csv_separator          Default is ','. The optional separator parameter sets the field separator (one single-byte character only).
     * @param   string  $csv_enclosure          Default is '"'. The optional enclosure parameter sets the field enclosure character (one single-byte character only).
     * @param   string  $csv_escape             Default is '\'. The optional escape parameter sets the escape character (at most one single-byte character). An empty string ('') disables the proprietary escape mechanism.
     *                                          @see `fgetcsv()` function. Default parameters can be changed in PHP 9 or later.
     *
     * AK TODO:
     *   1. Allow null's, if parameter not speicifed. I think about "?float", "?int" and "?string" for beginning. "?" character in the beginning of type name should allow nulls.
     *
     * @throws Exception if source file doesn't exists.
     *
     * @return array    Associative array (of [keys => values]) that can be encoded into JSON with json_encode().
     */
    public static function read(string $source_csv_filename,
                                ?array $columns_to_export = [],
                                int|callable $skip_garbage = 1,
                                string $csv_separator = ',',
                                string $csv_enclosure = '"',
                                string $csv_escape = ''): array { // ATTN!: default value for escape character in PHP8 is '\', but it's not compliant with RFC 4180, so we set '' explicitly.

        if (!$fh = @fopen($source_csv_filename, 'r')) {
            throw new Exception('CSV file can’t be open.');
        }

        $arr = [];
        try {
            $row_index = 0;
            // Walk through the rows
            while (($csv_row = fgetcsv($fh, 4096, $csv_separator, $csv_enclosure, $csv_escape)) !== false) {
                $cur_row = $row_index++; // get current counter value, before the counter increased.

                // skip header or garbage lines.
                if ($skip_garbage) {
                    if (is_int($skip_garbage)) {
                        if ($cur_row < $skip_garbage) {
                            continue; // skip row
                        }
                     // callable
                    }elseif (!$skip_garbage($csv_row, $cur_row)) {
                        continue; // skip row
                    }
                }

                if ($columns_to_export) {
                    $is_complete_set = true;
                    $arr_row = [];
                    foreach ($columns_to_export as $key => $val) {
                        if (isset($csv_row[$key])) {
                            if (is_array($val)) {
                                $v = $csv_row[$key];
                                if (isset($val[1])) {
                                    switch ($val[1]) { // lowercase only, don't waste time for other cases
                                        case 'float':
                                            $v = (float)$v;
                                            break;

                                        case 'int':
                                        case 'integer':
                                            $v = (int)$v;
                                            break;
                                    }
                                }
                                if ($val[0]) {
                                    $arr_row[$val[0]] = $v;
                                }else {
                                    $arr_row[] = $v;
                                }
                            }else {
                                $arr_row[$val] = $csv_row[$key];
                            }
                        }else { // TODO: allow null
                            $is_complete_set = false;
                            trigger_error("CSV doesn’t have column $key in row $cur_row.", E_USER_WARNING);
                        }
                    }

                    // We don't prepare JSON record if the data set is incomplete.
                    if ($is_complete_set) {
                        $arr[] = $arr_row;
                    }

                }else {
                    $arr[] = $csv_row; // as is, no transformations
                }
            }

        }finally {
            fclose($fh);
        }

        return $arr;
    }

}
