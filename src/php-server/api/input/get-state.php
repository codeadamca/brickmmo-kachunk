<?php
/** This file handles the following API request:
 *      GET /api/input/get-state.php?start-time={START_TIME},system={SYSTEM}
 * 
 * 
 * This GET request expects the following queries :
 * -- system : integer value representing the selected system
 * ---- if this is omitted, it will try to get the current active system
 *      from the panel status file.
 * 
 * -- start-time : microsecond accurate unix timestamp
 * ---- if this is omitted or invalid, return the entire input state history.
 * 
 */
//const INPUT_STATE_FILE_PATH = "../../input_state.csv";
const DATA_PATH = "../../data";
const MAX_INPUT_STATE_HISTORY_LINES = 10;
const MAX_LINE_CHARACTERS = 128;


$start_time = null;
$system = null;


if (isset($_GET["system"]) === false) {

    //If the user didn't specify the system, don't bother with the 
    //rest of the script.
    //exit();


    //If the user didn't specify the system, we will check the 
    //panel status csv file for the current state of the system and
    //see if we can get the currently active system from that file.
    $panel_status_file = fopen(DATA_PATH . "/panel_status.csv", "r");
    if ($panel_status_file === false) {
        echo "Failed to retreive panel status.";
        exit();
    }


    $panel_status_csv = fgetcsv($panel_status_file);
    if ($panel_status_csv === false) {
        echo "Error occured reading panel status";
        fclose($panel_status_file);
        exit();
    }


    $power = $panel_status_csv[0];
    $system = $panel_status_csv[1];
    fclose($panel_status_file);


    if ($system === "unavailable") {

        //This means that there is no active system for the panel, and
        //so we will just return the panel power state along with the
        //rest of the values being marked as unavailable.
        echo json_encode([
            [
                "timestamp" => microtime(true),
                "button_0" => "unavailable",
                "button_1" => "unavailable",
                "button_2" => "unavailable",
                "system" => "unavailable",
                "power_lever" => $power,
                "joystick_x" => "unavailable",
                "joystick_y" => "unavailable",
                "dial_0" => "unavailable",
            ],
        ]);
        exit();

    }
} else {
    $system = $_GET["system"];
}


if (isset($_GET["start-time"]) === true) {

    //if the start time parameter was set inside the GET request, save 
    //the value to a global handle.
    $start_time = floatval($_GET["start-time"]);
}


//Open the input state text file for reading and store a handle to the
//file object inside the `input_state_file`.
$input_state_file = fopen(DATA_PATH . "/$system.csv", "r");
if ($input_state_file === false) {

    //`fopen(...)` returns the file handle or `false` if it failed to
    //open the file. So in this case, we output an error and kill the
    //script if it failed to open the file.
    echo "Failed to open file for reading.";
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


    //Now check if a start time was provided with the query.
    if ($start_time !== null) {

        //If it is, then try to see if the input state being read is 
        //later than the requested start time.

        //NOTE: the value returned by `floatval()` is rounded when the
        //required decimal precision is greater than the precision a
        //floating point can properly represent. In this case, the
        //microseconds are rounded like so : 0.000199 -> 0.0002.
        //
        //FORESIGHT: This may cause bugs when working with start-time
        //values that rely on the microsecond accuracy.
        if ($start_time > floatval($line_csv[0])) {

            //If it turns out the the input state timestamp is now
            //earlier than the requested start time, end the loop right here.
            break;
        }

    }


    //Now that we have the csv line retreived from the file, we can
    //convert this into an associative array representing the JSON
    //format.
    $line_as_json = [
        "timestamp" => $line_csv[0],
        "button_0" => $line_csv[1],
        "button_1" => $line_csv[2],
        "button_2" => $line_csv[3],
        "system" => $line_csv[4],
        "power_lever" => $line_csv[5],
        "joystick_x" => $line_csv[6],
        "joystick_y" => $line_csv[7],
        "dial_0" => $line_csv[8],
    ];


    array_push($input_state_history, $line_as_json);
    $line_counter++;


    //Then, if the line counter hasn't exceeded the maximum lines we
    //intend to read for the input state history, continue the loop
    //otherwise, break out of the loop.
    if ($line_counter >= MAX_INPUT_STATE_HISTORY_LINES) {
        break;
    }
}


//Returns the input state history as a serialized JSON array in the
//following structure: [i, i, ..., i] where `i` represents an input state
//JSON object with the following structure: 
//{
//  "timestamp": ...,
//  "button_0": ...,
//  ...
//  "dial_0": ...,
//}
echo json_encode($input_state_history);


//After the file operations are over, close the file pointer to trigger the
//cleanup process.
fclose($input_state_file);
exit();