from collections import namedtuple


# Button State Definitions
BUTTON_STATE_UP = "up"
BUTTON_STATE_DOWN = "down"
PANEL_STATE_ON = "on"
PANEL_STATE_OFF = "off"

INPUT_STATE_UNAVAILABLE = "unavailable"
INPUT_STATE_ACTIVE = "active"
INPUT_STATE_INACTIVE = "inactive"



PanelState = namedtuple("PanelState", [
  "button_0",
  "button_1",
  "button_2",
])