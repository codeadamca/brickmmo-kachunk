import ev3dev2.button as btn


from ev3dev2.display import Display
from ev3dev2.sensor.lego import TouchSensor, ColorSensor, UltrasonicSensor
from ev3dev2.sensor import INPUT_1, INPUT_2, INPUT_3, INPUT_4
from ev3dev2.motor import Motor, OUTPUT_A, OUTPUT_B, OUTPUT_C, OUTPUT_D, SpeedPercent

#TODO: Clean up these imports. This could probably just be included in
# the main script.
from brickmmo.panel import (
    INPUT_STATE_UNAVAILABLE,
    INPUT_STATE_ACTIVE,
    INPUT_STATE_INACTIVE,
)
from time import perf_counter, time
from urllib.request import urlopen, Request
from urllib.parse import urlencode
from collections import deque


# --CONSTANTS--
PANEL_SERVER_URL = "http://169.254.169.248:7777"
BUTTON_INPUT_THRESHOLD = 10
# SYSTEM_SENSOR_WINDOW_SIZE = 100
SYSTEM_SENSOR_MAX_READING_CM = 20




hub_buttons = btn.Button()
screen = Display()

power_lever = None
joystick_x = None
joystick_y = None
dial_0 = None
button_0 = None
button_1 = None
button_2 = None
system_sensor = None


panel_state = {
    "timestamp": 0,
    "button_0": INPUT_STATE_UNAVAILABLE,
    "button_1": INPUT_STATE_UNAVAILABLE,
    "button_2": INPUT_STATE_UNAVAILABLE,
    "system": INPUT_STATE_UNAVAILABLE,
    "power_lever": INPUT_STATE_UNAVAILABLE,
    "joystick_x": INPUT_STATE_UNAVAILABLE,
    "joystick_y": INPUT_STATE_UNAVAILABLE,
    "dial_0": INPUT_STATE_UNAVAILABLE,
}


joystick_x_last_position = 0
joystick_y_last_position = 0
dial_0_last_position = 0
power_lever_last_position = 0

joystick_x_range = 100
joystick_y_range = 100
dial_0_range = 100


# HARDWARE LIMITATION : For some reason, when the EV3 is charging, the
# touch sensors will sometimes randomly give a reading of being active
# even when they weren't pressed. This happens on all tested sensors 
# and when tested on different cables.
#
# WORKAROUND : Set a variable to capture the button state, each instance
# that the button is held down increments this state variable by 1.
# When the button reaches a certain threshold, then it is registered
# as "active". This allows us to only react to reliable sensor data.
button_0_state = 0
button_1_state = 0
button_2_state = 0


# Using deque because we need fast First-In-First-Out functionality.
# ultrasonic_input_queue = deque()
# ultrasonic_input_registry = {}


break_loop = False




# Functions
def send_input_state_to_api(caller="unknown"):
    request_url = "{}/api/input/set-state.php".format(PANEL_SERVER_URL)
    panel_state["timestamp"] = time()
    encoded_panel_state_post_data = urlencode(panel_state).encode("utf-8")


    try:
        request = Request(request_url, data=encoded_panel_state_post_data)
        with urlopen(request, timeout=5) as response:
            print("SENT : {} --> {}".format(caller, panel_state))
            # print(response.read())

    except Exception as e:

        # TODO: Handle internal server error, connection refused, timeouts, etc.
        print("An error occured sending input state to the api : {}".format(e))


