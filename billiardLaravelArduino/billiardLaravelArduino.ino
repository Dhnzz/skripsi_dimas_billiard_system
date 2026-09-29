#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WebSocketsClient.h>

// ==============================================================================
// 1. KONFIGURASI JARINGAN & SERVER (CLOUDFLARE TUNNEL / HOSTING)
// ==============================================================================
const char* ssid     = "LPRON";
const char* password = "LPRON2025";

// Host domain Cloudflare (Port 443 HTTPS & WSS)
const char* hostDomain = "billiard-system.azhr.cloud";

// HTTP Endpoint untuk inisialisasi status meja & fallback sync
const String apiBaseUrl = "https://billiard-system.azhr.cloud/api/microcontroller/tables/light";

// Konfigurasi WebSocket Reverb via Cloudflare Tunnel
const char* wsHost     = hostDomain;
const int   wsPort     = 443;                     // Port 443 WSS Cloudflare
const bool  wsUseSSL   = true;                    // Wajib true untuk WSS (port 443)
const char* reverbKey  = "dqnufgh3eq1paxqb9asb";  // REVERB_APP_KEY dari file .env

// Channel Laravel Reverb
const char* targetChannel = "billiard-updates";

// ==============================================================================
// 2. HARDWARE, PIN & RELAY LOGIC
// ==============================================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Relay Pin (Indeks 0 = Meja 1, Indeks 9 = Meja 10)
const int ledPins[]   = {27, 26, 25, 19, 18, 5, 17, 16, 4, 15};
const int buzzer      = 13;
const int totalTables = 10;

// Set false jika relay Anda tipe Active LOW (kebanyakan relay module 5V)
// Set true jika relay Anda tipe Active HIGH
const bool RELAY_ACTIVE_HIGH = true;

// Status lampu lokal tiap meja [0..9]
bool tableLights[totalTables] = {false};

// ==============================================================================
// 3. OBJECT & TIMER
// ==============================================================================
WebSocketsClient webSocket;
unsigned long lastHeartbeat = 0;
const unsigned long heartbeatInterval = 60000; // Sinkronisasi ulang HTTP tiap 60 detik (fallback)

// ==============================================================================
// PROTOTYPE FUNCTION
// ==============================================================================
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length);
void handleWebSocketMessage(const String& message);
void fetchInitialTableStatus();
void updateRelay(int tableId, bool lightOn);
void updateLcdRow();
void alarm();

// ==============================================================================
// SETUP
// ==============================================================================
void setup() {
  Serial.begin(115200);

  lcd.init();
  lcd.backlight();
  pinMode(buzzer, OUTPUT);

  // Inisialisasi Pin Relay (Kondisi Awal: Mati)
  for (int i = 0; i < totalTables; i++) {
    pinMode(ledPins[i], OUTPUT);
    digitalWrite(ledPins[i], RELAY_ACTIVE_HIGH ? LOW : HIGH);
  }

  // 1. Koneksi WiFi
  WiFi.begin(ssid, password);
  lcd.setCursor(0, 0); lcd.print("MENCARI KONEKSI");
  lcd.setCursor(0, 1); lcd.print("     WIFI !");

  while (WiFi.status() != WL_CONNECTED) {
    alarm();
    delay(500);
    Serial.print(".");
  }

  Serial.println("\n[WIFI] Terkoneksi!");
  Serial.printf("[WIFI] IP Address: %s\n", WiFi.localIP().toString().c_str());

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("WIFI TERKONEKSI");
  lcd.setCursor(0, 1); lcd.print(WiFi.localIP().toString());
  delay(1500);

  // 2. Ambil status awal semua meja via HTTPS (1x call)
  fetchInitialTableStatus();

  // 3. Konfigurasi WebSocket Reverb via Cloudflare (WSS)
  String wsUrl = "/app/" + String(reverbKey) + "?protocol=7&client=js&version=8.4.0&flash=false";

  if (wsUseSSL) {
    // beginSSL tanpa fingerprint (lewati validasi CA agar cocok dengan Cloudflare edge cert)
    webSocket.beginSSL(wsHost, wsPort, wsUrl.c_str(), "");
  } else {
    webSocket.begin(wsHost, wsPort, wsUrl.c_str());
  }

  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);
  webSocket.enableHeartbeat(25000, 3000, 2);

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("WS CONNECTING...");
}

// ==============================================================================
// MAIN LOOP
// ==============================================================================
void loop() {
  webSocket.loop();

  // Fallback sync: cek status HTTP setiap 60 detik jika ada paket WS terlewat
  if (millis() - lastHeartbeat > heartbeatInterval) {
    fetchInitialTableStatus();
    lastHeartbeat = millis();
  }
}

// ==============================================================================
// WEBSOCKET EVENT LISTENER
// ==============================================================================
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch (type) {
    case WStype_DISCONNECTED:
      Serial.println("[WS] Terputus dari Reverb Server!");
      lcd.setCursor(0, 0); lcd.print("WS: DISCONNECTED");
      break;

    case WStype_CONNECTED:
      Serial.println("[WS] Berhasil terkoneksi ke Server Reverb!");
      lcd.setCursor(0, 0); lcd.print("WS: TERHUBUNG   ");
      // Sync status ulang saat reconnect
      fetchInitialTableStatus();
      break;

    case WStype_TEXT: {
      String message = String((char*)payload);
      handleWebSocketMessage(message);
      break;
    }

    case WStype_ERROR:
      Serial.printf("[WS] Error: %s\n", payload);
      break;

    default:
      break;
  }
}

