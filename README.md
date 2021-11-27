# IPSymconKebaConnect

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
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

 - IP-Symcon ab Version 6
 - eine Keba KeConnct P30-fähige Wallbox oder eine BMW Wallbox

Hinweis: es können beliebig viele Keba-Wallboxen gleichzeitig und unabhängig betrieben werden

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _KebaConnect_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ aufrufen.

Anschließend den Objektbaum _öffnen_.

![Objektbaum](docs/de/img/objektbaum.png?raw=true "Objektbaum")

Die Instanz _Modules_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](docs/de/img/Modules.png?raw=true "Modules")

![Plus](docs/de/img/plus.png?raw=true "Plus")

![ModulURL](docs/de/img/add_module.png?raw=true "Add Module")

Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/demel42/IPSymconKebaConnect.git
```

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Keba_ und als Gerät _KeConnectP30udp_ auswählen.
Es wird automatisch eine I/O-Instanz vom Type _UDP-Client_ angelegt und das Konfigurationsformular dieser Instanz geöffnet.

Die Konfiguration des UDP-Client wird komplett über die Keba-Geräteinstanz gesteuert.
Hinweis: der UDP-Client dient ausschliesslich zum Emfang von Briadcasts der Wallbox

In dem Konfigurationsformular der KebaConnect-Instanz kann man u.a. konfigurieren, welche Zusatzvariablen übernommen werden sollen.

## 4. Funktionsreferenz

`Dyson_UpdateStatus(int $InstanzID)`<br>
Auslösen einer Aktualisierungs-Anforderug an das Gerät.


`StandbyUpdate(int $InstanzID)`<br>
Abruf aller Daten vom Gerät.

`ChargingUpdate(int $InstanzID)`<br>
Eingeschränkte Abruf Daten vom Gerät (nur _report 2_).

`SendDisplayText(int $InstanzID, string $txt)`<br>
Übertragung eines Textes (max. 23 Zeichen) an eine Wallbox mit Display

`SwitchEnableCharging(int $InstanzID, bool $mode)`<br>
Steuern des Ladevorgangs; aktivieren (LED's blinken grün) oder deaktivieren (LED's blinken blau)

`UnlockPlug(int $InstanzID )`<br>
Entriegeln des Steckers am Fahrzeug, ein eventuell laufender Ladevorgang wird automatisch beendet.

`SetMaxChargingCurrent(int $InstanzID, float $current)`<br>
Setzen des maximalen Ladestroms, minimal 6A, maximal 63A soweit nicht durch Geräte-Konfiguration bzw Kabel/Fahrzeug weiter limitiert.

`SetChargingEnergyLimit(int $InstanzID, float $energy)`<br>
Setzen der für den Ladevorgang verfügbaren Energie

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| host                                  | string   |              | IP-Adresse der Wallbox |
|                                       |          |              | |
| standby_update_interval               | integer  |              | Datenabruf im Ruhezustand in Minuten |
| charging_update_interval              | integer  |              | Datenabruf während des Ladens in Sekunden |


#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
KebaConnect.CableState, KebaConnect.ChargingState, KebaConnect.Error, KebaConnect.MaxCurrent

* Float<br>
KebaConnect.Current, KebaConnect.Power, KebaConnect.Energy, KebaConnect.Voltage, KebaConnect.PowerFactor, KebaConnect.EnergyLimit

## 6. Anhang

GUIDs
- Modul: `{7BEA54BE-F767-1B83-A462-CC7F86941D12}`
- Instanzen:
  - KeConnectP30udp: `{A84E350B-55B7-2841-A6F1-C0B17FA0C4CD}`

Referenzen

[KeContact P20 / P30 UDP Programmers Guide](https://www.keba.com/file/downloads/e-mobility/KeContact_P20_P30_UDP_ProgrGuide_en.pdf)

## 7. Versions-Historie

- 1.0 @ 27.11.2021 18:19 (beta)
  - Initiale Version