def initialize_inputs():
    global power_lever
    global joystick_x
    global joystick_y
    global dial_0
    global button_0
    global button_1
    global button_2
    global system_sensor

    try:
        power_lever = Motor(OUTPUT_A)
        # Reset should be called everytime the motors are initialized, this doesn't
        # actually move the motor, but resets the position counter.
        power_lever.reset()
        # The panel starts off inactive by default, although maybe there's
        # an opportunity to auto-detect the lever state when it initializes
        panel_state["power_lever"] = INPUT_STATE_INACTIVE
    except:
        print("Failed to initialize power_lever.")

    try:
        joystick_x = Motor(OUTPUT_B)
        joystick_x.reset()
        panel_state["joystick_x"] = 0.0
    except:
        print("Failed to initialize joystick_x.")

    try:
        joystick_y = Motor(OUTPUT_C)
        joystick_y.reset()
        panel_state["joystick_y"] = 0.0
    except:
        print("Failed to initialize joystick_y.")

    try:
        dial_0 = Motor(OUTPUT_D)
        dial_0.reset()
        panel_state["dial_0"] = 0.0
    except:
        print("Failed to initialize dial_0.")

    try:
        button_2 = TouchSensor(INPUT_4)
        panel_state["button_2"] = INPUT_STATE_INACTIVE
    except:
        print("Failed to initialize button_2")

    try:
        button_1 = TouchSensor(INPUT_3)
        panel_state["button_1"] = INPUT_STATE_INACTIVE
    except:
        print("Failed to initialize button_1")

    try:
        button_0 = TouchSensor(INPUT_2)
        panel_state["button_0"] = INPUT_STATE_INACTIVE
    except:
        print("Failed to initialize button_0")

    try:
        system_sensor = UltrasonicSensor(INPUT_1)
        # panel_state["system"] = "NoColor"
    except:
        print("Failed to initialize system_sensor")

    # Send the initial panel state
    send_input_state_to_api("initialize_inputs")


# This system is intended to handle all input port values
# as well as HUB button inputs.
def process_sensor_inputs():
    global button_0_state
    global button_1_state
    global button_2_state


    # CONTEXT: This part is more complex than it has to be because there
    # is a hardware limitation where once the EV3 is plugged in and charging,
    # the touch sensors will randomly return a press event when it wasn't
    # pressed.
    if button_0 != None:
        if button_0.is_pressed:
            button_0_state += 1
            
            if button_0_state >= BUTTON_INPUT_THRESHOLD:
                button_0_state = BUTTON_INPUT_THRESHOLD

                if panel_state["button_0"] == INPUT_STATE_INACTIVE:
                    panel_state["button_0"] = INPUT_STATE_ACTIVE
                    send_input_state_to_api("button_0, onpress")
        else:
            button_0_state -= 1
            
            if button_0_state <= 0:
                button_0_state = 0

                if panel_state["button_0"] == INPUT_STATE_ACTIVE:
                    panel_state["button_0"] = INPUT_STATE_INACTIVE
                    send_input_state_to_api("button_0, onrelease")


    if button_1 != None:
        if button_1.is_pressed:
            button_1_state += 1
            
            if button_1_state >= BUTTON_INPUT_THRESHOLD:
                button_1_state = BUTTON_INPUT_THRESHOLD

                if panel_state["button_1"] == INPUT_STATE_INACTIVE:
                    panel_state["button_1"] = INPUT_STATE_ACTIVE
                    send_input_state_to_api("button_1, onpress")
        else:
            button_1_state -= 1
            
            if button_1_state <= 0:
                button_1_state = 0

                if panel_state["button_1"] == INPUT_STATE_ACTIVE:
                    panel_state["button_1"] = INPUT_STATE_INACTIVE
                    send_input_state_to_api("button_1, onrelease")
            
        
    if button_2 != None:
        if button_2.is_pressed:
            button_2_state += 1
            
            if button_2_state >= BUTTON_INPUT_THRESHOLD:
                button_2_state = BUTTON_INPUT_THRESHOLD

                if panel_state["button_2"] == INPUT_STATE_INACTIVE:
                    panel_state["button_2"] = INPUT_STATE_ACTIVE
                    send_input_state_to_api("button_2, onpress")
        else:
            button_2_state -= 1
            
            if button_2_state <= 0:
                button_2_state = 0

                if panel_state["button_2"] == INPUT_STATE_ACTIVE:
                    panel_state["button_2"] = INPUT_STATE_INACTIVE
                    send_input_state_to_api("button_2, onrelease")


    # if system_sensor != None and system_sensor.distance_centimeters <= SYSTEM_SENSOR_MAX_READING_CM:
    #     sensor_distance_cm = int(system_sensor.distance_centimeters)
    #     ultrasonic_input_queue.append(sensor_distance_cm)
    #     key_value = ultrasonic_input_registry.get(sensor_distance_cm)
    #     ultrasonic_input_registry.update({sensor_distance_cm : 1 if key_value == None else key_value + 1})

    #     
    #     if len(ultrasonic_input_queue) > SYSTEM_SENSOR_WINDOW_SIZE:
    #         deque_key = ultrasonic_input_queue.popleft()
    #         ultrasonic_input_registry[deque_key] -= 1
    #         

    #         if ultrasonic_input_registry[deque_key] <= 0:
    #             del ultrasonic_input_registry[deque_key]

    #     most_frequently_read_value = max(ultrasonic_input_registry, key=ultrasonic_input_registry.get)
    #     if most_frequently_read_value != panel_state["system"]:
    #         panel_state["system"] = most_frequently_read_value
    #         send_input_state_to_api("system_selector, system_changed")


