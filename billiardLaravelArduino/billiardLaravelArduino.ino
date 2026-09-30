#include <WiFi.h> 
#include <WiFiClientSecure.h> 
#include <HTTPClient.h> 
#include <ArduinoJson.h> 
#include <Wire.h> 
#include <LiquidCrystal_I2C.h> 

// ============================================================================ // 
// 1. KONFIGURASI WIFI & ENDPOINT SERVER                                        // 
// ============================================================================ // 
const char* ssid = "LPRON";          // Ganti dengan WiFi tempat ESP32 berada 
const char* password = "LPRON2025";  // Ganti dengan Password WiFi 

// Endpoint Batch (1 request langsung dapat status 10 meja) 
const String urlBatch = "https://billiard-system.azhr.cloud/api/microcontroller/tables/light"; 

// ============================================================================ // 
// 2. HARDWARE & POLARITAS RELAY                                               // 
// ============================================================================ // 
LiquidCrystal_I2C lcd(0x27, 16, 2); 
const int ledPins[] = {27, 26, 25, 19, 18, 5, 17, 16, 4, 15}; 
const int buzzer = 13; 
const int totalTables = 10; 
bool tableStates[totalTables] = {false}; 

// Set 'true' jika modul relay nyala saat sinyal HIGH (Active-HIGH)
// Set 'false' jika modul relay nyala saat sinyal LOW (Active-LOW)
const bool RELAY_ACTIVE_HIGH = true; 

unsigned long lastCheckTime = 0; 
const unsigned long checkInterval = 5000; // Polling tiap 5 detik 

// Forward declarations
void connectWiFi();
void checkAllTablesBatch();
void writeRelayPin(int index, bool on);
bool updateRelay(int tableId, bool lightOn); 
void updateLcd();
void alarm(); 

void writeRelayPin(int index, bool on) {
  int level = on ? (RELAY_ACTIVE_HIGH ? HIGH : LOW) : (RELAY_ACTIVE_HIGH ? LOW : HIGH);
  digitalWrite(ledPins[index], level);
}

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  delay(100);

  WiFi.begin(ssid, password);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("MENCARI KONEKSI");
  lcd.setCursor(0, 1);
  lcd.print(" WIFI !         ");

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    alarm();
    delay(500);
    Serial.printf("[WIFI] Status: %d\n", WiFi.status());
    attempts++;

    // Jika lewat 20 detik gagal, reset radio dan coba lagi
    if (attempts > 40) {
      Serial.println("[WIFI] Retry WiFi.begin...");
      WiFi.disconnect();
      delay(200);
      WiFi.begin(ssid, password);
      attempts = 0;
    }
  }

  Serial.println("\n[WIFI] Terkoneksi!");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("WIFI TERKONEKSI ");
  lcd.setCursor(0, 1);
  lcd.print(WiFi.localIP().toString());
  delay(1500);
  lcd.clear();
  updateLcd();
}

// ============================================================================ // 
// SETUP                                                                        // 
// ============================================================================ // 
void setup() { 
  Serial.begin(115200); 
  lcd.init(); 
  lcd.backlight(); 
  pinMode(buzzer, OUTPUT); 

  // Inisialisasi pin relay (Kondisi awal: SEMUA RELAY MATI) 
  for (int i = 0; i < totalTables; i++) { 
    pinMode(ledPins[i], OUTPUT); 
    writeRelayPin(i, false); 
  } 

  // Koneksi WiFi pertama kali
  connectWiFi();

  // Ambil data pertama kali langsung di boot
  Serial.println("[HTTP] Inisialisasi status meja pertama kali...");
  checkAllTablesBatch();
} 

// ============================================================================ // 
// LOOP                                                                         // 
// ============================================================================ // 
void loop() { 
  // Jika WiFi putus, langsung masuk mode penyambungan ulang
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WIFI] Terputus! Mencoba menyambung kembali...");
    connectWiFi();
    checkAllTablesBatch();
    lastCheckTime = millis();
    return;
  }

  // Polling data meja secara berkala
  if (millis() - lastCheckTime >= checkInterval) { 
    lastCheckTime = millis(); 
    checkAllTablesBatch(); 
  } 
} 

// ============================================================================ // 
// BATCH POLLING (1 Request untuk 10 Meja via HTTPS)                             // 
// ============================================================================ // 
void checkAllTablesBatch() { 
  WiFiClientSecure client; 
  client.setInsecure(); // BYPASS SSL CLOUDFLARE 
  
  HTTPClient http; 
  http.setTimeout(10000); // 10 detik agar aman untuk handshake TLS hotspot

  if (!http.begin(client, urlBatch)) { 
    Serial.println("[HTTP] Begin failed"); 
    client.stop();
    return; 
  } 

  http.addHeader("User-Agent", "ESP32-BilliardController");

  int httpCode = http.GET(); 
  Serial.printf("[HTTP] GET status: %d\n", httpCode);

  if (httpCode == 200) { 
    String payload = http.getString(); 

#if ARDUINOJSON_VERSION_MAJOR >= 7
    JsonDocument doc;
#else
    StaticJsonDocument<2048> doc;
#endif

    DeserializationError err = deserializeJson(doc, payload); 
    
    if (!err && doc["data"].is<JsonArray>()) { 
      bool stateChanged = false;
      JsonArray array = doc["data"].as<JsonArray>(); 
      for (JsonObject obj : array) { 
        int tableId = obj["table_id"] | 0; 
        bool lightOn = obj["light_on"] | false; 
        
        if (tableId >= 1 && tableId <= totalTables) { 
          if (updateRelay(tableId, lightOn)) {
            stateChanged = true;
            Serial.printf("[RELAY] Meja %d diubah -> %s\n", tableId, lightOn ? "NYALA" : "PADAM");
          }
        } 
      } 
      updateLcd();
      if (stateChanged) {
        alarm();
      }
    } else if (err) {
      Serial.printf("[JSON] Parse error: %s\n", err.c_str());
    }
  } else { 
    Serial.printf("[HTTP] Error Code: %d (%s)\n", httpCode, http.errorToString(httpCode).c_str()); 
  } 
  http.end(); 
  client.stop();
} 

bool updateRelay(int tableId, bool lightOn) { 
  int index = tableId - 1; 
  bool changed = (tableStates[index] != lightOn);
  tableStates[index] = lightOn;

  writeRelayPin(index, lightOn);
  
  return changed;
} 

void updateLcd() {
  lcd.setCursor(0, 0);
  lcd.print("M:1234567890    ");
  lcd.setCursor(0, 1);
  lcd.print("L:");
  for (int i = 0; i < totalTables; i++) {
    lcd.print(tableStates[i] ? "1" : "0");
  }
  lcd.print("    ");
}

void alarm() { 
  digitalWrite(buzzer, HIGH); 
  delay(40); 
  digitalWrite(buzzer, LOW); 
}
