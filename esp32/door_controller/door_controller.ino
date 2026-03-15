/*
 * ============================================================
 * Bunny Door System - ESP32 Door Controller
 * ============================================================
 * ระบบควบคุมประตูห้องสโตร์
 * - เซ็นเซอร์ PIR 2 ตัว (ด้านนอก + ด้านใน)
 * - Relay Module ควบคุมกลอนแม่เหล็กไฟฟ้า 12V
 * - สื่อสารกับ Raspberry Pi ผ่าน HTTP (Wi-Fi)
 * - ปุ่ม Emergency Exit
 * ============================================================
 * การต่อสาย (ตรงกับบอร์ดเดิม):
 *   Relay        -> GPIO 4  (เดิม)
 *   LED Status   -> GPIO 2  (เดิม)
 *   PIR Outside  -> GPIO 27
 *   PIR Inside   -> GPIO 26
 *   Buzzer       -> GPIO 33
 *   Emergency Btn -> GPIO 13 (Pull-up)
 * ============================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

// ============================================================
// Configuration
// ============================================================
const char* WIFI_SSID     = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL    = "http://192.168.1.121:5000";  // Raspberry Pi IP

// ============================================================
// Pin Definitions
// ============================================================
#define PIN_PIR_OUTSIDE    27    // เซ็นเซอร์ PIR ด้านนอกประตู
#define PIN_PIR_INSIDE     26    // เซ็นเซอร์ PIR ด้านในประตู
#define PIN_RELAY          4     // Relay -> กลอนแม่เหล็ก (เดิม GPIO 4)
#define PIN_BUZZER         33    // Buzzer แจ้งเตือน
#define PIN_LED_STATUS     2     // LED สถานะ (เดิม GPIO 2)
#define PIN_EMERGENCY_BTN  13    // ปุ่ม Emergency Exit

// ============================================================
// Constants
// ============================================================
#define DOOR_UNLOCK_MS         7000   // ระยะเวลาปลดล็อก 7 วินาที
#define PIR_COOLDOWN_MS        3000   // cooldown เซ็นเซอร์ 3 วินาที
#define HEARTBEAT_INTERVAL_MS  10000  // ส่ง heartbeat ทุก 10 วินาที
#define DEBOUNCE_MS            200    // debounce ปุ่ม

// ============================================================
// State Variables
// ============================================================
WebServer server(80);

bool doorLocked = true;
unsigned long doorUnlockTime = 0;
unsigned long lastPirOutside = 0;
unsigned long lastPirInside = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastBtnPress = 0;

bool pirOutsideState = false;
bool pirInsideState = false;

// ============================================================
// Setup
// ============================================================
void setup() {
    Serial.begin(115200);
    Serial.println("\n=== Bunny Door System - ESP32 ===");

    // Pin modes
    pinMode(PIN_PIR_OUTSIDE, INPUT);
    pinMode(PIN_PIR_INSIDE, INPUT);
    pinMode(PIN_RELAY, OUTPUT);
    pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT);
    pinMode(PIN_EMERGENCY_BTN, INPUT_PULLUP);

    // Initial state: door locked
    lockDoor();

    // Connect Wi-Fi
    connectWiFi();

    // Setup HTTP endpoints
    setupWebServer();

    Serial.println("System Ready!");
    beep(2, 100);  // 2 beeps = ready
}

// ============================================================
// Main Loop
// ============================================================
void loop() {
    server.handleClient();

    unsigned long now = millis();

    // อ่านเซ็นเซอร์ PIR ด้านนอก
    if (digitalRead(PIN_PIR_OUTSIDE) == HIGH && (now - lastPirOutside > PIR_COOLDOWN_MS)) {
        lastPirOutside = now;
        pirOutsideState = true;
        Serial.println("[PIR] Motion detected OUTSIDE");
        notifyServer("outside");
    }

    // อ่านเซ็นเซอร์ PIR ด้านใน
    if (digitalRead(PIN_PIR_INSIDE) == HIGH && (now - lastPirInside > PIR_COOLDOWN_MS)) {
        lastPirInside = now;
        pirInsideState = true;
        Serial.println("[PIR] Motion detected INSIDE");
        notifyServer("inside");
    }

    // Reset PIR states
    if (digitalRead(PIN_PIR_OUTSIDE) == LOW) pirOutsideState = false;
    if (digitalRead(PIN_PIR_INSIDE) == LOW) pirInsideState = false;

    // ปุ่ม Emergency Exit
    if (digitalRead(PIN_EMERGENCY_BTN) == LOW && (now - lastBtnPress > DEBOUNCE_MS)) {
        lastBtnPress = now;
        Serial.println("[EMERGENCY] Button pressed!");
        unlockDoor();
        notifyEmergency();
    }

    // ล็อกประตูอัตโนมัติเมื่อครบเวลา
    if (!doorLocked && (now - doorUnlockTime > DOOR_UNLOCK_MS)) {
        lockDoor();
        Serial.println("[DOOR] Auto-locked");
    }

    // Heartbeat
    if (now - lastHeartbeat > HEARTBEAT_INTERVAL_MS) {
        lastHeartbeat = now;
        sendHeartbeat();
    }

    delay(50);
}

// ============================================================
// Door Control
// ============================================================
void unlockDoor() {
    doorLocked = false;
    doorUnlockTime = millis();
    digitalWrite(PIN_RELAY, HIGH);    // Relay ON -> ปลดล็อก
    digitalWrite(PIN_LED_STATUS, HIGH);
    beep(1, 200);
    Serial.println("[DOOR] Unlocked");
}

void lockDoor() {
    doorLocked = true;
    digitalWrite(PIN_RELAY, LOW);     // Relay OFF -> ล็อก
    digitalWrite(PIN_LED_STATUS, LOW);
    Serial.println("[DOOR] Locked");
}

// ============================================================
// Wi-Fi
// ============================================================
void connectWiFi() {
    Serial.printf("Connecting to %s", WIFI_SSID);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    if (WiFi.status() == WL_CONNECTED) {
        Serial.printf("\nConnected! IP: %s\n", WiFi.localIP().toString().c_str());
    } else {
        Serial.println("\nWi-Fi connection failed! Running in offline mode.");
    }
}

// ============================================================
// Web Server (ESP32 รับคำสั่งจาก Raspberry Pi)
// ============================================================
void setupWebServer() {
    // สั่งเปิดประตู
    server.on("/api/door/unlock", HTTP_POST, []() {
        unlockDoor();
        server.send(200, "application/json", "{\"status\":\"unlocked\"}");
    });

    // สั่งล็อกประตู
    server.on("/api/door/lock", HTTP_POST, []() {
        lockDoor();
        server.send(200, "application/json", "{\"status\":\"locked\"}");
    });

    // ดูสถานะ
    server.on("/api/status", HTTP_GET, []() {
        StaticJsonDocument<256> doc;
        doc["door"] = doorLocked ? "locked" : "unlocked";
        doc["pir_outside"] = pirOutsideState;
        doc["pir_inside"] = pirInsideState;
        doc["uptime_sec"] = millis() / 1000;
        doc["ip"] = WiFi.localIP().toString();
        doc["rssi"] = WiFi.RSSI();
        String output;
        serializeJson(doc, output);
        server.send(200, "application/json", output);
    });

    // Health check
    server.on("/ping", HTTP_GET, []() {
        server.send(200, "text/plain", "pong");
    });

    server.begin();
    Serial.println("HTTP Server started on port 80");
}

// ============================================================
// Communication with Raspberry Pi
// ============================================================
void notifyServer(const char* side) {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String url = String(SERVER_URL) + "/api/motion";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<128> doc;
    doc["sensor"] = side;
    doc["timestamp"] = millis();
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);
    if (code > 0) {
        Serial.printf("[HTTP] Motion notify -> %d\n", code);
    } else {
        Serial.printf("[HTTP] Error: %s\n", http.errorToString(code).c_str());
    }
    http.end();
}

void notifyEmergency() {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String url = String(SERVER_URL) + "/api/emergency";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.POST("{\"type\":\"emergency_button\"}");
    http.end();
}

void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String url = String(SERVER_URL) + "/api/heartbeat";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<128> doc;
    doc["device"] = "esp32";
    doc["door"] = doorLocked ? "locked" : "unlocked";
    doc["pir_outside"] = pirOutsideState;
    doc["pir_inside"] = pirInsideState;
    doc["rssi"] = WiFi.RSSI();
    String body;
    serializeJson(doc, body);

    http.POST(body);
    http.end();
}

// ============================================================
// Buzzer
// ============================================================
void beep(int times, int duration) {
    for (int i = 0; i < times; i++) {
        digitalWrite(PIN_BUZZER, HIGH);
        delay(duration);
        digitalWrite(PIN_BUZZER, LOW);
        if (i < times - 1) delay(duration);
    }
}