# This system is intended to handle all `false inputs`; in
# this case, since motors are technically an output device
# it is being processed as an input by reading it's current
# rotation.
def process_motor_inputs():

    # # Declare that this function will modify the following global
    # # variables.
    global joystick_x_last_position
    global joystick_y_last_position
    global dial_0_last_position

    if joystick_x != None:
        if joystick_x.position != joystick_x_last_position:
            if joystick_x.position > joystick_x_range:
                joystick_x.on_to_position(SpeedPercent(100), joystick_x_range)

            elif joystick_x.position < joystick_x_range * -1:
                joystick_x.on_to_position(SpeedPercent(100), joystick_x_range * -1)

            else:
                joystick_x.stop_action = Motor.STOP_ACTION_COAST
                joystick_x.stop()

            joystick_x_last_position = joystick_x.position
            panel_state["joystick_x"] = joystick_x.position / joystick_x_range
            send_input_state_to_api("joystick_x, moved")

    if joystick_y != None:
        if joystick_y.position != joystick_y_last_position:
            if joystick_y.position > joystick_y_range:
                joystick_y.on_to_position(SpeedPercent(100), joystick_y_range)

            elif joystick_y.position < joystick_y_range * -1:
                joystick_y.on_to_position(SpeedPercent(100), joystick_y_range * -1)

            else:
                joystick_y.stop_action = Motor.STOP_ACTION_COAST
                joystick_y.stop()

            joystick_y_last_position = joystick_y.position
            panel_state["joystick_y"] = joystick_y.position / joystick_y_range
            send_input_state_to_api("joystick_y, moved")

    if dial_0 != None:
        if dial_0.position != dial_0_last_position:
            if dial_0.position > dial_0_range:
                dial_0.on_to_position(SpeedPercent(100), dial_0_range)

            elif dial_0.position < dial_0_range * -1:
                dial_0.on_to_position(SpeedPercent(100), dial_0_range * -1)

            else:
                dial_0.stop_action = Motor.STOP_ACTION_COAST
                dial_0.stop()
            dial_0_last_position = dial_0.position
            panel_state["dial_0"] = dial_0.position / dial_0_range
            send_input_state_to_api("dial_0, moved")


def process_power_lever():
    global power_lever_last_position

    if power_lever != None:
        if power_lever.position != power_lever_last_position:
            power_lever_last_position = power_lever.position

            if power_lever.position > 50:
                system_loaded = False

                
                if system_sensor != None:
                    sensor_distance_cm = int(system_sensor.distance_centimeters)
                    if sensor_distance_cm <= SYSTEM_SENSOR_MAX_READING_CM:
                        system_loaded = True
                        panel_state["system"] = sensor_distance_cm
                    else:
                        system_loaded = False
                        panel_state["system"] = INPUT_STATE_UNAVAILABLE
                

                # Only power the system on when the system sensor is loaded.
                if system_loaded:
                    panel_state["power_lever"] = INPUT_STATE_ACTIVE
                    power_lever.on_to_position(SpeedPercent(100), 50)
                    send_input_state_to_api("power_lever, on")
                else:
                    panel_state["power_lever"] = INPUT_STATE_INACTIVE
                    power_lever.on_to_position(SpeedPercent(100), 0)
                    send_input_state_to_api("power_lever, off")

            elif power_lever.position < 0:
                panel_state["power_lever"] = INPUT_STATE_INACTIVE
                power_lever.on_to_position(SpeedPercent(100), 0)
                send_input_state_to_api("power_lever, off")

            else:
                if power_lever.is_holding is True:
                    power_lever.stop_action = Motor.STOP_ACTION_COAST
                    power_lever.stop()




if __name__ == "__main__":
    initialize_inputs()

    try:
        while break_loop == False:
            process_power_lever()

            # Only process the other sensors and motors when the panel
            # is "turned on".
            if panel_state["power_lever"] is INPUT_STATE_ACTIVE:
                process_sensor_inputs()
                process_motor_inputs()

    except KeyboardInterrupt:
        print("\nExited...")
