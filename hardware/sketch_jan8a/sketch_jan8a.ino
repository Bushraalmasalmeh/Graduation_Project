// ESP32 Code for Charging Station with Keypad, LCD, Relay, and API Integration

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Keypad.h>
#include <LiquidCrystal_I2C.h>
#include <Wire.h>

// ====== WiFi & API Config ======
#define WIFI_SSID "Electra"
#define WIFI_PASSWORD "electra20262004"
#define SERVER_IP "164.92.199.83"
#define SERVER_PORT "8000"
#define VERIFY_URL "http://" SERVER_IP ":" SERVER_PORT "/api/hardware/verify-job"
#define START_URL "http://" SERVER_IP ":" SERVER_PORT "/api/hardware/start-session"
#define STOP_URL "http://" SERVER_IP ":" SERVER_PORT "/api/hardware/stop-session"
#define API_KEY "Electra_Hardware_Key_2025_SECURE"
#define DEVICE_UID "911"

// ====== Pins ======
#define RELAY_PIN 15
#define LED_GREEN 4
#define LED_RED 17
#define BUZZER_PIN 5

// ====== LCD & Keypad ======
LiquidCrystal_I2C lcd(0x27, 16, 2);
const byte ROWS = 4, COLS = 3;
char keys[ROWS][COLS] = {
  { '1', '2', '3' },
  { '4', '5', '6' },
  { '7', '8', '9' },
  { '*', '0', '#' }
};
byte rowPins[ROWS] = { 12, 13, 14, 27 };
byte colPins[COLS] = { 25, 32, 26 };
Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);

// ====== Variables ======
String jobInput = "";
bool charging = false;
int retryCount = 0;
unsigned long lockUntil = 0;
unsigned long lastKeyPress = 0;
bool welcomeShown = false;

// ====== Helpers ======
void show(String line1, String line2 = "") {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1);
  lcd.setCursor(0, 1);
  lcd.print(line2);
}

void beep(int ms) {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(ms);
  digitalWrite(BUZZER_PIN, LOW);
}

bool httpPostJson(const char* url, String json, String &response) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", "Bearer " + String(API_KEY));

  int code = http.POST(json);
  Serial.println("HTTP Code: " + String(code));

  response = http.getString();
  http.end();
  delay(100); 

  return (code == 200);
}



// ====== Lock Control ======
void lockLCD() {
  lockUntil = millis() + 60000;
}

bool isLocked() {
  if (lockUntil == 0) return false;
  long remaining = (lockUntil - millis()) / 1000;
  if (remaining <= 0) {
    lockUntil = 0;
    retryCount = 0;
    show("Welcome to 911", "Press any key");
    welcomeShown = false;
    return false;
  }
  show("LOCKED", "Wait " + String(remaining) + "s");
  return true;
}

// ====== Idle Timeout ======
void checkIdleTimeout() {
  if (charging && millis() - lastKeyPress > 1800000) {
    digitalWrite(RELAY_PIN, LOW);
    digitalWrite(LED_GREEN, LOW);
    charging = false;
    show("Auto Stop", "Idle 30 min");
    beep(1000);
    delay(2000);
    show("Welcome to 911", "Press any key");
    welcomeShown = false;
  }
}

// ====== Verify Job ======
String verifyJob(String jobNumber) {
  StaticJsonDocument<200> doc;
  doc["job_number"] = jobNumber;
  doc["uid"] = DEVICE_UID; // ← ضروري جدًا
  String json; serializeJson(doc, json);
  String resp;

  Serial.println("Sending JSON: " + json);

  if (!httpPostJson(VERIFY_URL, json, resp)) {
    Serial.println("HTTP request failed");
    return "error";
  }

  Serial.println("Response: " + resp);

  StaticJsonDocument<256> resDoc;
  if (deserializeJson(resDoc, resp)) return "error";

  if (resDoc["status"] == "success") return "success";
  if (resDoc["code"] == "NO_BOOKING") return "no_booking";
  return "invalid";
}


