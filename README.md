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
 - eine Keba KeConnct P30-fähige Wallbox

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

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Keba_ und als Gerät _KeConnectP30_ auswählen.
Es wird automatisch eine I/O-Instanz vom Type Client-IO-Socket und せine Splitter-Instanz vom Type ModBus angelegt und das Konfigurationsformular dieser Instanzen geöffnet.

Folgende Konfiguration
- Splitter-Instanz: _Geräte-ID_ = 255, _Swap_ = **aus**
- IO-Instanz: IP-Adresse der Wallbox sowie die _Portnummer_ = **502**

In dem Konfigurationsformular der KebaConnect-Instanz kann man konfigurieren, welche Variablen übernommen werden sollen.

## 4. Funktionsreferenz

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
|                                       |          |              | |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
KebaConnect.ChargingState, KebaConnect.CableState, KebaConnect.Error

* Float<br>
KebaConnect.Current, KebaConnect.Power, KebaConnect.Energy, KebaConnect.Voltage, KebaConnect.Factor

## 6. Anhang

GUIDs
- Modul: `{7BEA54BE-F767-1B83-A462-CC7F86941D12}`
- Instanzen:
  - KeConnectP30: `{9751633B-9E5C-CA9C-9096-9E0031F48E7E}`

Referenzen

https://www.keba.com/download/x/dea7ae6b84/kecontactp30modbustcp_pgen.pdf

## 7. Versions-Historie

- 1.0 @ 25.11.2021 18:06 (beta)
  - Initiale Version
