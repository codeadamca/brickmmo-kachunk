<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BrickMMO Panel Live State</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    </head>

    <body>
        <!--
        This file contains the main panel overview for the BrickMMO Panel.
        It demonstrates how to use the API, as well as information about
        the API.
        -->

        <header></header>
        <main class="container">
            <div class="row p-3">

                <h1 class="display-1 mb-4">Panel State</h1>
                <!--Disabled for now since the timestamps seem to produce
                    an inaccurate date when passed to JavaScript's date
                    constructor. For now they can be used to determine
                    the sequence of the input states.-->
                <p class="visually-hidden">
                    As of <span id="span-timestamp">TIMESTAMP</span>
                </p>

                <p class="d-flex align-items-center justify-content-start">
                    <span class="display-6 fs-4 me-3 text-body-tertiary">The panel is currently </span>
                    <span id="span-power" class="w-auto badge rounded-pill text-bg-success fs-2">ACTIVE</span>
                </p>

                <p class="d-flex align-items-center justify-content-start">
                    <span class="display-6 fs-4 me-3 text-body-tertiary">Currently controlling</span>
                    <span id="span-system" class="w-auto badge rounded-pill text-bg-secondary fs-2">255</span>
                </p>

                <div class="col-sm-6 list-group my-4">

                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Joystick X</span>
                        <span id="span-joystick-x" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Joystick Y</span>
                        <span id="span-joystick-y" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Dial 0</span>
                        <span id="span-dial-0" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                </div>
                <div class="col-sm-6 list-group my-4">
                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Button 0</span>
                        <span id="span-button-0" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Button 1</span>
                        <span id="span-button-1" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-around align-items-center">
                        <span class="">Button 2</span>
                        <span id="span-button-2" class="badge text-bg-warning fs-6">MISSING</span>
                    </div>
                </div>
            </div>
        </main>
        <footer></footer>


        <!--
        TODO: Write a <script> tag to make repeated fetch requests to the
        localhost and update the GUI information based on these requests.
        -->

        <!--
        TODO: Allow writing as some point in time.
        -->
        <script>

            //Global variables
            let span_button_0 = null;
            let span_button_1 = null;
            let span_button_2 = null;
            let span_system = null;
            let span_power = null;
            let span_joystick_x = null;
            let span_joystick_y = null;
            let span_dial_0 = null;
            let span_timestamp = null;

            let last_timestamp = null;


            window.onload = () => {

                console.log("preparing to retreive handlers");

                //Retreive a handler to all the dynamic elements.
                span_button_0 = document.querySelector("#span-button-0");
                span_button_1 = document.querySelector("#span-button-1");
                span_button_2 = document.querySelector("#span-button-2");
                span_system = document.querySelector("#span-system");
                span_power = document.querySelector("#span-power");
                span_joystick_x = document.querySelector("#span-joystick-x");
                span_joystick_y = document.querySelector("#span-joystick-y");
                span_dial_0 = document.querySelector("#span-dial-0");
                span_timestamp = document.querySelector("#span-timestamp");


                //Make the initial call to getPanelState to start the
                //infinite loop.
                getPanelState();
            };


            function getPanelState() {
                fetch("/api/input/get-state.php")
                    .then(r => r.json())
                    .then(json => updateLiveState(json))
                    .catch(error => console.log(error));
            }


            function updateLiveState(response_json) {

                console.log(response_json[0]);

                if (last_timestamp !== response_json[0].timestamp) {

                    updateButtonElementState(span_button_0, response_json[0].button_0);
                    updateButtonElementState(span_button_1, response_json[0].button_1);
                    updateButtonElementState(span_button_2, response_json[0].button_2);
                    updateMotorElementsState(span_joystick_x, response_json[0].joystick_x);
                    updateMotorElementsState(span_joystick_y, response_json[0].joystick_y);
                    updateMotorElementsState(span_dial_0, response_json[0].dial_0);
                    updatePowerElementState(span_power, response_json[0].power_lever);
                    updateSystemElementState(span_system, response_json[0].system);


                    // //If the response's most recent input state contains
                    // //an update, we'll simulate all the input states up
                    // //until that point in time and then reset the last_timestamp.
                    // let last_loop_timestamp = null;
                    // for (let i = 0; i < response_json.length; i++) {

                    //     //TODO: skip all the states that are earlier than our last timestamp.

                    //     // simulatePanelState(response_json[i], last_loop_timestamp);
                    //     // last_loop_timestamp = parseFloat(response_json[i].timestamp);
                    // }
                }


                //TODO: set the last timestamp before calling the next GET request.

                //Loop back.
                setTimeout(getPanelState, 100);
                //getPanelState();

            }


            // function simulatePanelState(panel_state, previous_timestamp = null) {

            //     let simulation_delay = 0;

            //     if (previous_timestamp !== null) {

            //         //If the previous timestamp is not null, then we don't
            //         //simulate the state instantly, but instead, calculate
            //         //the difference in milliseconds and simulate it after
            //         //that millisecond delay.
            //         simulation_delay = (previous_timestamp - parseFloat(panel_state.timestamp)) * 1000;
            //     }


            //     console.log(simulation_delay);

            // }


            function updateButtonElementState(element, state) {
                if (state === "unavailable") {
                    element.innerText = "MISSING";
                }
                else {
                    element.innerText = state.toUpperCase();
                }


                switch (state) {
                    case "active":
                        element.classList.remove("text-bg-secondary");
                        element.classList.add("text-bg-success");
                        element.classList.remove("text-bg-warning");
                        break;
                    case "inactive":
                        element.classList.add("text-bg-secondary");
                        element.classList.remove("text-bg-success");
                        element.classList.remove("text-bg-warning");
                        break;
                    default:
                        element.classList.remove("text-bg-secondary");
                        element.classList.remove("text-bg-success");
                        element.classList.add("text-bg-warning");
                }
            }


            function updateMotorElementsState(element, state) {
                if (state === "unavailable") {
                    element.innerText = "MISSING";
                    element.classList.remove("text-bg-light");
                    element.classList.add("text-bg-warning");
                }
                else {
                    element.innerText = state;
                    element.classList.add("text-bg-light");
                    element.classList.remove("text-bg-warning");
                }
            }


            function updatePowerElementState(element, state) {
                if (state === "unavailable") {
                    element.innerText = "MISSING";
                }
                else {
                    element.innerText = state.toUpperCase();
                }


                switch (state) {
                    case "active":
                        element.classList.remove("text-bg-secondary");
                        element.classList.add("text-bg-success");
                        element.classList.remove("text-bg-warning");
                        break;
                    case "inactive":
                        element.classList.add("text-bg-secondary");
                        element.classList.remove("text-bg-success");
                        element.classList.remove("text-bg-warning");
                        break;
                    default:
                        element.classList.remove("text-bg-secondary");
                        element.classList.remove("text-bg-success");
                        element.classList.add("text-bg-warning");
                }
            }


            function updateSystemElementState(element, state) {
                if (state === "unavailable") {
                    element.innerText = "MISSING";
                }
                else {
                    element.innerText = "#" + state;
                }
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    </body>

</html>