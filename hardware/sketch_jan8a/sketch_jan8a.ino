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
String jobInput = "";      // لإدخال رقم البداية
String stopInput = "";     // لإدخال رقم الإيقاف
String activeJobNumber = ""; 
bool charging = false;
bool waitingForStopCode = false; // حالة انتظار رقم الإيقاف
int retryCount = 0;
unsigned long lockUntil = 0;
unsigned long lastStatusCheck = 0;
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

// ====== Secure Stop Logic (Modified) ======
void handleStopWithAuth() {
  show("Verifying ID...", "Please wait");
  
  StaticJsonDocument<200> doc;
  doc["uid"] = DEVICE_UID;
  doc["job_number"] = stopInput; // إرسال الرقم المدخل للإيقاف
  
  String json;
  serializeJson(doc, json);
  String resp;

  if (httpPostJson(STOP_URL, json, resp)) {
    StaticJsonDocument<256> resDoc;
    deserializeJson(resDoc, resp);
    String status = resDoc["status"] | "";

    if (status == "success") {
      digitalWrite(RELAY_PIN, LOW);
      digitalWrite(LED_GREEN, LOW);
      charging = false;
      waitingForStopCode = false;
      activeJobNumber = "";
      
      show("Stopped", "Thank You!");
      beep(500);
      delay(3000);
      show("Welcome to 911", "Press any key");
      welcomeShown = false;
    } else {
      show("Wrong Job No.", "Access Denied");
      digitalWrite(LED_RED, HIGH);
      beep(800);
      delay(2000);
      digitalWrite(LED_RED, LOW);
      
      // العودة لحالة الشحن
      waitingForStopCode = false;
      show("Charging...", "Press * to Stop");
    }
  } else {
    show("Server Error", "Try Again");
    delay(2000);
    show("Charging...", "Press * to Stop");
  }
  stopInput = "";
}

// ====== Polling Status ======
void checkSessionStatus() {
  if (!charging || waitingForStopCode) return;
  if (millis() - lastStatusCheck < 30000) return;
  lastStatusCheck = millis();

  StaticJsonDocument<200> doc;
  doc["job_number"] = activeJobNumber; 
  doc["uid"] = DEVICE_UID;
  String json;
  serializeJson(doc, json);
  String resp;

  if (httpPostJson(VERIFY_URL, json, resp)) {
    StaticJsonDocument<512> resDoc;
    deserializeJson(resDoc, resp);
    String code = resDoc["code"] | "";
    if (code == "EXPIRED" || code == "NO_BOOKING") {
      digitalWrite(RELAY_PIN, LOW);
      digitalWrite(LED_GREEN, LOW);
      charging = false;
      activeJobNumber = "";
      show("Time Ended", "Session Closed");
      beep(1000);
      delay(3000);
      show("Welcome to 911", "Press any key");
      welcomeShown = false;
    }
  }
}

// ====== Start Logic ======
void handleChargingStart() {
  if (isLocked()) return;
  show("Verifying...", "Please wait");
  
  StaticJsonDocument<200> doc;
  doc["job_number"] = jobInput;
  doc["uid"] = DEVICE_UID;
  String json;
  serializeJson(doc, json);
  String resp;

  if (httpPostJson(START_URL, json, resp)) {
    StaticJsonDocument<512> resDoc;
    deserializeJson(resDoc, resp);
    String status = resDoc["status"] | "";
    String message = resDoc["message"] | "";

    if (status == "success") {
      activeJobNumber = jobInput;
      digitalWrite(RELAY_PIN, HIGH);
      digitalWrite(LED_GREEN, HIGH);
      charging = true;
      show("Hello " + resDoc["user_name"].as<String>(), "Charging Start");
      beep(200); delay(100); beep(200);
      delay(3000);
      show("Charging...", "Press * to Stop");
    } else {
      digitalWrite(LED_RED, HIGH);
      beep(800);
      if (message == "BOOKING_NOT_STARTED_YET") show("Too Early!");
      else show("No Booking", "Check Job No.");
      delay(2500);
      digitalWrite(LED_RED, LOW);
      retryCount++;
      if (retryCount >= 3) lockLCD();
      else show("Enter Job No.", "Press #");
    }
  }
  jobInput = "";
}

// ====== Setup ======
void setup() {
  Serial.begin(115200);
  Wire.begin(21, 22);
  lcd.init(); lcd.backlight();
  pinMode(LED_GREEN, OUTPUT); pinMode(LED_RED, OUTPUT);
  pinMode(RELAY_PIN, OUTPUT); pinMode(BUZZER_PIN, OUTPUT);
  
  show("ELECTRA SYSTEM", "Connecting...");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  
  show("911 Online", "Ready");
  beep(200); delay(2000);
  show("Welcome to 911", "Press any key");
}

// ====== Loop (Modified for Stop Auth) ======
void loop() {
  if (isLocked()) { delay(500); return; }
  if (charging) checkSessionStatus();

  char key = keypad.getKey();
  if (key) {
    beep(50);
    if (!welcomeShown) { welcomeShown = true; show("Enter Job No.", "Press #"); }

    // 1. التعامل مع أرقام الكيباد
    if (key >= '0' && key <= '9') {
      if (waitingForStopCode) {
        stopInput += key;
        show("ID to Stop:", stopInput);
      } else if (!charging) {
        jobInput += key;
        show("Job No:", jobInput);
      }
    } 
    // 2. زر التأكيد (#)
    else if (key == '#') {
      if (waitingForStopCode) handleStopWithAuth();
      else if (!charging && jobInput.length() > 0) handleChargingStart();
    } 
    // 3. زر الإلغاء/الإيقاف (*)
    else if (key == '*') {
      if (charging) {
        waitingForStopCode = true;
        stopInput = "";
        show("Enter Job No.", "To Stop: #");
      } else {
        jobInput = "";
        show("Cleared", "Enter Job No.");
      }
    }
  }
}