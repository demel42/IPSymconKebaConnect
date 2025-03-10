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

`KebaConnect_SendDisplayText(int $InstanzID, string $txt, int $MinimumDuration, int $MaximumDuration)`<br>
Übertragung eines Textes (max. 23 Zeichen) an eine Wallbox mit Display
*MinimumDuration* gibt die minimale Dauer in Sekunden an (bis die Meldung durch eine neue Meldung überschrieben wird) und *MaximumDuration* die Dauer, bis die Meldung verschwindet;
eine Angabe von **0** verwendet die Systemvorgabe (2s/10s)

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

`KebaConnect_SetOperationMode(int $InstanzID, int $operationMode)`<br>
Betriebsart setzen: 0=aus, 1=manuell, 2=Überschussladen

`KebaConnect_SetMainsConnectionMode(int $InstanzID, int $connectionMode)`<br>
Netzanschluss Phasenumschaltung setzen: 0=dynamisch, 1=1 Phase (fix), 3=3 Phasen (fix)

`KebaConnect_GetHistory(int $InstanzID)`<br>
Liefert die Lade-Historie gemäß den Einstellungen in der Instanz-Konfiguⅹation als json-kodierte Liste von Einträgen.

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| host                                  | string   |              | IP-Adresse der Wallbox |
| serialnumber                          | string   |              | Seriennummer der Wallbox _[1]_ |
|                                       |          |              | |
| save_history                          | boolean  | false        | Ladehistorie sichern |
| show_history                          | boolean  | false        | Ladehistorie in HTML-Box darstellen |
| history_age                           | integer  | 90           | maximales Alter eines Ladevorgangs in Tagen |
|                                       |          |              | |
| save_per_rfid                         | boolean  | false        | Speicherung des Energieverbrauchs pro RFID _[2]_ |
|                                       |          |              | |
| phase_count                           | integer  | 3            | Anzahl der Phases des Netzanschlusses |
| phase_switching                       | boolean  | false        | Unterstützen einer (dynamischen) Phasen-Umschaltung _[3]_ |
| with_surplus_control                  | boolean  | false        | Anlage von Variablen für PV-Überschussladung _[4]_|
|                                       |          |              | |
| standby_update_interval               | integer  | 300          | Aktualisierungsintervall im Ruhezustand in Sekunden |
| charging_update_interval              | integer  | 1            | Aktualisierungsintervall während des Ladens in Sekunden |

- _[1]_: Seriennummer der Wallbox<br>
die Angabe der Seriennummer scheint erforderlich zu sein, wenn mehrere Wallboxen im Verband genutzt werden

- _[2]_: Speicherung des Energieverbrauchs pro RFID<br>
es wird für jede erkannte RFID eine eigene Variable als Aggregation vom Typ _Zähler_ angelegt (Ident *ChargedEnergy_\<RFID-Tag\>*) und nach
jedem Ladeborgang um den jeweiligen Wert erhöht; der Energieverbrauch wird aus der Ladehistorie der Wallbox ermittelt.
Hierfür ist die Speicherung der Ladehistorie erforderlich.<br>
Falls mehrere Keba-Wallboxen im Einsatz sind und eine Gesamtsumme pro RFID genötigt wird, siehe [docs/sum_per_rfid.php](docs/sum_per_rfid.php).

