#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WebSocketsClient.h>

// ==========================================
// 1. KONFIGURASI JARINGAN & SERVER
// ==========================================
const char* ssid     = "LPRON";
const char* password = "LPRON2025";

// Host HTTP API (untuk inisialisasi status awal & fallback)
const String apiBaseUrl = "https://billiard-system.azhr.cloud/api/microcontroller/tables/light";

// Konfigurasi WebSocket Laravel Reverb / Pusher
// Sesuaikan dengan host Reverb Anda (misal domain atau IP lokal)
const char* wsHost     = "billiard-system.azhr.cloud";
const int   wsPort     = 443;                     // 443 untuk WSS, 8080 untuk WS lokal
const bool  wsUseSSL   = true;                    // true jika https/wss, false jika ws://
const char* reverbKey  = "dqnufgh3eq1paxqb9asb";  // REVERB_APP_KEY dari file .env

// Channel yang didengarkan
const char* targetChannel = "billiard-updates";

// ==========================================
// 2. HARDWARE & PIN
// ==========================================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Relay Pin (Indeks 0 = Meja 1, Indeks 9 = Meja 10)
const int ledPins[]   = {27, 26, 25, 19, 18, 5, 17, 16, 4, 15};
const int buzzer      = 13;
const int totalTables = 10;

// Status lampu lokal [0..9]
bool tableLights[totalTables] = {false};

// ==========================================
// 3. OBJECT & TIMER
// ==========================================
WebSocketsClient webSocket;
unsigned long lastHeartbeat = 0;
const unsigned long heartbeatInterval = 60000; // Sinkronisasi ulang HTTP setiap 60 detik (fallback)

// ==========================================
// PROTOTYPE FUNCTION
// ==========================================
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length);
void handleWebSocketMessage(const String& message);
void fetchInitialTableStatus();
void updateRelay(int tableId, bool lightOn);
void updateLcdRow();
void alarm();

// ==========================================
// SETUP
// ==========================================
void setup() {
  Serial.begin(115200);

  lcd.init();
  lcd.backlight();
  pinMode(buzzer, OUTPUT);

  // Inisialisasi Pin Relay
  for (int i = 0; i < totalTables; i++) {
    pinMode(ledPins[i], OUTPUT);
    digitalWrite(ledPins[i], LOW);
  }

  // Koneksi WiFi
  WiFi.begin(ssid, password);
  lcd.setCursor(0, 0); lcd.print("MENCARI KONEKSI");
  lcd.setCursor(0, 1); lcd.print("     WIFI !");

  while (WiFi.status() != WL_CONNECTED) {
    alarm();
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Terkoneksi!");
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("WIFI TERKONEKSI");
  lcd.setCursor(0, 1); lcd.print(WiFi.localIP().toString());
  delay(1500);

  // 1. Ambil status awal semua meja via 1 HTTP request
  fetchInitialTableStatus();

  // 2. Hubungkan ke WebSocket Laravel Reverb
  String wsUrl = "/app/" + String(reverbKey) + "?protocol=7&client=js&version=8.4.0&flash=false";
  
  if (wsUseSSL) {
    webSocket.beginSSL(wsHost, wsPort, wsUrl.c_str());
  } else {
    webSocket.begin(wsHost, wsPort, wsUrl.c_str());
  }

  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);
  webSocket.enableHeartbeat(25000, 3000, 2);

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("WS CONNECTING...");
}

// ==========================================
// LOOP
// ==========================================
void loop() {
  webSocket.loop();

  // Fallback sync: polling berkala setiap 60 detik jika ada event terlewat
  if (millis() - lastHeartbeat > heartbeatInterval) {
    fetchInitialTableStatus();
    lastHeartbeat = millis();
  }
}

// ==========================================
// WEBSOCKET EVENT HANDLER
// ==========================================
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch (type) {
    case WStype_DISCONNECTED:
      Serial.println("[WS] Disconnected!");
      lcd.setCursor(0, 0); lcd.print("WS: DISCONNECTED");
      break;

    case WStype_CONNECTED:
      Serial.println("[WS] Connected to Server!");
      lcd.setCursor(0, 0); lcd.print("WS: TERHUBUNG   ");
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

// ==========================================
// PUSHER / REVERB PROTOCOL PARSER
// ==========================================
void handleWebSocketMessage(const String& message) {
  StaticJsonDocument<1024> doc;
  DeserializationError err = deserializeJson(doc, message);
  if (err) {
    Serial.printf("[JSON] Parse error: %s\n", err.c_str());
    return;
  }

  const char* eventName = doc["event"] | "";

  // 1. Handshake Pusher Connection Established -> Subscribe channel
  if (strcmp(eventName, "pusher:connection_established") == 0) {
    Serial.println("[WS] Connection established. Subscribing to channel...");

    StaticJsonDocument<256> subDoc;
    subDoc["event"] = "pusher:subscribe";
    JsonObject dataObj = subDoc.createNestedObject("data");
    dataObj["channel"] = targetChannel;

    String subMsg;
    serializeJson(subDoc, subMsg);
    webSocket.sendTXT(subMsg);
    return;
  }

  // 2. Ping dari Server Reverb -> Balas Pong
  if (strcmp(eventName, "pusher:ping") == 0) {
    webSocket.sendTXT("{\"event\":\"pusher:pong\",\"data\":{}}");
    return;
  }

  // 3. Konfirmasi langganan sukses
  if (strcmp(eventName, "pusher_internal:subscription_succeeded") == 0) {
    Serial.printf("[WS] Subscribed to channel: %s\n", targetChannel);
    lcd.setCursor(0, 0); lcd.print("READY BILLIARD  ");
    updateLcdRow();
    return;
  }

  // 4. Event TableStatusUpdated (Lampu meja ON/OFF realtime)
  if (strstr(eventName, "TableStatusUpdated") != NULL) {
    // Di protokol Pusher, field `data` bisa berupa JSON string atau object
    int tableId = 0;
    bool lightOn = false;

    if (doc["data"].is<const char*>()) {
      StaticJsonDocument<512> dataDoc;
      deserializeJson(dataDoc, doc["data"].as<const char*>());
      tableId = dataDoc["table_id"] | dataDoc["tableId"] | 0;
      lightOn = dataDoc["light_on"] | dataDoc["device_status"] | false;
    } else {
      tableId = doc["data"]["table_id"] | doc["data"]["tableId"] | 0;
      lightOn = doc["data"]["light_on"] | doc["data"]["device_status"] | false;
    }

    if (tableId >= 1 && tableId <= totalTables) {
      Serial.printf("[WS EVENT] Table %d Light: %s\n", tableId, lightOn ? "ON" : "OFF");
      updateRelay(tableId, lightOn);
      alarm(); // Bunyikan buzzer singkat saat ada update status
    }
  }
}

// ==========================================
// HTTP FALLBACK / STATUS AWAL
// ==========================================
void fetchInitialTableStatus() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(apiBaseUrl);
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
      Serial.println("[HTTP] Status awal semua meja berhasil disinkronkan.");
    }
  } else {
    Serial.printf("[HTTP] Gagal mengambil status meja. Kode: %d\n", httpCode);
  }
  http.end();
}

// ==========================================
// KONTROL RELAY & LCD
// ==========================================
void updateRelay(int tableId, bool lightOn) {
  int index = tableId - 1;
  tableLights[index] = lightOn;
  digitalWrite(ledPins[index], lightOn ? HIGH : LOW);
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
