#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ==============================================================================
// 1. KONFIGURASI JARINGAN & SERVER CLOUDFLARE
// ==============================================================================
const char* ssid     = "LPRON";
const char* password = "LPRON2025";

// Endpoint Batch Status Lampu Seluruh Meja
// Mengambil data 10 meja sekaligus dalam 1 request via HTTPS Cloudflare Tunnel
const char* apiBatchUrl = "https://billiard-system.azhr.cloud/api/microcontroller/tables/light";

// ==============================================================================
// 2. HARDWARE, PIN & RELAY POLARITY CONFIGURATION
// ==============================================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Pin Relay (Indeks 0 = Meja 1, Indeks 9 = Meja 10)
const int ledPins[]   = {27, 26, 25, 19, 18, 5, 17, 16, 4, 15};
const int buzzer      = 13;
const int totalTables = 10;

/**
 * PENTING: Pengaturan Polaritas Modul Relay
 * - Set 'false' (Active LOW): Mayoritas modul relay 5V Arduino di pasaran (LOW = Relay ON/Menyala, HIGH = Relay OFF/Padam)
 * - Set 'true'  (Active HIGH): Jika modul relay Anda berjenis Active HIGH (HIGH = Relay ON, LOW = Relay OFF)
 */
const bool RELAY_ACTIVE_HIGH = false;

// Status lampu lokal saat ini [0..9] untuk perbandingan perubahan (state change detection)
bool currentLightStates[totalTables] = {false};

// ==============================================================================
// 3. INTERVAL POLLING (5000 ms = 5 detik)
// ==============================================================================
unsigned long lastPollTime = 0;
const unsigned long pollInterval = 5000;

// Counter kegagalan HTTP untuk deteksi koneksi putus
int consecutiveErrors = 0;

// Prototype functions
void pollAllTables();
void setRelayPhysical(int tableIndex, bool turnOn);
void updateLcdDisplay();
void beepNotification();

// ==============================================================================
// SETUP
// ==============================================================================
void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n--- BILLIARD CONTROLLER INITIALIZING ---");

  lcd.init();
  lcd.backlight();
  pinMode(buzzer, OUTPUT);
  digitalWrite(buzzer, LOW);

  // Inisialisasi awal seluruh pin relay ke posisi MATI (Padam)
  for (int i = 0; i < totalTables; i++) {
    pinMode(ledPins[i], OUTPUT);
    setRelayPhysical(i, false);
    currentLightStates[i] = false;
  }

  // Koneksi ke WiFi
  lcd.setCursor(0, 0); lcd.print("MENGHUBUNGKAN...");
  lcd.setCursor(0, 1); lcd.print(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int wifiAttempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    beepNotification();
    delay(500);
    Serial.print(".");
    wifiAttempts++;
    if (wifiAttempts > 40) { // Jika gagal 20 detik, restart WiFi
      Serial.println("\n[WIFI] Gagal konek, mencoba ulang...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);
      wifiAttempts = 0;
    }
  }

  Serial.println("\n[WIFI] Terhubung! IP: " + WiFi.localIP().toString());
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("WIFI TERHUBUNG! ");
  lcd.setCursor(0, 1); lcd.print(WiFi.localIP().toString().substring(0, 16));
  delay(1500);

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("M123456789X OK ");
  updateLcdDisplay();

  // Polling pertama kali saat boot
  pollAllTables();
}

// ==============================================================================
// MAIN LOOP
// ==============================================================================
void loop() {
  // Reconnect WiFi otomatis jika terputus
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WIFI] Koneksi terputus! Mencoba menghubungkan kembali...");
    lcd.setCursor(0, 0); lcd.print("WIFI DISCONNECT ");
    WiFi.reconnect();
    delay(2000);
    return;
  }

  // Polling per 5000ms (5 detik)
  if (millis() - lastPollTime >= pollInterval) {
    lastPollTime = millis();
    pollAllTables();
  }
}

// ==============================================================================
// POLLING BATCH 10 MEJA SEKALIGUS (HTTPS / SSL Insecure)
// ==============================================================================
void pollAllTables() {
  WiFiClientSecure client;
  // Bypass validasi sertifikat CA agar kompatibel dengan SSL Cloudflare Edge
  client.setInsecure();

  HTTPClient http;
  http.setTimeout(4000); // Timeout 4 detik

  if (!http.begin(client, apiBatchUrl)) {
    Serial.println("[HTTP] Inisialisasi HTTPClient gagal.");
    return;
  }

  int httpCode = http.GET();

  if (httpCode == 200) {
    consecutiveErrors = 0;
    String payload = http.getString();

    // Parse JSON Response: {"data": [{"table_id": 1, "light_on": true}, ...]}
    StaticJsonDocument<2048> doc;
    DeserializationError error = deserializeJson(doc, payload);

    if (!error && doc.containsKey("data")) {
      JsonArray array = doc["data"].as<JsonArray>();
      bool stateChanged = false;

      for (JsonObject obj : array) {
        int tableId   = obj["table_id"] | 0;
        bool lightOn  = obj["light_on"] | false;

        // Validasi ID Meja (1 s/d 10)
        if (tableId >= 1 && tableId <= totalTables) {
          int index = tableId - 1;

          // Cek apakah ada perubahan status
          if (currentLightStates[index] != lightOn) {
            stateChanged = true;
            currentLightStates[index] = lightOn;
            setRelayPhysical(index, lightOn);

            Serial.printf("[UPDATE] Meja %d -> Lampu %s\n", 
                          tableId, lightOn ? "MENYALA (ON)" : "PADAM (OFF)");
          }
        }
      }

      if (stateChanged) {
        updateLcdDisplay();
        beepNotification(); // Bunyi buzzer singkat sebagai indikasi status berubah
      }
    } else {
      Serial.printf("[JSON] Gagal parsing JSON: %s\n", error.c_str());
    }
  } else {
    consecutiveErrors++;
    Serial.printf("[HTTP ERROR] Code: %d (Gagal ke-%d)\n", httpCode, consecutiveErrors);

    if (consecutiveErrors >= 3) {
      lcd.setCursor(0, 0); 
      lcd.printf("ERR HTTP:%d   ", httpCode);
    }
  }

  http.end();
}

// ==============================================================================
// KONTROL FISIK RELAY
// ==============================================================================
void setRelayPhysical(int tableIndex, bool turnOn) {
  int pin = ledPins[tableIndex];

  if (RELAY_ACTIVE_HIGH) {
    digitalWrite(pin, turnOn ? HIGH : LOW);
  } else {
    // Active LOW: Beri sinyal LOW agar relay menyala, HIGH agar relay mati
    digitalWrite(pin, turnOn ? LOW : HIGH);
  }
}

// ==============================================================================
// UPDATE DISPLAY LCD 16x2
// Baris 0: Status Header (misal: "M123456789X OK ")
// Baris 1: Status Tiap Meja ("1" = Nyala, "0" = Padam)
// ==============================================================================
void updateLcdDisplay() {
  lcd.setCursor(0, 0);
  lcd.print("M:12345678910 OK");

  lcd.setCursor(0, 1);
  for (int i = 0; i < totalTables; i++) {
    lcd.print(currentLightStates[i] ? "1" : "0");
  }
  lcd.print("    "); // Bersihkan sisa karakter
}

// ==============================================================================
// BUZZER BEEP
// ==============================================================================
void beepNotification() {
  digitalWrite(buzzer, HIGH);
  delay(50);
  digitalWrite(buzzer, LOW);
}
