/*
 * ============================================================
 * Bunny Door System - ESP32 Door Controller (with Auto-Pair)
 * ============================================================
 * ระบบควบคุมประตูห้องสโตร์
 * - เซ็นเซอร์ PIR 2 ตัว (ด้านนอก + ด้านใน)
 * - Relay Module ควบคุมกลอนแม่เหล็กไฟฟ้า 12V
 * - สื่อสารกับ Raspberry Pi ผ่าน HTTP (Wi-Fi)
 * - ปุ่ม Emergency Exit
 * - Auto-Pair: discover Pi/web อัตโนมัติบน WiFi เดียวกัน
 *   - broadcast UDP ทุก 5s
 *   - ฟัง broadcast จาก Pi → save SERVER_URL ลง Preferences (NVS)
 * ============================================================
 * การต่อสาย (ตรงกับบอร์ดเดิม):
 *   Relay        -> GPIO 4
 *   LED Status   -> GPIO 2
 *   PIR Outside  -> GPIO 27
 *   PIR Inside   -> GPIO 26
 *   Buzzer       -> GPIO 33
 *   Emergency Btn -> GPIO 13 (Pull-up)
 * ============================================================
 * Serial commands (กรอกผ่าน Serial Monitor 115200):
 *   token <PAIRING_TOKEN>  → ตั้ง pairing token
 *   server <URL>           → ตั้ง SERVER_URL (override discovery)
 *   reset                  → ลบทุกค่าที่บันทึก
 *   show                   → แสดงค่าปัจจุบัน
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiUdp.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <Preferences.h>

// ============================================================
// Configuration (default — override ได้ผ่าน Preferences / Serial)
// ============================================================
const char* WIFI_SSID            = "TEE_2.4G";
const char* WIFI_PASSWORD        = "tee246246";
const char* DEFAULT_SERVER_URL   = "";       // ว่าง → ใช้ discovery
const char* DEFAULT_PAIR_TOKEN   = "";       // ว่าง → ใส่ผ่าน Serial หรือใส่ตรงนี้ก่อน compile

// ============================================================
// Pin Definitions
// ============================================================
#define PIN_PIR_OUTSIDE    27
#define PIN_PIR_INSIDE     26
#define PIN_RELAY          4
#define PIN_BUZZER         33
#define PIN_LED_STATUS     2
#define PIN_EMERGENCY_BTN  13

// ============================================================
// Constants
// ============================================================
#define DOOR_UNLOCK_MS         7000
#define PIR_COOLDOWN_MS        3000
#define HEARTBEAT_INTERVAL_MS  10000
#define DEBOUNCE_MS            200

// Auto-pair / Discovery
#define DISCOVERY_PORT          47474
#define DISCOVERY_MAGIC         "BUNNYDOOR"
#define DISCOVERY_VERSION       "v1"
#define DISCOVERY_BROADCAST_MS  5000

// ============================================================
// State Variables
// ============================================================
WebServer server(80);
WiFiUDP udp;
Preferences prefs;

bool doorLocked = true;
unsigned long doorUnlockTime = 0;
unsigned long lastPirOutside = 0;
unsigned long lastPirInside = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastBtnPress = 0;
unsigned long lastDiscoveryBcast = 0;

bool pirOutsideState = false;
bool pirInsideState = false;

// runtime config (loaded จาก Preferences)
String runtimeServerUrl  = "";
String runtimePairToken  = "";
String myDeviceId        = "";  // MAC address

// ============================================================
// Forward declarations
// ============================================================
void unlockDoor();
void lockDoor();
void beep(int times, int duration);
void connectWiFi();
void setupWebServer();
void notifyServer(const char* side);
void notifyEmergency();
void sendHeartbeat();
void discoveryBroadcast();
void discoveryListen();
void announceToWeb(const String& webBase);
void handleSerial();
void loadPrefs();
void savePrefs();
void printConfig();

// ============================================================
// Setup
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(100);
    Serial.println("\n=== Bunny Door System - ESP32 (Auto-Pair) ===");

    // Pin modes
    pinMode(PIN_PIR_OUTSIDE, INPUT);
    pinMode(PIN_PIR_INSIDE, INPUT);
    pinMode(PIN_RELAY, OUTPUT);
    pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT);
    pinMode(PIN_EMERGENCY_BTN, INPUT_PULLUP);

    // Initial state: door locked
    lockDoor();

    // Load saved config
    loadPrefs();

    // Connect Wi-Fi
    connectWiFi();

    // เริ่ม UDP listener สำหรับ discovery
    if (WiFi.status() == WL_CONNECTED) {
        if (udp.begin(DISCOVERY_PORT)) {
            Serial.printf("[Discovery] UDP listening on %d\n", DISCOVERY_PORT);
        } else {
            Serial.println("[Discovery] UDP bind failed");
        }
    }

    // Setup HTTP endpoints (สำหรับ Pi เรียก unlock/lock/status)
    setupWebServer();

    myDeviceId = WiFi.macAddress();
    Serial.printf("[Device] MAC = %s\n", myDeviceId.c_str());
    printConfig();

    Serial.println("System Ready!");
    beep(2, 100);  // 2 beeps = ready
}

// ============================================================
// Main Loop
// ============================================================
void loop() {
    server.handleClient();
    handleSerial();

    unsigned long now = millis();

    // PIR outside
    if (digitalRead(PIN_PIR_OUTSIDE) == HIGH && (now - lastPirOutside > PIR_COOLDOWN_MS)) {
        lastPirOutside = now;
        pirOutsideState = true;
        Serial.println("[PIR] Motion OUTSIDE");
        notifyServer("outside");
    }

    // PIR inside
    if (digitalRead(PIN_PIR_INSIDE) == HIGH && (now - lastPirInside > PIR_COOLDOWN_MS)) {
        lastPirInside = now;
        pirInsideState = true;
        Serial.println("[PIR] Motion INSIDE");
        notifyServer("inside");
    }

    if (digitalRead(PIN_PIR_OUTSIDE) == LOW) pirOutsideState = false;
    if (digitalRead(PIN_PIR_INSIDE) == LOW) pirInsideState = false;

    // Emergency button
    if (digitalRead(PIN_EMERGENCY_BTN) == LOW && (now - lastBtnPress > DEBOUNCE_MS)) {
        lastBtnPress = now;
        Serial.println("[EMERGENCY] Button pressed!");
        unlockDoor();
        notifyEmergency();
    }

    // Auto-lock
    if (!doorLocked && (now - doorUnlockTime > DOOR_UNLOCK_MS)) {
        lockDoor();
        Serial.println("[DOOR] Auto-locked");
    }

    // Heartbeat
    if (now - lastHeartbeat > HEARTBEAT_INTERVAL_MS) {
        lastHeartbeat = now;
        sendHeartbeat();
    }

    // Discovery broadcast + listen
    if (now - lastDiscoveryBcast > DISCOVERY_BROADCAST_MS) {
        lastDiscoveryBcast = now;
        discoveryBroadcast();
    }
    discoveryListen();

    delay(20);
}

// ============================================================
// Door Control
// ============================================================
void unlockDoor() {
    doorLocked = false;
    doorUnlockTime = millis();
    digitalWrite(PIN_RELAY, HIGH);
    digitalWrite(PIN_LED_STATUS, HIGH);
    beep(1, 200);
    Serial.println("[DOOR] Unlocked");
}

void lockDoor() {
    doorLocked = true;
    digitalWrite(PIN_RELAY, LOW);
    digitalWrite(PIN_LED_STATUS, LOW);
    Serial.println("[DOOR] Locked");
}

// ============================================================
// Preferences (NVS)
// ============================================================
void loadPrefs() {
    prefs.begin("bunny", true);  // read-only
    runtimeServerUrl = prefs.getString("server_url", DEFAULT_SERVER_URL);
    runtimePairToken = prefs.getString("pair_token", DEFAULT_PAIR_TOKEN);
    prefs.end();
}

void savePrefs() {
    prefs.begin("bunny", false);
    prefs.putString("server_url", runtimeServerUrl);
    prefs.putString("pair_token", runtimePairToken);
    prefs.end();
}

void printConfig() {
    Serial.println("--- Config ---");
    Serial.printf("  WIFI_SSID    : %s\n", WIFI_SSID);
    Serial.printf("  IP           : %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("  MAC          : %s\n", myDeviceId.c_str());
    Serial.printf("  SERVER_URL   : %s\n", runtimeServerUrl.length() ? runtimeServerUrl.c_str() : "(none — รอ discovery)");
    Serial.printf("  PAIR_TOKEN   : %s\n", runtimePairToken.length() ? "***" : "(empty — Serial: token <x>)");
    Serial.println("--------------");
}

// ============================================================
// Wi-Fi
// ============================================================
void connectWiFi() {
    Serial.printf("[WiFi] Connecting to %s", WIFI_SSID);
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    if (WiFi.status() == WL_CONNECTED) {
        Serial.printf("\nConnected! IP: %s  RSSI: %d dBm\n",
                      WiFi.localIP().toString().c_str(), WiFi.RSSI());
    } else {
        Serial.println("\nWi-Fi failed — running in offline mode.");
    }
}

// ============================================================
// Discovery: broadcast ตัวเองออกไป
// ============================================================
void discoveryBroadcast() {
    if (WiFi.status() != WL_CONNECTED) return;
    // Zero-config: broadcast แม้ไม่มี token (web จะรับเป็น PENDING)

    IPAddress local = WiFi.localIP();
    IPAddress mask  = WiFi.subnetMask();
    IPAddress bcast;
    for (int i = 0; i < 4; i++) bcast[i] = local[i] | (~mask[i]);

    String packet = String(DISCOVERY_MAGIC) + "|" + DISCOVERY_VERSION + "|ESP32|"
                  + myDeviceId + "|" + local.toString() + "|80|" + runtimePairToken;

    udp.beginPacket(bcast, DISCOVERY_PORT);
    udp.print(packet);
    udp.endPacket();
    // ยิงไป 255.255.255.255 ด้วย (กรณี subnet mask ไม่ใช่ /24)
    udp.beginPacket(IPAddress(255,255,255,255), DISCOVERY_PORT);
    udp.print(packet);
    udp.endPacket();
}

// ============================================================
// Discovery: ฟัง broadcast จาก Pi
// ============================================================
void discoveryListen() {
    int size = udp.parsePacket();
    if (size <= 0) return;

    char buf[256] = {0};
    int n = udp.read(buf, sizeof(buf) - 1);
    if (n <= 0) return;
    buf[n] = '\0';

    String s = String(buf);
    // ตัวอย่าง: BUNNYDOOR|v1|PI|AA:BB:CC:...|192.168.1.50|5000|<token>
    if (!s.startsWith(String(DISCOVERY_MAGIC) + "|" + DISCOVERY_VERSION + "|")) return;

    // split
    String parts[7];
    int idx = 0, last = 0;
    for (int i = 0; i < s.length() && idx < 7; i++) {
        if (s.charAt(i) == '|') {
            parts[idx++] = s.substring(last, i);
            last = i + 1;
        }
    }
    if (idx < 7) parts[idx++] = s.substring(last);
    if (idx < 7) return;

    String role     = parts[2];
    String devId    = parts[3];
    String ip       = parts[4];
    String port     = parts[5];
    String token    = parts[6];

    // ตรวจ token — ถ้าทั้งคู่มี token แต่ไม่ตรง → ข้าม
    // ถ้าฝั่งใดฝั่งหนึ่งว่าง = zero-config mode → ยอมรับ
    if (runtimePairToken.length() > 0 && token.length() > 0 && token != runtimePairToken) return;
    // ละทิ้ง broadcast จากตัวเอง
    if (role == "ESP32" && devId == myDeviceId) return;

    if (role == "PI") {
        String newUrl = "http://" + ip + ":" + port;
        if (runtimeServerUrl != newUrl) {
            runtimeServerUrl = newUrl;
            savePrefs();
            Serial.printf("[Discovery] Pi found → SERVER_URL = %s (saved)\n", newUrl.c_str());
        }
    }
}

// ============================================================
// Web Server (ESP32 รับคำสั่งจาก Raspberry Pi)
// ============================================================
void setupWebServer() {
    server.on("/api/door/unlock", HTTP_POST, []() {
        unlockDoor();
        server.send(200, "application/json", "{\"status\":\"unlocked\"}");
    });

    server.on("/api/door/lock", HTTP_POST, []() {
        lockDoor();
        server.send(200, "application/json", "{\"status\":\"locked\"}");
    });

    server.on("/api/status", HTTP_GET, []() {
        StaticJsonDocument<320> doc;
        doc["door"] = doorLocked ? "locked" : "unlocked";
        doc["pir_outside"] = pirOutsideState;
        doc["pir_inside"] = pirInsideState;
        doc["uptime_sec"] = millis() / 1000;
        doc["ip"] = WiFi.localIP().toString();
        doc["rssi"] = WiFi.RSSI();
        doc["server_url"] = runtimeServerUrl;
        doc["paired"] = runtimePairToken.length() > 0;
        String output;
        serializeJson(doc, output);
        server.send(200, "application/json", output);
    });

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
    if (WiFi.status() != WL_CONNECTED || runtimeServerUrl.length() == 0) return;

    HTTPClient http;
    String url = runtimeServerUrl + "/api/motion";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<128> doc;
    doc["sensor"] = side;
    doc["timestamp"] = millis();
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);
    if (code > 0) Serial.printf("[HTTP] Motion notify -> %d\n", code);
    else          Serial.printf("[HTTP] Error: %s\n", http.errorToString(code).c_str());
    http.end();
}

void notifyEmergency() {
    if (WiFi.status() != WL_CONNECTED || runtimeServerUrl.length() == 0) return;

    HTTPClient http;
    String url = runtimeServerUrl + "/api/emergency";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.POST("{\"type\":\"emergency_button\"}");
    http.end();
}

void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED || runtimeServerUrl.length() == 0) return;

    HTTPClient http;
    String url = runtimeServerUrl + "/api/heartbeat";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc["device"] = "esp32";
    doc["device_id"] = myDeviceId;
    doc["door"] = doorLocked ? "locked" : "unlocked";
    doc["pir_outside"] = pirOutsideState;
    doc["pir_inside"] = pirInsideState;
    doc["rssi"] = WiFi.RSSI();
    doc["ip"] = WiFi.localIP().toString();
    String body;
    serializeJson(doc, body);

    http.POST(body);
    http.end();
}

// ============================================================
// Serial command interface (เปลี่ยน token / server URL ผ่าน Serial)
// ============================================================
void handleSerial() {
    if (!Serial.available()) return;
    String line = Serial.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) return;

    int sp = line.indexOf(' ');
    String cmd = sp > 0 ? line.substring(0, sp) : line;
    String arg = sp > 0 ? line.substring(sp + 1) : "";
    arg.trim();
    cmd.toLowerCase();

    if (cmd == "token") {
        if (arg.length() == 0) { Serial.println("usage: token <PAIRING_TOKEN>"); return; }
        runtimePairToken = arg;
        savePrefs();
        Serial.println("[OK] pair token saved");
    } else if (cmd == "server") {
        if (arg.length() == 0) { Serial.println("usage: server <http://ip:port>"); return; }
        runtimeServerUrl = arg;
        savePrefs();
        Serial.println("[OK] server URL saved");
    } else if (cmd == "reset") {
        prefs.begin("bunny", false);
        prefs.clear();
        prefs.end();
        runtimeServerUrl = DEFAULT_SERVER_URL;
        runtimePairToken = DEFAULT_PAIR_TOKEN;
        Serial.println("[OK] prefs cleared");
    } else if (cmd == "show") {
        printConfig();
    } else {
        Serial.println("commands: token <x>, server <url>, reset, show");
    }
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
