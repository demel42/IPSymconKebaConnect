# IPSymconKebaConnect

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

## 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- eine Keba KeConnct P30-fähige Wallbox oder eine BMW Wallbox

Hinweis: es können beliebig viele Keba-Wallboxen gleichzeitig und unabhängig betrieben werden

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Den **Modulstore** öffnen und im Suchfeld nun `KebaConnect` eingeben, das Modul auswählen und auf _Installieren_ auswählen.
Alternativ kann das Modul auch über **ModulControl** (im Objektbaum innerhalb _Kern Instanzen_ die Instanz _Modules_) installiert werden,
als URL muss `https://github.com/demel42/IPSymconKebaConnect` angegeben werden.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ auswählen, als Hersteller _Keba_ und als Gerät _KeConnectP30udp_ auswählen.

Es wird automatisch eine I/O-Instanz vom Type _UDP-Client_ angelegt und das Konfigurationsformular dieser Instanz geöffnet.

Die Konfiguration des UDP-Client wird komplett über die Keba-Geräteinstanz gesteuert.
Hinweis: der UDP-Client dient ausschliesslich zum Empfang von Broadcasts der Wallbox

In dem Konfigurationsformular der KebaConnect-Instanz kann man u.a. konfigurieren, welche Zusatzvariablen übernommen werden sollen.

## 4. Funktionsreferenz

`KebaConnect_StandbyUpdate(int $InstanzID)`<br>
Abruf aller Daten vom Gerät.

`KebaConnect_ChargingUpdate(int $InstanzID)`<br>
Eingeschränkte Abruf Daten vom Gerät (nur _report 2_).

`KebaConnect_SendDisplayText(int $InstanzID, string $txt)`<br>
Übertragung eines Textes (max. 23 Zeichen) an eine Wallbox mit Display

`KebaConnect_SwitchEnableCharging(int $InstanzID, bool $mode)`<br>
Steuern des Ladevorgangs; aktivieren (LED's blinken grün) oder deaktivieren (LED's blinken blau)

`KebaConnect_UnlockPlug(int $InstanzID )`<br>
Entriegeln des Steckers am Fahrzeug, ein eventuell laufender Ladevorgang wird automatisch beendet.

`KebaConnect_SetMaxChargingCurrent(int $InstanzID, float $current)`<br>
Setzen des maximalen Ladestroms, zwischen minimal 6A und maximal 63A (soweit nicht durch Geräte-Konfiguration bzw Kabel/Fahrzeug weiter limitiert).

`KebaConnect_SetChargingEnergyLimit(int $InstanzID, float $energy)`<br>
Setzen der für den Ladevorgang verfügbaren Energie

`KebaConnect_AuthorizeSession(int $InstanzID, string $Tag, string $Class)`<br>
Authorisierung eines Ladevorgangs durch Angabe von *Tag* und *Class* der RFID-Karte

`KebaConnect_DeauthorizeSession(int $InstanzID, string $Tag)`<br>
Aufhebung einer vorangegangenen Authorisierung eines Ladevorgangs, hier wird nur das *Tag* benötigt

`KebaConnect_GetHistory(int $InstanzID)`<br>
Liefert die Lade-Historie gemäß den Einstellungen in der Instanz-Konfiguⅹation als json-kodierte Liste von Einträgen.

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| host                                  | string   |              | IP-Adresse der Wallbox |
| serialnumber                          | string   |              | Seriennummer der Wallbox |
|                                       |          |              | |
| save_history                          | boolean  | false        | Ladehistorie sichern |
| show_history                          | boolean  | false        | Ladehistorie in HTML-Box darstellen |
| history_age                           | integer  | 90           | maximales Alter eines Ladevorgangs in Tagen |
|                                       |          |              | |
| save_per_rfid                         | boolean  | false        | Speicherung des Energieverbrauchs pro RFID |
|                                       |          |              | |
| standby_update_interval               | integer  | 300          | Aktualisierungsintervall im Ruhezustand in Sekunden |
| charging_update_interval              | integer  | 1            | Aktualisierungsintervall während des Ladens in Sekunden |

* Seriennummer der Wallbox<br>
die Angabe der Seriennummer scheint erforderlich zu sein, wenn mehrere Wallboxen im Verband genutzt werden

* Speicherung des Energieverbrauchs pro RFID<br>
es wird für jede erkannte RFID eine eigene Variable als Aggregation vom Typ _Zähler_ angelegt (Ident *ChargedEnergy_\<RFID-Tag\>*) und nach
jedem Ladeborgang um den jeweiligen Wert erhöht; der Energieverbrauch wird aus der Ladehistorie der Wallbox ermittelt.
Hierfür ist die Speicherung der Ladehistorie erforderlich.<br>
Falls mehrere Keba-Wallboxen im Einsatz sind und eine Gesamtsumme pro RFID genötigt wird, siehe [docs/sum_per_rfid.php](docs/sum_per_rfid.php).

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:

* Boolean<br>
KebaConnect.ComBackend,
KebaConnect.EnableCharging,
KebaConnect.UnlockPlug

* Integer<br>
KebaConnect.CableState,
KebaConnect.ChargingState,
KebaConnect.Error,
KebaConnect.MaxCurrent

