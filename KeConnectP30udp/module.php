<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php'; // globale Funktionen
require_once __DIR__ . '/../libs/local.php';  // lokale Funktionen

class KeConnectP30udp extends IPSModule
{
    use KebaConnectCommonLib;
    use KebaConnectLocalLib;

    private static $UnicastPort = 7090;
    private static $BroadcastPort = 7092;

    public static $STATE_SYSTEM_STARTED = 0;
    public static $STATE_NOTREADY = 1;
    public static $STATE_READY = 2;
    public static $STATE_CHARGING = 3;
    public static $STATE_ERROR = 4;
    public static $STATE_SUSPENDED = 5;

    public static $CABLE_NOT_PLUGGED = 0;
    public static $CABLE_PLUGGED_IN_STATION = 1;
    public static $CABLE_LOCKED_IN_STATION = 3;
    public static $CABLE_PLUGGED_IN_VEHICLE = 5;
    public static $CABLE_LOCKED_IN_VEHICLE = 7;

    public static $fixedVariables = [
        'ChargingState',
        'CableState',

        'ActivePower',
        'ChargedEnergy',
        'TotalEnergy',

        'MaxChargingCurrent',
        'MaxSupportedCurrent',

        'EnableCharging',
        'ChargingEnergyLimit',
    ];

