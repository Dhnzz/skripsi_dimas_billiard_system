#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h> // Tambahkan library ini
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// Konfigurasi Network
const char* ssid = "LPRON";
const char* password = "LPRON2025";

// Alamat API (Sesuaikan IP Server jika bukan localhost)
// Gunakan IP Laptop/Server Anda, misal: http://192.168.1.10:8000
String baseUrl = "https://billiard-system.azhr.cloud/api/microcontroller/table/";

LiquidCrystal_I2C lcd(0x27, 16, 2);

// Pin Relay/LED
const int ledPins[] = {27, 26, 25, 19, 18, 5, 17, 16, 4, 15};
const int buzzer = 13;
const int totalTables = 10;

unsigned long lastCheckTime = 0;
const unsigned long checkInterval = 5000; // Cek API setiap 5 detik

void setup() {
  Serial.begin(115200);
  lcd.init();
  lcd.backlight();
  
  pinMode(buzzer, OUTPUT);
  for(int i = 0; i < totalTables; i++) {
    pinMode(ledPins[i], OUTPUT);
    digitalWrite(ledPins[i], LOW); // Inisialisasi mati
  }

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    alarm();
    delay(1000);
    lcd.setCursor(0,0); lcd.print("MENCARI KONEKSI");
    lcd.setCursor(0,1); lcd.print("     WIFI !");
  }
  lcd.clear();
  lcd.setCursor(0,0); lcd.print("WIFI TERKONEKSI");
  Serial.println("Connected to WiFi");
}

void loop() {
  // Cek API secara berkala
  if (millis() - lastCheckTime > checkInterval) {
    checkAllTables();
    lastCheckTime = millis();
  }
}

void checkAllTables() {
  if (WiFi.status() == WL_CONNECTED) {
    for (int i = 1; i <= totalTables; i++) {
      String fullUrl = baseUrl + String(i) + "/light";
      HTTPClient http;
      
      http.begin(fullUrl);
      int httpResponseCode = http.GET();

      if (httpResponseCode == 200) {
        String payload = http.getString();
        
        // Parsing JSON
        StaticJsonDocument<200> doc;
        DeserializationError error = deserializeJson(doc, payload);

        if (!error) {
          bool lightOn = doc["light_on"]; // true atau false dari API
          int tableId = doc["table_id"];
          
          // Update Relay (Indeks array mulai dari 0, table_id mulai dari 1)
          digitalWrite(ledPins[tableId - 1], lightOn ? HIGH : LOW);
          
          // Update LCD sederhana
          lcd.setCursor(i-1, 1);
          lcd.print(lightOn ? "1" : "0");
          
          Serial.printf("Table %d: %s\n", tableId, lightOn ? "ON" : "OFF");
        }
      } else {
        Serial.print("Error on Table ");
        Serial.print(i);
        Serial.print(": ");
        Serial.println(httpResponseCode);
      }
      http.end();
      delay(100); // Jeda tipis antar request agar tidak membebani server
    }
  }
}

void alarm() {
  digitalWrite(buzzer, HIGH); delay(50);
  digitalWrite(buzzer, LOW); delay(50);
}