* Float<br>
KebaConnect.Current,
KebaConnect.Energy,
KebaConnect.EnergyLimit,
KebaConnect.Power,
KebaConnect.PowerFactor,
KebaConnect.Voltage

## 6. Anhang

GUIDs
- Modul: `{7BEA54BE-F767-1B83-A462-CC7F86941D12}`
- Instanzen:
  - KeConnectP30udp: `{A84E350B-55B7-2841-A6F1-C0B17FA0C4CD}`

Referenzen

[KeContact P20 / P30 UDP Programmers Guide](https://www.keba.com/file/downloads/e-mobility/KeContact_P20_P30_UDP_ProgrGuide_en.pdf)

## 7. Versions-Historie

- 1.8 @ 19.09.2023 16:48
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.7 @ 05.07.2023 11:56
  - Fix: die Funktionen 'KebaConnect_StandbyUpdate' und 'KebaConnect_ChargingUpdate' sind wieder verfügbar
  - Neu: Schalter, um die Meldung eines inaktiven Gateway zu steuern
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.6.3 @ 12.10.2022 12:01
  - Fix: Aktion "Ladeenergie begrenzen" war fehlerhaft

- 1.6.2 @ 11.10.2022 08:23
  - update submodule CommonStubs
    Fix: Fehler in SetVariableLogging()

- 1.6.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.6 @ 31.08.2022 08:09
  - Verbesserung: bei fehlender NTP-Zeisynchronisation der Wallbox wird diese aus dem IPS eingestellt
  - Fix: verbessete Dekodierung von übermittelten Zeitstempeln bei fehlender Synchronisation
  - update submodule CommonStubs

- 1.5 @ 04.08.2022 16:45
  - Änderung: "Aktualisierungsintervall im Ruhezustand" kann nun in Sekunden angegeben werden
  - update submodule CommonStubs

- 1.4.2 @ 02.08.2022 18:37
  - Fix: kleiner Übersetzungsfehler im Testbereich der Instanz

- 1.4.1 @ 26.07.2022 10:28
  - update submodule CommonStubs
    Fix: CheckModuleUpdate() nicht mehr aufrufen, wenn das erstmalig installiert wird

- 1.4 @ 05.07.2022 09:35
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert

- 1.3.2 @ 22.06.2022 10:33
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert

- 1.3.1 @ 28.05.2022 11:37
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts

- 1.3 @ 28.05.2022 09:46
  - update submodule CommonStubs
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun entweder private oder nur noch via IPS_RequestAction() erreichbar

- 1.2.4 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.2.3 @ 10.05.2022 15:06
  - update submodule CommonStubs

- 1.2.2 @ 29.04.2022 17:59
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)

- 1.2.1 @ 26.04.2022 12:26
  - Korrektur: self::$IS_DEACTIVATED wieder IS_INACTIVE

- 1.2 @ 20.04.2022 09:43
  - zusätzliches optionale Feld "Kommunikations-Backend"
  - zusätzliche Funktionen/Aktionen AuthorizeSession() und DeauthorizeSession() um bei einer Wallbox mit RFID-Autorisierung diese aus dem IPS heraus zu setzen/zu löschen
  - Implememtierung einer Update-Logik
  - diverse interne Änderungen

- 1.1.1 @ 16.04.2022 12:07
  - potentieller Namenskonflikt behoben (trait CommonStubs)
  - Aktualisierung von submodule CommonStubs

- 1.1 @ 11.04.2022 11:18
  - Ausgabe der Instanz-Timer unter "Referenzen"
  - verbesserte Ausgabe der Timer im Debug
  - Schreibfehler korrigiert

- 1.0.17 @ 03.03.2022 14:07
  - Fix in CommonStubs
  - Anzeige referenzierten Statusvariablen

- 1.0.16 @ 01.03.2022 21:57
  - Anzeige der Referenzen der Instanz
  - Korrektur zu 1.0.12

- 1.0.15 @ 19.02.2022 15:20
  - Aktionen hinzugefügt
  - Anpassungen an IPS 6.2 (Prüfung auf ungültige ID's)
  - libs/common.php -> submodule CommonStubs

- 1.0.14 @ 12.02.2022 07:39
  - Korrektur zu 1.0.12

- 1.0.13 @ 10.02.2022 10:53
  - Verbesserung der Auswertung des Broadcast

- 1.0.12 @ 06.02.2022 14:56
  - optionale Speicherung des Verbrauchs pro RFID

- 1.0.11 @ 18.01.2022 12:22
  - optionale Angabe der Seriennummer der Wallbox

- 1.0.10 @ 14.01.2022 17:13
  - Lade-Historie

- 1.0.9 @ 18.12.2021 10:13
  - Vereinfachung der Socket-Kommunikation

- 1.0.8 @ 16.12.2021 20:14
  - ClientIp des Broadcast wurde nicht korrekt ausgewertet
  - Feld "Setenergy" wird in 0.1 Wh übertragen,muss also durch 10.000 geteilt werden um 10 KWh zu bekommen

- 1.0.7 @ 16.12.2021 13:53
  - Absicherung der Anzeige der Modul/Bibliotheks-Informationen

- 1.0.6 @ 14.12.2021 14:01
  - Initiale Version