    private static $optionalVariables = [
        [
            'Ident'           => 'CurrentPhase1',
            'Desc'            => 'Charging current phase 1',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
        ],
        [
            'Ident'           => 'CurrentPhase2',
            'Desc'            => 'Charging current phase 2',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
        ],
        [
            'Ident'           => 'CurrentPhase3',
            'Desc'            => 'Charging current phase 3',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
        ],

        [
            'Ident'           => 'VoltagePhase1',
            'Desc'            => 'Voltage phase 1',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],
        [
            'Ident'           => 'VoltagePhase2',
            'Desc'            => 'Voltage phase 2',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],
        [
            'Ident'           => 'VoltagePhase3',
            'Desc'            => 'Voltage phase 3',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],

        [
            'Ident'           => 'PowerFactor',
            'Desc'            => 'Power factor',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.PowerFactor',
        ],

        [
            'Ident'           => 'ProductType',
            'Desc'            => 'Product type',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Ident'           => 'SerialNumber',
            'Desc'            => 'Serial number',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Ident'           => 'FirmwareVersion',
            'Desc'            => 'Firmware version',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Ident'           => 'LastBoot',
            'Desc'            => 'Last boot',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => '~UnixTimestamp',
        ],

        [
            'Ident'           => 'ChargingStarted',
            'Desc'            => 'Charging started',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => '~UnixTimestamp',
        ],
        [
            'Ident'           => 'ChargingEnded',
            'Desc'            => 'Charging ended',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => '~UnixTimestamp',
        ],
        [
            'Ident'           => 'RFID',
            'Desc'            => 'RFID card',
            'VariableType'    => VARIABLETYPE_STRING,
        ],

        [
            'Ident'           => 'ErrorCode',
            'Desc'            => 'Error code',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.Error',
        ],
        [
            'Ident'           => 'ErrorText',
            'Desc'            => 'Error text',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
    ];

    public function InstallVarProfiles(bool $reInstall = false)
    {
        $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);

        $this->CreateVarProfile('KebaConnect.Current', VARIABLETYPE_FLOAT, ' A', 0, 0, 0, 1, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Power', VARIABLETYPE_FLOAT, ' kW', 0, 0, 0, 2, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Energy', VARIABLETYPE_FLOAT, ' kWh', 0, 0, 0, 2, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 0, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.PowerFactor', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 1, '', '', $reInstall);

        $this->CreateVarProfile('KebaConnect.MaxCurrent', VARIABLETYPE_INTEGER, ' A', 0, 0, 0, 0, '', '', $reInstall);

        $associations = [];
        $associations[] = ['Wert' => self::$STATE_SYSTEM_STARTED, 'Name' => $this->Translate('system started'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$STATE_NOTREADY, 'Name' => $this->Translate('not ready for charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$STATE_READY, 'Name' => $this->Translate('ready for charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$STATE_CHARGING, 'Name' => $this->Translate('charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$STATE_ERROR, 'Name' => $this->Translate('error occured'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$STATE_SUSPENDED, 'Name' => $this->Translate('charging suspended'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.ChargingState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [];
        $associations[] = ['Wert' => self::$CABLE_NOT_PLUGGED, 'Name' => $this->Translate('not plugged'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$CABLE_PLUGGED_IN_STATION, 'Name' => $this->Translate('plugged in station'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$CABLE_LOCKED_IN_STATION, 'Name' => $this->Translate('locked in station'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$CABLE_PLUGGED_IN_VEHICLE, 'Name' => $this->Translate('plugged in vehicle'), 'Farbe' => -1];
        $associations[] = ['Wert' => self::$CABLE_LOCKED_IN_VEHICLE, 'Name' => $this->Translate('locked in vehicle'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.CableState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('no error'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('Error 0x%05x'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.Error', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('no limit'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('%0.0f kWh'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.EnergyLimit', VARIABLETYPE_FLOAT, '', 0, 100, 1, 0, '', $associations, $reInstall);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('no'), 'Farbe' => -1];
        $associations[] = ['Wert' => true, 'Name' => $this->Translate('yes'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.EnableCharging', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('-'), 'Farbe' => -1];
        $associations[] = ['Wert' => true, 'Name' => $this->Translate('unlock'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.UnlockPlug', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyString('serialnumber', '');
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyBoolean('save_history', false);
        $this->RegisterPropertyBoolean('show_history', false);
        $this->RegisterPropertyInteger('history_age', 90);

        $this->RegisterPropertyInteger('standby_update_interval', '5');
        $this->RegisterPropertyInteger('charging_update_interval', '1');

        $this->RegisterTimer('StandbyUpdate', 0, 'KebaConnect_StandbyUpdate(' . $this->InstanceID . ');');
        $this->RegisterTimer('ChargingUpdate', 0, 'KebaConnect_ChargingUpdate(' . $this->InstanceID . ');');

        $this->InstallVarProfiles(false);

        $this->RequireParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');
    }

    private function CheckConfiguration()
    {
        $s = '';
        $r = [];

        $host = $this->ReadPropertyString('host');
        if ($host == '') {
            $this->SendDebug(__FUNCTION__, '"host" is needed', 0);
            $r[] = $this->Translate('Host must be specified');
        }

        $save_history = $this->ReadPropertyBoolean('save_history');
        $show_history = $this->ReadPropertyBoolean('show_history');
        if ($show_history && $save_history == false) {
            $this->SendDebug(__FUNCTION__, '"show_history" needs "save_history"', 0);
            $r[] = $this->Translate('to be able to display the charging history, it must be saved');
        }

        if ($r != []) {
            $s = $this->Translate('The following points of the configuration are incorrect') . ':' . PHP_EOL;
            foreach ($r as $p) {
                $s .= '- ' . $p . PHP_EOL;
            }
        }

        return $s;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;
        $this->MaintainVariable('ChargingState', $this->Translate('Charging state'), VARIABLETYPE_INTEGER, 'KebaConnect.ChargingState', $vpos++, true);
        $this->MaintainVariable('CableState', $this->Translate('Cable state'), VARIABLETYPE_INTEGER, 'KebaConnect.CableState', $vpos++, true);

        $vpos = 10;
        $this->MaintainVariable('ActivePower', $this->Translate('Active power'), VARIABLETYPE_FLOAT, 'KebaConnect.Power', $vpos++, true);
        $this->MaintainVariable('ChargedEnergy', $this->Translate('Power consumption of the current loading session'), VARIABLETYPE_FLOAT, 'KebaConnect.Energy', $vpos++, true);
        $this->MaintainVariable('TotalEnergy', $this->Translate('Total energy'), VARIABLETYPE_FLOAT, 'KebaConnect.Energy', $vpos++, true);

        $vpos = 20;
        $this->MaintainVariable('EnableCharging', $this->Translate('Enable charging'), VARIABLETYPE_BOOLEAN, 'KebaConnect.EnableCharging', $vpos++, true);
        $this->MaintainAction('EnableCharging', true);

        $this->MaintainVariable('UnlockPlug', $this->Translate('Unlock plug'), VARIABLETYPE_BOOLEAN, 'KebaConnect.UnlockPlug', $vpos++, true);
        $this->MaintainAction('UnlockPlug', true);

        $this->MaintainVariable('MaxChargingCurrent', $this->Translate('Max charging current'), VARIABLETYPE_INTEGER, 'KebaConnect.MaxCurrent', $vpos++, true);
        $this->MaintainAction('MaxChargingCurrent', true);

        $this->MaintainVariable('ChargingEnergyLimit', $this->Translate('Charging energy limit'), VARIABLETYPE_FLOAT, 'KebaConnect.EnergyLimit', $vpos++, true);
        $this->MaintainAction('ChargingEnergyLimit', true);

        $this->MaintainVariable('MaxSupportedCurrent', $this->Translate('Max supported current'), VARIABLETYPE_INTEGER, 'KebaConnect.MaxCurrent', $vpos++, true);

        $vpos = 40;
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $desc = $this->Translate($var['Desc']);
            $vartype = $var['VariableType'];
            $varprof = isset($var['VariableProfile']) ? $var['VariableProfile'] : '';
            $this->MaintainVariable($ident, $desc, $vartype, $varprof, $vpos++, $use);
        }

        $vpos = 80;
        $show_history = $this->ReadPropertyBoolean('show_history');
        if ($show_history) {
            $this->MaintainVariable('History', $this->Translate('Charging history'), VARIABLETYPE_STRING, '~HTMLBox', $vpos++, $show_history);
        }

        $vpos = 90;
        $this->MaintainVariable('LastChange', $this->Translate('Last change'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $refs = $this->GetReferenceList();
        foreach ($refs as $ref) {
            $this->UnregisterReference($ref);
        }
        $propertyNames = [];
        foreach ($propertyNames as $name) {
            $oid = $this->ReadPropertyInteger($name);
            if ($oid > 0) {
                $this->RegisterReference($oid);
            }
        }

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('StandbyUpdate', 0);
            $this->SetTimerInterval('ChargingUpdate', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->SetTimerInterval('StandbyUpdate', 0);
            $this->SetTimerInterval('ChargingUpdate', 0);
            $this->SetStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $this->SetStatus(IS_ACTIVE);

        $this->SetStandbyUpdateInterval();
        $this->SetChargingUpdateInterval();
    }

    public function UpdateFields()
    {
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);

        $chg = false;
        foreach ($use_fields as $field) {
            $ident = $field['ident'];
            $fnd = false;
            foreach (self::$optionalVariables as $var) {
                if ($ident == $var['Ident']) {
                    $fnd = true;
                    break;
                }
            }
            if ($fnd == false) {
                $chg = true;
            }
        }

        $values = [];
        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $use = false;
            $fnd = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $fnd = true;
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            if ($fnd == false) {
                $chg = true;
            }
            $desc = $this->Translate($var['Desc']);
            $values[] = ['ident' => $ident, 'desc' => $desc, 'use' => $use];
        }

        if ($chg == true) {
            $this->UpdateFormField('use_fields', 'values', json_encode($values));
        }
    }

    protected function GetFormElements()
    {
        $formElements = [];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'KEBA KeConnect P30 (UDP)'
        ];

        $s = $this->CheckConfiguration();
        if ($s != '') {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $s
            ];
            $formElements[] = [
                'type'    => 'Label',
            ];
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $items = [];
        $items[] = [
            'type'     => 'ValidationTextBox',
            'name'     => 'host',
            'caption'  => 'IP address of the wallbox',
            'validate' => '^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$',
        ];
        $items[] = [
            'type'     => 'ValidationTextBox',
            'name'     => 'serialnumber',
            'caption'  => 'Serial number (optional)',
        ];

        $items[] = [
            'type'    => 'Label',
            'caption' => 'Update data in standby every X minutes'
        ];
        $items[] = [
            'type'    => 'IntervalBox',
            'name'    => 'standby_update_interval',
            'caption' => 'Minutes'
        ];

        $items[] = [
            'type'    => 'Label',
            'caption' => 'Update data while charging every X seconds'
        ];
        $items[] = [
            'type'    => 'IntervalBox',
            'name'    => 'charging_update_interval',
            'caption' => 'Seconds'
        ];

        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => $items,
            'caption'  => 'Basic configuration',
            'expanded' => false,
        ];

        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $desc = $this->Translate($var['Desc']);
            $values[] = [
                'ident' => $ident,
                'desc'  => $desc
            ];
        }

        $items = [];
        $items[] = [
            'type'     => 'List',
            'name'     => 'use_fields',
            'caption'  => 'available variables',
            'rowCount' => count($values),
            'add'      => false,
            'delete'   => false,
            'columns'  => [
                [
                    'caption' => 'Ident',
                    'name'    => 'ident',
                    'width'   => '200px',
                    'save'    => true
                ],
                [
                    'caption' => 'Description',
                    'name'    => 'desc',
                    'width'   => 'auto'
                ],
                [
                    'caption' => 'use',
                    'name'    => 'use',
                    'width'   => '100px',
                    'edit'    => [
                        'type' => 'CheckBox'
                    ],
                ],
            ],
            'values'   => $values
        ];
        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => $items,
            'caption'  => 'Additional variables',
            'expanded' => false,
            'onClick'  => 'KebaConnect_UpdateFields($id);'
        ];

        $items = [];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'save_history',
            'caption' => 'save charging history entries'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'minimum' => 0,
            'suffix'  => 'days',
            'name'    => 'history_age',
            'caption' => 'maximun age of history entries'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'show_history',
            'caption' => 'show table of charging history'
        ];
        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => $items,
            'caption'  => 'Charging history',
            'expanded' => false,
        ];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'KebaConnect_StandbyUpdate($id);'
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded ' => false,
            'items'     => [
                [
                    'type'    => 'Button',
                    'caption' => 'Re-install variable-profiles',
                    'onClick' => 'KebaConnect_InstallVarProfiles($id, true);'
                ]
            ]
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Test area',
            'expanded ' => false,
            'items'     => [
                [
                    'type'    => 'TestCenter',
                ],
                [
                    'type'    => 'Label',
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Send text to wallbox (max 23 character) - Test of function "KebaConnect_SendDisplayText"',
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'txt',
                            'caption' => 'Text'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Send',
                            'onClick' => 'KebaConnect_SendDisplayText($id, $txt);'
                        ],
                    ],
                ],
            ]
        ];

        $formActions[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Information',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => $this->InstanceInfo($this->InstanceID),
                ],
            ],
        ];

        return $formActions;
    }

    private function GetConnectionID()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $cID = $inst['ConnectionID'];
        return $cID;
    }

    public function GetConfigurationForParent()
    {
        $host = $this->ReadPropertyString('host');

        $r = IPS_GetConfiguration($this->GetConnectionID());
        $this->SendDebug(__FUNCTION__, print_r($r, true), 0);
        $j = [
            'Host'               => $host,
            'Port'               => self::$BroadcastPort,
            'BindPort'           => self::$BroadcastPort,
            'EnableBroadcast'    => true,
            'EnableReuseAddress' => true,
        ];
        $d = json_encode($j);
        $this->SendDebug(__FUNCTION__, print_r($j, true), 0);
        return $d;
    }

    protected function SetStandbyUpdateInterval(int $sec = null)
    {
        if ($sec > 0) {
            $this->SendDebug(__FUNCTION__, 'override interval with sec=' . $sec, 0);
            $msec = $sec * 1000;
        } else {
            $min = $this->ReadPropertyInteger('standby_update_interval');
            $this->SendDebug(__FUNCTION__, 'default interval with min=' . $min, 0);
            $msec = $min > 0 ? $min * 60 * 1000 : 0;
        }
        $this->SetTimerInterval('StandbyUpdate', $msec);
    }

    protected function SetChargingUpdateInterval()
    {
        $chargingState = $this->GetValue('ChargingState');
        if ($chargingState == self::$STATE_CHARGING) {
            $sec = $this->ReadPropertyInteger('charging_update_interval');
            $this->SendDebug(__FUNCTION__, 'default interval with sec=' . $sec, 0);
            $msec = $sec > 0 ? $sec * 1000 : 0;
        } else {
            $this->SendDebug(__FUNCTION__, 'off', 0);
            $msec = 0;
        }
        $this->SetTimerInterval('ChargingUpdate', $msec);
    }

    public function ReceiveData($data)
    {
        $this->SendDebug(__FUNCTION__, 'got broadcast, data="' . $data . '"', 0);
        $jdata = json_decode($data, true);
        if (isset($jdata['ClientIP'])) {
            $clientIP = $jdata['ClientIP'];
            $host = $this->ReadPropertyString('host');
            if ($clientIP != $host) {
                $this->SendDebug(__FUNCTION__, 'ignore broadcast from IP ' . $clientIP, 0);
                return;
            }
        }
        $buffer = $jdata['Buffer'];
        $this->DecodeBroadcast($buffer);
    }

    private function ExecuteCmd(string $cmd)
    {
        $host = $this->ReadPropertyString('host');
        $port = self::$UnicastPort;

        // min 100MS zwischend zwei UDP-Kommandos

        IPS_Sleep(100);

        $fp = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($fp == false) {
            $this->SendDebug(__FUNCTION__, 'socket_create() failed, reason=' . socket_strerror(socket_last_error($fp)), 0);
            return false;
        }
        socket_set_option($fp, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($fp, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($fp, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        if (socket_bind($fp, '0.0.0.0', $port) == false) {
            $this->SendDebug(__FUNCTION__, 'socket_bind() failed, reason=' . socket_strerror(socket_last_error($fp)), 0);
            socket_close($fp);
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'send ' . strlen($cmd) . ' bytes to ' . $host . ':' . $port . ', cmd="' . $cmd . '"', 0);
        if (socket_sendto($fp, $cmd, strlen($cmd), 0, $host, $port) == false) {
            $this->SendDebug(__FUNCTION__, 'socket_sendto() failed, reason=' . socket_strerror(socket_last_error($fp)), 0);
            socket_close($fp);
            return false;
        }
        if (($bytes = socket_recv($fp, $buf, 2048, 0)) == false) {
            $this->SendDebug(__FUNCTION__, 'socket_recv() failed, reason=' . socket_strerror(socket_last_error($fp)), 0);
            socket_close($fp);
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'received ' . $bytes . ' bytes, buf="' . $buf . '"', 0);
        socket_close($fp);

        return $buf;
    }

    public function StandbyUpdate()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            $this->LogMessage('has no active parent instance', KL_WARNING);
            return;
        }

        foreach (['report 1', 'report 2', 'report 3', 'report 100'] as $cmd) {
            $buf = $this->ExecuteCmd($cmd);
            if ($buf != false) {
                $this->DecodeReport($buf);
            }
        }

        $save_history = $this->ReadPropertyBoolean('save_history');
        if ($save_history) {
            $this->GetChargingHistory();
        }

        $this->SetStandbyUpdateInterval();
        $this->SetChargingUpdateInterval();
    }

    private function cmp_entries($a, $b)
    {
        $a_sessionID = $a['Session ID'];
        $b_sessionID = $b['Session ID'];
        return ($a_sessionID > $b_sessionID) ? -1 : 1;
    }

    private function GetChargingHistory()
    {
        $save_history = $this->ReadPropertyBoolean('save_history');
        if ($save_history == false) {
            return;
        }

        $old_entries = false;
        $old_s = $this->GetMediaData('ChargingHistory');
        if ($old_s != false) {
            $old_entries = json_decode((string) $old_s, true);
        }
        if ($old_entries != false) {
            usort($old_entries, ['KeConnectP30udp', 'cmp_entries']);
            $lastSessionID = $old_entries[0]['Session ID'];
        } else {
            $old_entries = [];
            $lastSessionID = 0;
        }

        $this->SendDebug(__FUNCTION__, 'old_entries=' . print_r($old_entries, true), 0);
        $this->SendDebug(__FUNCTION__, 'lastSessionID=' . $lastSessionID, 0);

        $serialnumber = $this->ReadPropertyString('serialnumber');

        $new_entries = [];
        for ($i = 1; $i <= 30; $i++) {
            $cmd = 'report ' . strval(100 + $i);
            $buf = $this->ExecuteCmd($cmd);
            if ($buf == false) {
                continue;
            }
            $jdata = json_decode($buf, true);
            $this->SendDebug(__FUNCTION__, 'entry=' . print_r($jdata, true), 0);

            $sessionID = $this->GetArrayElem($jdata, 'Session ID', '');
            if ($sessionID <= 0) {
                $this->SendDebug(__FUNCTION__, 'all valid reports processed', 0);
                break;
            }

            if ($serialnumber != false) {
                $serial = $this->GetArrayElem($jdata, 'Serial', '');
                if ($serial == '') {
                    $this->SendDebug(__FUNCTION__, 'missing "Serial" in json-data - unable to check', 0);
                } elseif ($serial != $serialnumber) {
                    $this->SendDebug(__FUNCTION__, 'serial number "' . $serial . '" don\'t match', 0);
                    continue;
                }
            }

            $started = 0;
            $s = $this->GetArrayElem($jdata, 'started', '');
            if ($s != false) {
                $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $s, new DateTimeZone('UTC'));
                if ($d == false) {
                    $this->SendDebug(__FUNCTION__, 'field "started": parse failed ' . print_r(DateTime::getLastErrors(), true), 0);
                } else {
                    $started = intval($d->format('U'));
                }
            }

            $ended = 0;
            $s = $this->GetArrayElem($jdata, 'ended', '');
            if ($s != false) {
                $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $s, new DateTimeZone('UTC'));
                if ($d == false) {
                    $this->SendDebug(__FUNCTION__, 'field "ended": parse failed ' . print_r(DateTime::getLastErrors(), true), 0);
                } else {
                    $ended = intval($d->format('U'));
                }
            }

            $tag = $this->GetArrayElem($jdata, 'RFID tag', 0);
            if (preg_match('/^[0]+$/', $tag)) {
                $tag = '';
            }

            $curr_hw = floatval($this->GetArrayElem($jdata, 'Curr HW', 0));
            $curr_hw /= 1000;

            $e_start = floatval($this->GetArrayElem($jdata, 'E start', 0));
            $e_start /= 10000;

            $e_pres = floatval($this->GetArrayElem($jdata, 'E pres', 0));
            $e_pres /= 10000;

            $entry = [
                'Session ID'  => $sessionID,
                'started'     => $started,
                'ended'       => $ended,
                'RFID tag'    => $tag,
                'Curr HW'     => $curr_hw,
                'E start'     => $e_start,
                'E pres'      => $e_pres,
                'reason'      => $jdata['reason'],
            ];
            $new_entries[] = $entry;

            if ($sessionID <= $lastSessionID) {
                $this->SendDebug(__FUNCTION__, 'all new reports processed', 0);
                break;
            }
        }

        $history_age = $this->ReadPropertyInteger('history_age');
        if ($history_age > 0) {
            $reftstamp = time() - ($history_age * 60 * 60 * 24);
        } else {
            $reftstamp = 0;
        }

        foreach ($old_entries as $entry) {
            $fnd = false;
            if ($entry['started'] < $reftstamp) {
                continue;
            }
            foreach ($new_entries as $e) {
                if ($entry['Session ID'] == $e['Session ID']) {
                    $fnd = true;
                    break;
                }
            }
            if ($fnd == false) {
                $new_entries[] = $entry;
            }
        }

        if ($new_entries != []) {
            usort($new_entries, ['KeConnectP30udp', 'cmp_entries']);
            $n = count($new_entries);
            $new_s = json_encode($new_entries);
        } else {
            $n = 0;
            $new_s = '';
        }
        if ($new_s != $old_s) {
            $this->SendDebug(__FUNCTION__, $n . ' entries=' . print_r($new_entries, true), 0);
            $this->SetMediaData('ChargingHistory', $new_s, MEDIATYPE_DOCUMENT, '.dat', false);
        } else {
            $this->SendDebug(__FUNCTION__, 'entries not changed', 0);
        }

        $show_history = $this->ReadPropertyBoolean('show_history');
        if ($show_history) {
            $use_idents = self::$fixedVariables;
            $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
            foreach ($use_fields as $field) {
                $ident = $this->GetArrayElem($field, 'ident', '');
                $use = (bool) $this->GetArrayElem($field, 'use', false);
                if ($use && $ident != false) {
                    $use_idents[] = $ident;
                }
            }
            $use_rfid = in_array('RFID', $use_idents);

            $tbl = '';
            foreach ($new_entries as $entry) {
                $s = date('d.m. H:i:s', $entry['started']);
                $e = date('d.m. H:i:s', $entry['ended']);
                $e_pres = GetValueFormattedEx($this->GetIDForIdent('ChargedEnergy'), $entry['E pres']);
                $tbl .= '<tr>' . PHP_EOL;
                $tbl .= '<td>' . $entry['Session ID'] . '</td>' . PHP_EOL;
                $tbl .= '<td>' . $s . '</td>' . PHP_EOL;
                $tbl .= '<td>' . $e . '</td>' . PHP_EOL;
                $tbl .= '<td style=\'text-align: right\'>' . $e_pres . '</td>' . PHP_EOL;
                if ($use_rfid) {
                    $tbl .= '<td>' . $entry['RFID tag'] . '</td>' . PHP_EOL;
                }
                $tbl .= '</tr>' . PHP_EOL;
            }
            if ($tbl != '') {
                $html = '<style>' . PHP_EOL;
                $html .= 'th, td { padding: 2px 10px; text-align: left; }' . PHP_EOL;
                $html .= '</style>' . PHP_EOL;
                $html .= '<table>' . PHP_EOL;
                $html .= '<tr>' . PHP_EOL;
                $html .= '<th>' . $this->Translate('Session ID') . '</th>' . PHP_EOL;
                $html .= '<th>' . $this->Translate('Started') . '</th>' . PHP_EOL;
                $html .= '<th>' . $this->Translate('Ended') . '</th>' . PHP_EOL;
                $html .= '<th style=\'text-align: right\'>' . $this->Translate('Energy') . '</th>' . PHP_EOL;
                if ($use_rfid) {
                    $html .= '<th>' . $this->Translate('RFID card') . '</th>' . PHP_EOL;
                }
                $html .= '</tr>' . PHP_EOL;
                $html .= $tbl;
                $html .= '</table>' . PHP_EOL;
            } else {
                $html = $this->Translate('there are no charging sessions present');
            }

            if ($this->GetValue('History') != $html) {
                $this->SetValue('History', $html);
            }
        }
    }

    public function ChargingUpdate()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            $this->LogMessage('has no active parent instance', KL_WARNING);
            return;
        }