// ====== Start Charging ======
void startCharging() {
  if (isLocked()) return;
  show("Checking...", "Please wait");

  String result = verifyJob(jobInput);
  if (result != "success") {
    retryCount++;
    String msg = (result == "no_booking") ? "No Booking Found" : (retryCount == 1) ? "First miss"
                                                               : (retryCount == 2) ? "Second miss"
                                                                                   : "Final chance !";
    show("Invalid Job No", msg);
    digitalWrite(LED_RED, HIGH);
    beep(800);
    delay(2000);
    digitalWrite(LED_RED, LOW);
    if (retryCount >= 3) lockLCD();
    else show("Enter Job No.", "Then #");
    jobInput = "";
    return;
  }

  retryCount = 0;
  StaticJsonDocument<200> doc;
  doc["job_number"] = jobInput;
  doc["uid"] = DEVICE_UID;
  String json;
  serializeJson(doc, json);
  String resp;

  if (httpPostJson(START_URL, json, resp)) {
    StaticJsonDocument<256> resDoc;
    deserializeJson(resDoc, resp);
    String userName = resDoc["user_name"].as<String>();
    digitalWrite(RELAY_PIN, HIGH);
    digitalWrite(LED_GREEN, HIGH);
    charging = true;
    show("Hello " + userName, "Start charge");
    beep(200);
    delay(100);
    beep(200);
    delay(10000);
    show("Charging...", "");
  } else {
    show("Start Failed", "Try Again");
    digitalWrite(LED_RED, HIGH);
    beep(600);
    delay(1000);
    digitalWrite(LED_RED, LOW);
  }

  jobInput = "";
}

// ====== Stop Charging ======
void stopCharging() {
  show("Stopping...", "Please wait");
  StaticJsonDocument<100> doc;
  doc["uid"] = DEVICE_UID;
  String json;
  serializeJson(doc, json);
  String resp;

  if (httpPostJson(STOP_URL, json, resp)) {
    digitalWrite(RELAY_PIN, LOW);
    digitalWrite(LED_GREEN, LOW);
    charging = false;
    show("Stopped", "Thank You!");
    beep(500);
    delay(3000);
    show("Welcome to 911", "Press any key");
    welcomeShown = false;
  } else {
    show("Stop Failed", "");
    digitalWrite(LED_RED, HIGH);
    beep(800);
    delay(1000);
    digitalWrite(LED_RED, LOW);
  }
}

// ====== Setup ======
void setup() {
  Serial.begin(115200);
  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_RED, OUTPUT);
  pinMode(RELAY_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);
  digitalWrite(LED_GREEN, LOW);
  digitalWrite(LED_RED, LOW);
  show("Connecting", "to WiFi...");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 30) {
    delay(500);
    Serial.print(".");
    tries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    show("WiFi Connected", WiFi.localIP().toString());
    beep(200);
    delay(2000);
  } else {
    show("WiFi Failed", "Restart Device");
    digitalWrite(LED_RED, HIGH);
    while (1)
      ;
  }
  show("Welcome to 911", "Press any key");
  lastKeyPress = millis();
}

// ====== Loop ======
void loop() {
  if (isLocked()) {
    checkIdleTimeout();
    delay(500);
    return;
  }

  char key = keypad.getKey();
  if (key) {
    lastKeyPress = millis();
    beep(50);
    if (!welcomeShown) {
      show("Enter Job No.", "Press # to confirm");
      welcomeShown = true;
    }

    if (key >= '0' && key <= '9' && !charging) {
      if (jobInput.length() < 10) {
        jobInput += key;
        show("Job No:", jobInput);
      } else {
        show("Max 10 digits");
        beep(600);
        delay(1500);
        show("Job No:", jobInput);
      }
    } else if (key == '#' && !charging) {
      if (jobInput.length() < 1) {
        show("Enter at least 1 digit");
        beep(600);
        delay(1500);
        show("Enter Job No", "Then #");
      } else {
        startCharging();
      }
    } else if (key == '*') {
      if (charging) stopCharging();
      else {
        jobInput = "";
        show("Cleared", "Enter Job No.");
      }
    }
  }
}