// ==============================================================================
// PUSHER / REVERB PROTOCOL PARSER
// ==============================================================================
void handleWebSocketMessage(const String& message) {
  StaticJsonDocument<1024> doc;
  DeserializationError err = deserializeJson(doc, message);
  if (err) {
    Serial.printf("[JSON] Parse error: %s\n", err.c_str());
    return;
  }

  const char* eventName = doc["event"] | "";

  // 1. Handshake Pusher: Connection Established -> Langganan Channel
  if (strcmp(eventName, "pusher:connection_established") == 0) {
    Serial.println("[WS] Handshake OK. Berlangganan ke channel billiard-updates...");

    StaticJsonDocument<256> subDoc;
    subDoc["event"] = "pusher:subscribe";
    JsonObject dataObj = subDoc.createNestedObject("data");
    dataObj["channel"] = targetChannel;

    String subMsg;
    serializeJson(subDoc, subMsg);
    webSocket.sendTXT(subMsg);
    return;
  }

  // 2. Reverb Heartbeat Ping -> Balas Pong
  if (strcmp(eventName, "pusher:ping") == 0) {
    webSocket.sendTXT("{\"event\":\"pusher:pong\",\"data\":{}}");
    return;
  }

  // 3. Konfirmasi Langganan Channel Sukses
  if (strcmp(eventName, "pusher_internal:subscription_succeeded") == 0) {
    Serial.printf("[WS] Sukses langganan channel: %s\n", targetChannel);
    lcd.setCursor(0, 0); lcd.print("READY BILLIARD  ");
    updateLcdRow();
    return;
  }

  // 4. Event TableStatusUpdated (Lampu meja ON/OFF realtime)
  if (strstr(eventName, "TableStatusUpdated") != NULL) {
    int tableId = 0;
    bool lightOn = false;

    const char* dataRaw = doc["data"].as<const char*>();

    // Pusher mengirimkan field `data` sebagai JSON string
    if (dataRaw != NULL && dataRaw[0] == '{') {
      StaticJsonDocument<512> dataDoc;
      deserializeJson(dataDoc, dataRaw);
      tableId = dataDoc["table_id"] | dataDoc["tableId"] | 0;
      lightOn = dataDoc["light_on"] | dataDoc["device_status"] | false;
    } else if (doc["data"].is<JsonObject>()) {
      tableId = doc["data"]["table_id"] | doc["data"]["tableId"] | 0;
      lightOn = doc["data"]["light_on"] | doc["data"]["device_status"] | false;
    }

    if (tableId >= 1 && tableId <= totalTables) {
      Serial.printf("[WS EVENT] Meja %d -> Lampu %s\n", tableId, lightOn ? "MENYALA" : "PADAM");
      updateRelay(tableId, lightOn);
      alarm(); // Notifikasi buzzer singkat
    }
  }
}

// ==============================================================================
// HTTPS FALLBACK / AMBIL STATUS AWAL
// ==============================================================================
void fetchInitialTableStatus() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClientSecure client;
  client.setInsecure(); // Bypass CA root cert validation di ESP32

  HTTPClient http;
  if (!http.begin(client, apiBaseUrl)) {
    Serial.println("[HTTP] Inisialisasi HTTPClient gagal.");
    return;
  }

  int httpCode = http.GET();

  if (httpCode == 200) {
    String payload = http.getString();
    StaticJsonDocument<2048> doc;
    DeserializationError err = deserializeJson(doc, payload);

    if (!err && doc.containsKey("data")) {
      JsonArray array = doc["data"].as<JsonArray>();
      for (JsonObject obj : array) {
        int id = obj["table_id"] | 0;
        bool lightOn = obj["light_on"] | false;
        if (id >= 1 && id <= totalTables) {
          updateRelay(id, lightOn);
        }
      }
      Serial.println("[HTTP] Status awal semua meja tersinkronisasi.");
    }
  } else {
    Serial.printf("[HTTP] Gagal mengambil data meja. HTTP Code: %d\n", httpCode);
  }

  http.end();
}

// ==============================================================================
// KONTROL RELAY & LCD
// ==============================================================================
void updateRelay(int tableId, bool lightOn) {
  int index = tableId - 1;
  tableLights[index] = lightOn;

  // Sesuaikan Active High / Active Low
  if (RELAY_ACTIVE_HIGH) {
    digitalWrite(ledPins[index], lightOn ? HIGH : LOW);
  } else {
    digitalWrite(ledPins[index], lightOn ? LOW : HIGH);
  }

  updateLcdRow();
}

void updateLcdRow() {
  lcd.setCursor(0, 1);
  for (int i = 0; i < totalTables; i++) {
    lcd.print(tableLights[i] ? "1" : "0");
  }
}

void alarm() {
  digitalWrite(buzzer, HIGH);
  delay(40);
  digitalWrite(buzzer, LOW);
}
