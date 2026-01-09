// ESP32 Code for Charging Station - Updated for Port 80 & Detailed Error Feedback
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
// ملاحظة: بما أن Nginx شغال، نستخدم المنفذ 80
#define SERVER_PORT "80" 

#define VERIFY_URL "http://" SERVER_IP "/api/hardware/verify-job"
#define START_URL "http://" SERVER_IP "/api/hardware/start-session"
#define STOP_URL "http://" SERVER_IP "/api/hardware/stop-session"

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
  return (code == 200 || code == 403 || code == 404 || code == 409); 
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

// ====== Verify & Start Logic ======
void handleChargingStart() {
  if (isLocked()) return;
  
  show("Verifying...", "Please wait");
  beep(100);

  // 1. Prepare JSON
  StaticJsonDocument<200> doc;
  doc["job_number"] = jobInput;
  doc["uid"] = DEVICE_UID;
  String json;
  serializeJson(doc, json);
  String resp;

  // 2. Call START_URL (Includes validation in your Controller)
  if (httpPostJson(START_URL, json, resp)) {
    StaticJsonDocument<512> resDoc;
    deserializeJson(resDoc, resp);

    String status = resDoc["status"] | "";
    String message = resDoc["message"] | "";

    if (status == "success") {
      String userName = resDoc["user_name"].as<String>();
      digitalWrite(RELAY_PIN, HIGH);
      digitalWrite(LED_GREEN, HIGH);
      charging = true;
      show("Hello " + userName, "Charging Started");
      beep(200); delay(100); beep(200);
      delay(3000);
      show("Charging...", "Press * to Stop");
    } 
    else {
      // التعامل مع أخطاء الـ Validation من الـ Controller
      digitalWrite(LED_RED, HIGH);
      beep(800);
      
      if (message == "NO_BOOKING_FOUND") show("Error:", "No Booking Found");
      else if (message == "BOOKING_NOT_STARTED_YET") show("Error:", "Too Early!");
      else if (message == "BOOKING_EXPIRED") show("Error:", "Booking Expired");
      else if (message == "ACTIVE_SESSION_EXISTS") show("Error:", "Already Active");
      else show("Access Denied", "Try Again");

      delay(3000);
      digitalWrite(LED_RED, LOW);
      
      retryCount++;
      if (retryCount >= 3) lockLCD();
      else show("Enter Job No.", "Then #");
    }
  } else {
    show("Server Error", "Check Connection");
    digitalWrite(LED_RED, HIGH);
    beep(1000);
    delay(2000);
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
    show("Stop Failed", "Try Again");
    digitalWrite(LED_RED, HIGH);
    beep(800);
    delay(1500);
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

  show("ELECTRA SYSTEM", "Connecting...");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 30) {
    delay(500);
    Serial.print(".");
    tries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    show("System Online", "IP: " + WiFi.localIP().toString());
    beep(200);
    delay(2000);
  } else {
    show("WiFi Error", "Manual Restart");
    digitalWrite(LED_RED, HIGH);
    while (1);
  }
  
  show("Welcome to 911", "Press any key");
  lastKeyPress = millis();
}

// ====== Loop ======
void loop() {
  if (isLocked()) {
    delay(500);
    return;
  }

  char key = keypad.getKey();
  if (key) {
    lastKeyPress = millis();
    beep(50);
    if (!welcomeShown) {
      show("Enter Job No.", "Press #");
      welcomeShown = true;
    }

    if (key >= '0' && key <= '9' && !charging) {
      if (jobInput.length() < 10) {
        jobInput += key;
        show("Job No:", jobInput);
      }
    } else if (key == '#' && !charging) {
      if (jobInput.length() > 0) handleChargingStart();
      else show("Enter No. First");
    } else if (key == '*') {
      if (charging) stopCharging();
      else {
        jobInput = "";
        show("Cleared", "Enter Job No.");
      }
    }
  }
}