        $buf = $this->ExecuteCmd('report 3', 'report 100');
        if ($buf != false) {
            $this->DecodeReport($buf);
        }
    }

    private function DecodeReport(string $data)
    {
        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);
        if ($jdata == false) {
            $this->SendDebug(__FUNCTION__, 'invalid json-data, data=' . $data, 0);
            return;
        }
        if (isset($jdata['ID']) == false) {
            $this->SendDebug(__FUNCTION__, 'missing "ID" in json-data, data=' . $data, 0);
            return;
        }

        $report_id = intval($jdata['ID']);
        $this->SendDebug(__FUNCTION__, 'report ' . $report_id, 0);

        $serialnumber = $this->ReadPropertyString('serialnumber');
        if ($serialnumber != false) {
            $serial = $this->GetArrayElem($jdata, 'Serial', '');
            if ($serial == '') {
                $this->SendDebug(__FUNCTION__, 'missing "Serial" in json-data - ignore check', 0);
            } elseif ($serial != $serialnumber) {
                $this->SendDebug(__FUNCTION__, 'serial number "' . $serial . '" don\'t match', 0);
                return;
            }
        }

        $use_idents = self::$fixedVariables;
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        foreach ($use_fields as $field) {
            $ident = $this->GetArrayElem($field, 'ident', '');
            $use = (bool) $this->GetArrayElem($field, 'use', false);
            if ($use && $ident != false) {
                $use_idents[] = $ident;
            }
        }

        $now = time();
        $is_changed = false;

        if ($report_id == 1) {
            $product = $this->GetArrayElem($jdata, 'Product', '');
            $serial = $this->GetArrayElem($jdata, 'Serial', '');
            $this->SetSummary($product . ' (#' . $serial . ')');

            if (in_array('ProductType', $use_idents)) {
                $this->SaveValue('ProductType', $product, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ProductType" to "' . $product . '" from field "Product"', 0);
            }
            if (in_array('SerialNumber', $use_idents)) {
                $this->SaveValue('SerialNumber', $serial, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "SerialNumber" to "' . $serial . '" from field "Serial"', 0);
            }
            if (in_array('FirmwareVersion', $use_idents)) {
                $firmware = $this->GetArrayElem($jdata, 'Firmware', '');
                $this->SaveValue('FirmwareVersion', $firmware, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "FirmwareVersion" to "' . $firmware . '" from field "Firmware"', 0);
            }
            if (in_array('LastBoot', $use_idents)) {
                $sec = intval($this->GetArrayElem($jdata, 'Sec', 0));
                $ts = $now - $sec;
                $this->SaveValue('LastBoot', $ts, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "LastBoot" to ' . date('d.m.Y H:i:s', $ts) . ' from field "Sec"', 0);
            }
        }

        if ($report_id == 2) {
            if (in_array('ChargingState', $use_idents)) {
                $charging_state = $this->GetArrayElem($jdata, 'State', 0);
                $this->SaveValue('ChargingState', $charging_state, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ChargingState" to ' . $charging_state . ' from field "State"', 0);
            }
            if (in_array('CableState', $use_idents)) {
                $cable_state = $this->GetArrayElem($jdata, 'Plug', 0);
                $this->SaveValue('CableState', $cable_state, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "CableState" to ' . $cable_state . ' from field "CableState"', 0);
            }
            if (in_array('EnableCharging', $use_idents)) {
                $enable_sys = boolval($this->GetArrayElem($jdata, 'Enable sys', false));
                $this->SaveValue('EnableCharging', $enable_sys, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "EnableCharging" to ' . $enable_sys . ' from field "Enable sys"', 0);
            }

            $error1 = $this->GetArrayElem($jdata, 'Error 1', 0);
            $error2 = $this->GetArrayElem($jdata, 'Error 2', 0);
            if ($error1 > 0 && $error2 > 0) {
                $this->LogMessage('got both error: Error 1=' . $error1 . ', Error 2=' . $error, KL_WARNING);
            }
            if ($error2) {
                $error = $error2;
                $fld = 'Error 2';
            } else {
                $error = $error1;
                $fld = 'Error 1';
            }
            if (in_array('ErrorCode', $use_idents)) {
                $this->SaveValue('ErrorCode', $error, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ErrorCode" to ' . $error . ' from field "' . $fld . '"', 0);
            }
            if (in_array('ErrorText', $use_idents)) {
                $this->SetValue('ErrorText', $this->ErrorCode2Text($error));
            }

            if (in_array('MaxChargingCurrent', $use_idents)) {
                $max_curr = floatval($this->GetArrayElem($jdata, 'Max curr', 0));
                $max_curr /= 1000;
                $this->SaveValue('MaxChargingCurrent', $max_curr, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "MaxChargingCurrent" to ' . $max_curr . ' from field "Max curr"', 0);
            }
            if (in_array('MaxSupportedCurrent', $use_idents)) {
                $curr_hw = floatval($this->GetArrayElem($jdata, 'Curr HW', 0));
                $curr_hw /= 1000;
                $this->SaveValue('MaxSupportedCurrent', $curr_hw, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "MaxSupportedCurrent" to ' . $curr_hw . ' from field "Curr HW"', 0);
            }
            if (in_array('ChargingEnergyLimit', $use_idents)) {
                $setenergy = floatval($this->GetArrayElem($jdata, 'Setenergy', 0));
                $setenergy /= 10000;
                $this->SaveValue('ChargingEnergyLimit', $setenergy, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ChargingEnergyLimit" to ' . $setenergy . ' from field "Setenergy"', 0);
            }

            $b = $this->checkAction('SwitchEnableCharging', false);
            $this->MaintainAction('EnableCharging', $b);

            $b = $this->checkAction('SetMaxChargingCurrent', false);
            $this->MaintainAction('MaxChargingCurrent', $b);

            $b = $this->checkAction('SetChargingEnergyLimit', false);
            $this->MaintainAction('ChargingEnergyLimit', $b);

            $b = $this->checkAction('UnlockPlug', false);
            $this->MaintainAction('UnlockPlug', $b);
        }

        if ($report_id == 3) {
            if (in_array('CurrentPhase1', $use_idents)) {
                $curr1 = floatval($this->GetArrayElem($jdata, 'I1', 0));
                $curr1 /= 1000;
                $this->SaveValue('CurrentPhase1', $curr1, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "CurrentPhase1" to ' . $curr1 . ' from field "I1"', 0);
            }
            if (in_array('CurrentPhase2', $use_idents)) {
                $curr2 = floatval($this->GetArrayElem($jdata, 'I2', 0));
                $curr2 /= 1000;
                $this->SaveValue('CurrentPhase2', $curr2, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "CurrentPhase2" to ' . $curr2 . ' from field "I2"', 0);
            }
            if (in_array('CurrentPhase3', $use_idents)) {
                $curr3 = floatval($this->GetArrayElem($jdata, 'I3', 0));
                $curr3 /= 1000;
                $this->SaveValue('CurrentPhase3', $curr3, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "CurrentPhase3" to ' . $curr3 . ' from field "I3"', 0);
            }
            if (in_array('VoltagePhase1', $use_idents)) {
                $volt1 = floatval($this->GetArrayElem($jdata, 'U1', 0));
                $this->SaveValue('VoltagePhase1', $volt1, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "VoltagePhase1" to ' . $volt1 . ' from field "U1"', 0);
            }
            if (in_array('VoltagePhase2', $use_idents)) {
                $volt2 = floatval($this->GetArrayElem($jdata, 'U2', 0));
                $this->SaveValue('VoltagePhase2', $volt2, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "VoltagePhase2" to ' . $volt2 . ' from field "U2"', 0);
            }
            if (in_array('VoltagePhase3', $use_idents)) {
                $volt3 = floatval($this->GetArrayElem($jdata, 'U3', 0));
                $this->SaveValue('VoltagePhase3', $volt3, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "VoltagePhase3" to ' . $volt3 . ' from field "U3"', 0);
            }
            if (in_array('ActivePower', $use_idents)) {
                $power = floatval($this->GetArrayElem($jdata, 'P', 0));
                $power /= 1000000;
                $this->SaveValue('ActivePower', $power, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ActivePower" to ' . $power . ' from field "P"', 0);
            }
            if (in_array('PowerFactor', $use_idents)) {
                $pf = floatval($this->GetArrayElem($jdata, 'PF', 0));
                $pf /= 10;
                $this->SaveValue('PowerFactor', $pf, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "PowerFactor" to ' . $pf . ' from field "PF"', 0);
            }
            if (in_array('ChargedEnergy', $use_idents)) {
                $e_pres = floatval($this->GetArrayElem($jdata, 'E pres', 0));
                $e_pres /= 10000;
                $this->SaveValue('ChargedEnergy', $e_pres, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ChargedEnergy" to ' . $e_pres . ' from field "E pres"', 0);
            }
            if (in_array('TotalEnergy', $use_idents)) {
                $e_total = floatval($this->GetArrayElem($jdata, 'E total', 0));
                $e_total /= 10000;
                $this->SaveValue('TotalEnergy', $e_total, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "TotalEnergy" to ' . $e_total . ' from field "E total"', 0);
            }
        }

        if ($report_id == 100) {
            $session_id = $this->GetArrayElem($jdata, 'Session ID', 0);
            if ($session_id <= 0) {
                $this->SendDebug(__FUNCTION__, 'ignore zero "Session ID"', 0);
                return;
            }

            if (in_array('RFID', $use_idents)) {
                $tag = $this->GetArrayElem($jdata, 'RFID tag', 0);
                if (preg_match('/^[0]+$/', $tag)) {
                    $tag = '';
                }
                $this->SaveValue('RFID', $tag, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "RFID" to "' . $tag . '" from field "RFID tag"', 0);
            }

            if (in_array('ChargingStarted', $use_idents)) {
                $ts = 0;
                $s = $this->GetArrayElem($jdata, 'started', '');
                if ($s != false) {
                    $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $s, new DateTimeZone('UTC'));
                    if ($d == false) {
                        $this->SendDebug(__FUNCTION__, 'field "started": parse failed ' . print_r(DateTime::getLastErrors(), true), 0);
                    } else {
                        $ts = intval($d->format('U'));
                    }
                }
                $this->SaveValue('ChargingStarted', $ts, $is_changed);
                $s = $ts ? date('d.m.Y H:i:s', $ts) : '-';
                $this->SendDebug(__FUNCTION__, 'set variable "ChargingStarted" to ' . $s . ' from field "started"', 0);
            }
            if (in_array('ChargingEnded', $use_idents)) {
                $ts = 0;
                $s = $this->GetArrayElem($jdata, 'ended', '');
                if ($s != false) {
                    $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $s, new DateTimeZone('UTC'));
                    if ($d == false) {
                        $this->SendDebug(__FUNCTION__, 'field "ended": parse failed ' . print_r(DateTime::getLastErrors(), true), 0);
                    } else {
                        $ts = intval($d->format('U'));
                    }
                }
                $this->SaveValue('ChargingEnded', $ts, $is_changed);
                $s = $ts ? date('d.m.Y H:i:s', $ts) : '-';
                $this->SendDebug(__FUNCTION__, 'set variable "ChargingEnded" to ' . $s . ' from field "ended"', 0);
            }
        }

        $this->SetValue('LastUpdate', $now);
        if ($is_changed) {
            $this->SetValue('LastChange', $now);
        }
    }

    private function DecodeBroadcast(string $data)
    {
        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $use_idents = self::$fixedVariables;
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        foreach ($use_fields as $field) {
            $ident = $this->GetArrayElem($field, 'ident', '');
            $use = (bool) $this->GetArrayElem($field, 'use', false);
            if ($use && $ident != false) {
                $use_idents[] = $ident;
            }
        }

        $now = time();
        $is_changed = false;

        $reload_reports = false;

        if (in_array('ChargingState', $use_idents)) {
            $charging_state = $this->GetArrayElem($jdata, 'State', 0);
            $chg = false;
            $this->SaveValue('ChargingState', $charging_state, $chg);
            if ($chg) {
                $is_changed = true;
                $reload_reports = true;
            }
            $this->SendDebug(__FUNCTION__, 'set variable "ChargingState" to ' . $charging_state . ' from field "State"', 0);
        }
        if (in_array('CableState', $use_idents)) {
            $cable_state = $this->GetArrayElem($jdata, 'Plug', 0);
            $chg = false;
            $this->SaveValue('CableState', $cable_state, $chg);
            if ($chg) {
                $is_changed = true;
                $reload_reports = true;
            }
            $this->SendDebug(__FUNCTION__, 'set variable "CableState" to ' . $cable_state . ' from field "CableState"', 0);
        }
        if (in_array('MaxChargingCurrent', $use_idents)) {
            $max_curr = floatval($this->GetArrayElem($jdata, 'Max curr', 0));
            $max_curr /= 1000;
            $this->SaveValue('MaxChargingCurrent', $max_curr, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set variable "MaxChargingCurrent" to ' . $max_curr . ' from field "Max curr"', 0);
        }
        if (in_array('ChargedEnergy', $use_idents)) {
            $e_pres = floatval($this->GetArrayElem($jdata, 'E pres', 0));
            $e_pres /= 10000;
            $this->SaveValue('ChargedEnergy', $e_pres, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set variable "ChargedEnergy" to ' . $e_pres . ' from field "E pres"', 0);
        }
        if (in_array('EnableCharging', $use_idents)) {
            $enable_sys = boolval($this->GetArrayElem($jdata, 'Enable sys', false));
            $this->SaveValue('EnableCharging', $enable_sys, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set variable "EnableCharging" to ' . $enable_sys . ' from field "Enable sys"', 0);
        }

        if ($is_changed) {
            $this->SetValue('LastChange', $now);
        }

        if ($reload_reports) {
            $this->SetStandbyUpdateInterval(1);
        }
        $this->SetChargingUpdateInterval();
    }

    private function checkAction($func, $verbose)
    {
        $enabled = false;

        switch ($func) {
            case 'SwitchEnableCharging':
                /*
                $chargingState = $this->GetValue('ChargingState');
                switch ($chargingState) {
                    case self::$STATE_READY:
                    case self::$STATE_READY:
                    case self::$STATE_CHARGING:
                    case self::$STATE_SUSPENDED:
                        $enabled = true;
                        break;
                    default:
                        if ($verbose) {
                            $this->SendDebug(__FUNCTION__, 'wrong ChargingState ' . $chargingState, 0);
                        }
                        break;
                }
                 */
                $enabled = true;
                break;
            case 'SetMaxChargingCurrent':
                $enabled = true;
                break;
            case 'SetChargingEnergyLimit':
                $enabled = true;
                break;
            case 'UnlockPlug':
                $cableState = $this->GetValue('CableState');
                switch ($cableState) {
                    case self::$CABLE_LOCKED_IN_VEHICLE:
                        $enabled = true;
                        break;
                    default:
                        if ($verbose) {
                            $this->SendDebug(__FUNCTION__, 'wrong CableState ' . $cableState, 0);
                        }
                        break;
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unsupported action "' . $func . '"', 0);
                break;
        }

        if ($verbose) {
            $this->SendDebug(__FUNCTION__, 'action "' . $func . '" is ' . ($enabled ? 'enabled' : 'disabled'), 0);
        }
        return $enabled;
    }

    private function CallAction($cmd)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            $this->LogMessage('has no active parent instance', KL_WARNING);
            return;
        }

        $r = $this->ExecuteCmd($cmd);
        $this->SendDebug(__FUNCTION__, 'cmd=' . $cmd . ' => ' . $r, 0);
        return $r == "TCH-OK :done\n";
    }

    public function SendDisplayText(string $txt)
    {
        // Text shown on the display. Maximum 23 ASCII characters can be used. 0 .. 23 characters
        //   ~ == Σ
        //   $ == blank
        //   , == comma
        $s = substr(str_replace([' '], '$', $txt), 0, 23);
        $this->SendDebug(__FUNCTION__, 'text="' . $txt . '" => ˝' . $s . '"', 0);
        $cmd = 'display 0 0 0 0 ' . $s;
        $this->CallAction($cmd);
    }

    public function UnlockPlug()
    {
        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        if ($this->GetValue('EnableCharging') == true) {
            $this->SendDebug(__FUNCTION__, 'force disable charging', 0);
            $this->CallAction('ena 0');
            IPS_Sleep(250);
        }
        return $this->CallAction('unlock');
    }

    public function SwitchEnableCharging(bool $mode)
    {
        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // enable charging
        // 0 = Disabled; is indicated with a blue flashing LED
        // 1 = Enabled
        $cmd = 'ena ' . ($mode ? '1' : '0');
        return $this->CallAction($cmd);
    }

    public function SetMaxChargingCurrent(float $current)
    {
        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // maximum allowed loading current in milliampere
        // range: 6000mA ... 63000mA or 'MaxSupportedCurrent'
        $min = 6.0;
        if ($current < $min) {
            $this->SendDebug(__FUNCTION__, 'value ist below minimum (6A)', 0);
            $current = $min;
        }
        $max = $this->GetValue('MaxSupportedCurrent');
        if ($current > $max) {
            $this->SendDebug(__FUNCTION__, 'value ist above supported maximum (' . $max . 'A)', 0);
            $current = $max;
        }
        $c = intval($current * 1000);
        $cmd = 'curr ' . $c;
        return $this->CallAction($cmd);
    }

    public function SetChargingEnergyLimit(float $energy)
    {
        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // Charging energy limit in 0,1Wh
        $e = $energy * 10000;
        $cmd = 'setenergy ' . $e;
        return $this->CallAction($cmd);
    }

    public function RequestAction($Ident, $Value)
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            return;
        }

        $r = false;
        switch ($Ident) {
            case 'EnableCharging':
                $r = $this->SwitchEnableCharging((bool) $Value);
                $this->SendDebug(__FUNCTION__, $Ident . '=' . $this->bool2str($Value) . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetValue($Ident, $Value);
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'MaxChargingCurrent':
                $r = $this->SetMaxChargingCurrent((float) $Value);
                $this->SendDebug(__FUNCTION__, $Ident . '=' . $Value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetValue($Ident, $Value);
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'ChargingEnergyLimit':
                $r = $this->SetChargingEnergyLimit((float) $Value);
                $this->SendDebug(__FUNCTION__, $Ident . '=' . $Value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetValue($Ident, $Value);
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'UnlockPlug':
                $r = $this->UnlockPlug();
                $this->SendDebug(__FUNCTION__, $Ident . '=' . $Value . ' => ret=' . $this->bool2str($r), 0);
                $this->SetValue($Ident, false); // Trick, damit der Wert immer "false" bleibt
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $Ident, 0);
                break;
        }
    }

    private function ErrorCode2Text($code)
    {
        $code2text = [
            0    => 'no error',
            1    => 'Plug was disconnected during charging',
            2    => 'Plug was not recognized',
            3    => 'Temperature cut-off',
            4    => 'Plug could not be locked',
            5    => 'invalid consumer detected',
            8    => 'Plug at charging station has invalid state',
            4003 => 'Overcurrent detected in vehicle',
            8005 => 'Fault current detected in vehicle',
        ];

        $s = '';
        foreach ($code2text as $c => $t) {
            if ($c == $code) {
                $s = $this->Translate($t);
                break;
            }
        }
        if ($s == '') {
            $s = sprintf($this->Translate('Error 0x%05x'), $code);
        }
        return $s;
    }

    public function GetHistory()
    {
        return $this->GetMediaData('ChargingHistory');
    }
}