- _[3]_: dynamische Phasen-Umschaltung<br>
die dynamische Phasenumschaktung setzt entsprechende Zusatzhardware voraus (z.B. [KeContact S10 Phase Switching Device](https://www.keba.com/download/x/a5613f1cc0/kecontacts10_ihde.pdf)), die Verbindung von Ausgabe **X2** zu dem Umschalter sowie ein aktuellen Firmwarestand. Siehe Punkt *7.5* auch im [Installationshandbuch](https://www.keba.com/download/x/44d3dc9e54/kecontactp30_ihde_web.pdf).
**Achtung**: diese Funktion ist noch nicht vollständig implementiert und umfänglich getestet!

- _[4]_: PV-Überschussladung<br>
beim Eintragen eines Wertes in diese Variablen (z.B. durch den [EnergieverbrauchOptimierer](https://community.symcon.de/t/energieverbrauch-optimierer-inkl-kachel/133036)) wird in Abhängigkeit der Anzahl der Phasen der _Maximaler Ladestrom_ berechnet.

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:

* Boolean<br>
KebaConnect.ComBackend,
KebaConnect.UnlockPlug,
KebaConnect.YesNo

* Integer<br>
KebaConnect.CableState,
KebaConnect.ChargingState,
KebaConnect.Error,
KebaConnect.OperationMode,
KebaConnect.PhaseSwitch,
KebaConnect.PhaseSwitchSource

* Float<br>
KebaConnect.Current,
KebaConnect.Energy,
KebaConnect.EnergyLimit,
KebaConnect.MaxCurrent,
KebaConnect.Power,
KebaConnect.PowerFactor,
KebaConnect.SurplusPower,
KebaConnect.Voltage

## 6. Anhang

GUIDs
- Modul: `{7BEA54BE-F767-1B83-A462-CC7F86941D12}`
- Instanzen:
  - KeConnectP30udp: `{A84E350B-55B7-2841-A6F1-C0B17FA0C4CD}`

Referenzen

[KeContact P30 UDP Programmers Guide](https://www.keba.com/download/x/4a925c4c61/kecontactp30udp_pgen.pdf)

## 7. Versions-Historie

- 1.17 @ 27.02.2025 14:31
  - Verbesserung: Broadcast-Port kann eingestellt werden

- 1.16 @ 04.02.2025 09:48
  - Verbesserung: KebaConnect_SendDisplayText() nun mit Angabe der Darstellungsdauer

- 1.15 @ 11.11.2024 10:09
  - Verbesserung: Variable zur Phasenumschaltung entsprechend der Umschalt-Ruhezeit temporär deaktivieren
  - Verbesserung: Reduzierung der Häufigkeit von Phasenumschaltung
  - Verbesserung: Abfangen von Fehlern bei nicht erfolgreicher Kommunikation
  - update submodule CommonStubs

- 1.14 @ 13.05.2024 11:40
  - Verbesserung: Absicherung den UDP-Kommunikation mit Semaphore, exakte Einhaltung vorgeschriebener Abstände von UDP-Aufrufen

- 1.13 @ 21.04.2024 08:53
  - Fix: Anpassung der übernahme der Lade-Historie (Feld "reason" hat neuen Wert 5)

- 1.12 @ 15.04.2024 11:34
  - Änderung: Handhabung von SetMainsConnectionPhases(): Beachtung der "Abkühlzeit"

- 1.11 @ 25.03.2024 15:11
  - Fix: Ermittlung der Wallbox-Hardware-Version korrigiert
  - Fix: Funktionen waren fehlerhafterweise "public" (SetMainsConnectionPhases(), EvalSurplusReady())
  - Fix: README.md um Beschreibung öffentlicher Funtionen (KebaConnect_SetOperationMode(), KebaConnect_SetMainsConnectionMode()) ergänzt
  - Neu: Aktion "SetMainsConnectionMode" hinzugefügt
  - update submodule CommonStubs

- 1.10 @ 28.01.2024 15:46
  - Änderung: Medien-Objekte haben zur eindeutigen Identifizierung jetzt ebenfalls ein Ident
  - update submodule CommonStubs

- 1.9.7 @ 05.01.2024 16:57
  - Fix: Schreibfehler im README korrigiert
  - update submodule CommonStubs

- 1.9.6 @ 13.12.2023 14:00
  - Verbesserung; Variablen "Netzanschluss genutzte Phasen" und "Netzanschluss Phasenumschaltung" werden nur noch angelegt, wenn eine dynamische Umschatung möglich sein soll
  - Neu: Einstellung "Anzahl der Phasen des Netzanschlusses"

- 1.9.5 @ 12.12.2023 18:47
  - Fix: Übersetzungsfehler im Variablenprofil "KebaConnect.MainsPhase"

- 1.9.4 @ 12.12.2023 18:36
  - Fix: Variablenprofile "KebaConnect.MaxCurrent" und "KebaConnect.EnergyLimit" wieder im Webfront anpassbar gemacht - aber nur mit Modus "manuell", im Modus "PV-Überschussladen" sind die Variablen nicht editierbar
  - Fix: nach Setzen der Stromstärke sprang der Wert kurz zurück auf 0

- 1.9.3 @ 12.12.2023 17:36
  - Fix: der maximale Strom wurde nicht korrekt ausgelesen, aufgrund des Wechsel von Kommando "curr" zu "currtimer"

- 1.9.2 @ 12.12.2023 09:34
  - Fix: unbekannte Variable (Korrektur zu 1.9.1)
  - Fix: Korrektur im README.md

- 1.9.1 @ 30.11.2023 11:07
  - Neu: Modul-interne Protokollierung der Aktivitäten/Einstellungen zur besseren Nachvollziehbarkeit insbesondere bei PV-Überschussladung

- 1.9 @ 29.11.2023 11:27
  - Neu: Unterstützung von PV-Überschussladen mit dynamischer Umschaltung der Ladestromstärke
    Für das Zusammenspiel mit dem Energieverbrauch-Optimierer stellt das Modul Variablen zur Verfüpung
	- zu Setzen der verfügbaren Leistung
	- zur Abfrage, ob die Wallbox ladebereit ist
  - Neu: 1/3-phasiges Laden
    Vorbereitung von dynamischem Umschalten - ungetestet -

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
