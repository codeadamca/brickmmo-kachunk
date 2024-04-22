<?php
/** This file handles the following API request:
 *      POST /api/input/set-state.php
 * 
 * 
 * This POST request expects the following payload:
 *  {
 *      timestamp: int,
 *      ...input-state-data
 *  }
 */

//const INPUT_STATE_FILE_PATH = "../../input_state.csv";

const DATA_PATH = "../../data";
const MAX_INPUT_STATE_HISTORY_LINES = 10;
const MAX_LINE_CHARACTERS = 128;


//Validate the POST data coming in.
if (
    isset($_POST["timestamp"]) === false ||
    isset($_POST["button_0"]) === false ||
    isset($_POST["button_1"]) === false ||
    isset($_POST["button_2"]) === false ||
    isset($_POST["system"]) === false ||
    isset($_POST["power_lever"]) === false ||
    isset($_POST["joystick_x"]) === false ||
    isset($_POST["joystick_y"]) === false ||
    isset($_POST["dial_0"]) === false
) {

    //If any of the above fields are missing from the POST data, then
    //the payload is invalid and we kill the script.
    echo "Invalid Payload.";
    exit();
}


$panel_status_file = fopen(DATA_PATH . "/panel_status.csv", "w");
if ($panel_status_file === false) {
    echo "Failed to open panel status file for writing.";
    exit();
}


//Now that the panel status csv is loaded, try to see if we need to
//make any updates to the file, if not, then just close it.
$system = $_POST["system"];
$bytes_written = fwrite($panel_status_file, $_POST["power_lever"] . "," . $_POST["system"]);
if ($bytes_written === false) {

    //`fwrite(...)` returns either the number of bytes written or `false`
    //if it failed to write to the file. If it failed to write, then
    //output the error message, close the file handle and kill the script.
    echo "Failed to write to the panel status file.";
    fclose($panel_status_file);
    exit();
}
echo "written to panel status...";
fclose($panel_status_file);


if ($system === "unavailable") {

    //If there is no system selected, don't bother opening the input state
    //file to update.
    exit();
}


//Open the input state text file for reading and writing. Then store a 
//handle to the file object inside `input_state_file`.
$input_state_file = fopen(DATA_PATH . "/$system.csv", "c+");
if ($input_state_file === false) {

    //`fopen(...)` returns the file handle or `false` if it failed to
    //open the file. So in this case, we output an error and kill the
    //script if it failed to open the file.
    echo "Failed to open file for writing.";
    exit();
}


//Next, read the last N lines from the file into an array.
$input_state_history = array();
$line_counter = 0;
while (feof($input_state_file) === false) {

    //While the file-pointer hasn't reached the end of the file yet,
    //get the Comma-Separated-Value contents of the current line and
    //append that to the input state history array.
    $line_csv = fgetcsv($input_state_file);
    if ($line_csv === false) {

        //If it failed to parse a CSV line from the file, break out of
        //the loop.
        break;
    }


    array_push($input_state_history, $line_csv);
    $line_counter++;

    //Then, if the line counter hasn't exceeded the maximum lines we
    //intend to read for the input state history, continue the loop
    //otherwise, break out of the loop.
    if ($line_counter >= MAX_INPUT_STATE_HISTORY_LINES) {
        break;
    }
}


//Now by this point, we ought to have an array containing up to N amount
//of lines for the input history saved. The array also ought to be ordered
//such that the most recent timestamp is first.

//Remove all the old data from the original file.
if (ftruncate($input_state_file, 0) === false) {

    //If an error occured erasing the old data, throw an error and 
    //kill the script.
    echo "Error occured truncating the file.";
    exit();
}


//Rewind the file pointer, so we can replace the file contents.
if (rewind($input_state_file) === false) {
    echo "Error occured rewinding the file pointer.";
    exit();
}


//Enforce the following CSV Format:
//timestamp,button_0,button_1,button_2,system,power_lever,joystick_x,joystick_y,dial_0
$post_data_csv = $_POST["timestamp"] . ","
    . $_POST["button_0"] . ","
    . $_POST["button_1"] . ","
    . $_POST["button_2"] . ","
    . $_POST["system"] . ","
    . $_POST["power_lever"] . ","
    . $_POST["joystick_x"] . ","
    . $_POST["joystick_y"] . ","
    . $_POST["dial_0"] . "\n";


//The first line will contain the most recent timestamp, so we write
//the POST data payload first.
$bytes_written = fwrite($input_state_file, $post_data_csv);
if ($bytes_written === false) {

    //`fwrite(...)` returns either the number of bytes written or `false`
    //if it failed to write to the file. If it failed to write, then
    //output the error message, close the file handle and kill the script.
    echo "Failed to write to the input state file.";
    fclose($input_state_file);
    exit();
}


//Now we write the rest of the input state history to the file. Since
//we only keep track of a certain amount of lines in the input state 
//history, we'll check if the line counter is less than the maximum 
//amount minus 1 (because we already wrote the first line earlier), if
//the line counter is less than the max amount - 1 (likely because the 
//file reached the end of the line before the max amount), then we use
//that as the maximum amount for the for loop iterator.
$i_max = $line_counter < MAX_INPUT_STATE_HISTORY_LINES - 1 ? $line_counter : MAX_INPUT_STATE_HISTORY_LINES - 1;
for ($i = 0; $i < $i_max; $i++) {

    //Write each line csv back into the file. Since the first write 
    //attempt was successful, it's likely (but not guaranteed) that the
    //following write attempts will be successful as well.
    $bytes_written = fputcsv($input_state_file, $input_state_history[$i]);
}


//Writing completed, close the file handler and kill the script.
echo "Successfully wrote data into the input state file.";
fclose($input_state_file);
exit();