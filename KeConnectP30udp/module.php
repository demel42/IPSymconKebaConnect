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
        'ErrorCode',

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
            'Ident'           => 'RFID',
            'Desc'            => 'RFID card',
            'VariableType'    => VARIABLETYPE_STRING,
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
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyInteger('standby_update_interval', '5');
        $this->RegisterPropertyInteger('charging_update_interval', '1');

        $this->RegisterTimer('StandbyUpdate', 0, 'KebaConnect_StandbyUpdate(' . $this->InstanceID . ');');
        $this->RegisterTimer('ChargingUpdate', 0, 'KebaConnect_ChargingUpdate(' . $this->InstanceID . ');');

        $this->InstallVarProfiles(false);

        $this->RequireParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;
        $this->MaintainVariable('ChargingState', $this->Translate('Charging state'), VARIABLETYPE_INTEGER, 'KebaConnect.ChargingState', $vpos++, true);
        $this->MaintainVariable('CableState', $this->Translate('Cable state'), VARIABLETYPE_INTEGER, 'KebaConnect.CableState', $vpos++, true);
        $this->MaintainVariable('ErrorCode', $this->Translate('Error code'), VARIABLETYPE_INTEGER, 'KebaConnect.Error', $vpos++, true);
        $this->MaintainVariable('ErrorText', $this->Translate('Error text'), VARIABLETYPE_STRING, '', $vpos++, true);

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

        $vpos = 90;
        $this->MaintainVariable('LastChange', $this->Translate('Last change'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('StandbyUpdate', 0);
            $this->SetTimerInterval('ChargingUpdate', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $host = $this->ReadPropertyString('host');
        if ($host == '') {
            $this->SetStatus(self::$IS_INVALIDCONFIG);
            return;
        }

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

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'     => 'ValidationTextBox',
            'name'     => 'host',
            'caption'  => 'IP address of the wallbox',
            'validate' => '^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$',
        ];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Update data in standby every X minutes'
        ];
        $formElements[] = [
            'type'    => 'IntervalBox',
            'name'    => 'standby_update_interval',
            'caption' => 'Minutes'
        ];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Update data while charging every X seconds'
        ];
        $formElements[] = [
            'type'    => 'IntervalBox',
            'name'    => 'charging_update_interval',
            'caption' => 'Seconds'
        ];

        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $desc = $this->Translate($var['Desc']);
            $values[] = [
                'ident' => $ident,
                'desc'  => $desc
            ];
        }

        $columns = [];
        $columns[] = [
            'caption' => 'Ident',
            'name'    => 'ident',
            'width'   => '200px',
            'save'    => true
        ];
        $columns[] = [
            'caption' => 'Description',
            'name'    => 'desc',
            'width'   => 'auto'
        ];
        $columns[] = [
            'caption' => 'use',
            'name'    => 'use',
            'width'   => '100px',
            'edit'    => [
                'type' => 'CheckBox'
            ]
        ];

        $items = [];

        $items[] = [
            'type'     => 'List',
            'name'     => 'use_fields',
            'caption'  => 'available variables',
            'rowCount' => count($values),
            'add'      => false,
            'delete'   => false,
            'columns'  => $columns,
            'values'   => $values
        ];

        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => $items,
            'caption'  => 'Additional variables',
            'expanded' => false,
            'onClick'  => 'KebaConnect_UpdateFields($id);'
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
            //'BindIP'             => '0.0.0.0',
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
        $state = $this->GetValue('ChargingState');
        if ($state == self::$STATE_CHARGING) {
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
            }
        }
        $buffer = $jdata['Buffer'];
        $this->DecodeBroadcast($buffer);
    }

    private function SendData(string $cmd)
    {
        $r = IPS_GetConfiguration($this->GetConnectionID());
        $cfg = json_decode($r, true);

        $data = [
            'DataID'     => '{8E4D9B23-E0F2-1E05-41D8-C21EA53B8706}',
            'Buffer'     => utf8_encode($cmd),
            'ClientIP'   => $cfg['Host'],
            'ClientPort' => self::$UnicastPort,
            'Broadcast'  => false,
        ];
        $jdata = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'request data ' . print_r($jdata, true), 0);
        $this->SendDataToParent($jdata);
    }

    private function ExecuteCmd(string $cmd)
    {
        $host = $this->ReadPropertyString('host');
        $port = self::$UnicastPort;

        $fp = stream_socket_client("udp://$host:$port", $errno, $errstr);
        if (!$fp) {
            $this->SendDebug(__FUNCTION__, 'stream_socket_client("udp://' . $host . ':' . $port . '") failed, errno=' . $errno, 0);
            return false;
        }
        stream_set_timeout($fp, 5);
        fwrite($fp, $cmd);
        $info = stream_get_meta_data($fp);
        fclose($fp);
        if ($info['timed_out']) {
            $this->SendDebug(__FUNCTION__, 'send cmd "' . $cmd . '" timeout', 0);
            return false;
        }
        $this->SendDebug(__FUNCTION__, 'send cmd "' . $cmd . '"', 0);

        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            $this->SendDebug(__FUNCTION__, 'socket_create() failed', 0);
            return false;
        }
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec'=>5, 'usec'=>0]);
        if (!socket_bind($socket, '0.0.0.0', $port)) {
            $this->SendDebug(__FUNCTION__, 'socket_bind() failed', 0);
            return false;
        }
        if (($bytes = socket_recv($socket, $buf, 2048, MSG_WAITALL)) == false) {
            $this->SendDebug(__FUNCTION__, 'socket_recv() failed, reason=' . socket_strerror(socket_last_error($socket)), 0);
            $buf = false;
        } else {
            $this->SendDebug(__FUNCTION__, 'socket_recv(): ' . $bytes . ' bytes, buf="' . $buf . '"', 0);
        }
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

        foreach (['report 1', 'report 2', 'report 3'] as $cmd) {
            $buf = $this->ExecuteCmd($cmd);
            if ($buf != false) {
                $this->DecodeReport($buf);
            }
        }
        $this->SetStandbyUpdateInterval();
        $this->SetChargingUpdateInterval();
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

        $buf = $this->ExecuteCmd('report 3');
        if ($buf != false) {
            $this->DecodeReport($buf);
        }
    }

    private function DecodeReport(string $data)
    {
        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);

        $this->SendDebug(__FUNCTION__, 'report ' . $jdata['ID'], 0);

        $now = time();
        $is_changed = false;

        foreach ($jdata as $var => $val) {
            $fnd = true;
            $ign = false;
            $use = false;
            switch ($jdata['ID']) {
                case '1':
                    switch ($var) {
                        case 'ID':
                            $ign = true;
                            break;
                        case 'Product':
                            $ident = 'ProductType';
                            break;
                        case 'Serial':
                            $ident = 'SerialNumber';
                            break;
                        case 'Firmware':
                            $ident = 'FirmwareVersion';
                            break;
                        case 'Sec':
                            $ident = 'LastBoot';
                            break;
                        default:
                            $fnd = false;
                            break;
                    }
                    break;
                case '2':
                    switch ($var) {
                        case 'ID':
                            $ign = true;
                            break;
                        case 'State':
                            $ident = 'ChargingState';
                            break;
                        case 'Plug':
                            $ident = 'CableState';
                            break;
                        case 'Enable sys':
                            $ident = 'EnableCharging';
                            break;
                        case 'Error 1':
                        case 'Error 2':
                            $ign = true;
                            $use = true;
                            break;
                        case 'Max curr':
                            $ident = 'MaxChargingCurrent';
                            break;
                        case 'Curr HW':
                            $ident = 'MaxSupportedCurrent';
                            break;
                        case 'Setenergy':
                            $ident = 'ChargingEnergyLimit';
                            break;
                        case 'Serial':
                        case 'Sec':
                            $ign = true;
                            break;
                        default:
                            $fnd = false;
                            break;
                    }
                    break;
                case '3':
                    switch ($var) {
                        case 'ID':
                            $ign = true;
                            break;
                        case 'I1':
                            $ident = 'CurrentPhase1';
                            break;
                        case 'I2':
                            $ident = 'CurrentPhase2';
                            break;
                        case 'I3':
                            $ident = 'CurrentPhase3';
                            break;
                        case 'U1':
                            $ident = 'VoltagePhase1';
                            break;
                        case 'U2':
                            $ident = 'VoltagePhase2';
                            break;
                        case 'U3':
                            $ident = 'VoltagePhase3';
                            break;
                        case 'P':
                            $ident = 'ActivePower';
                            break;
                        case 'PF':
                            $ident = 'PowerFactor';
                            break;
                        case 'E pres':
                            $ident = 'ChargedEnergy';
                            break;
                        case 'E total':
                            $ident = 'TotalEnergy';
                            break;
                        case 'Serial':
                        case 'Sec':
                            $ign = true;
                            break;
                        default:
                            $fnd = false;
                            break;
                    }
                }
            if ($ign) {
                continue;
            }
            if ($fnd == false) {
                $this->SendDebug(__FUNCTION__, 'unused field ' . $var . '="' . $val . '"', 0);
                continue;
            }
            if ($use == false) {
                $use = in_array($ident, self::$fixedVariables);
            }
            if ($use == false) {
                foreach ($use_fields as $field) {
                    if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                        $use = (bool) $this->GetArrayElem($field, 'use', false);
                        break;
                    }
                }
            }
            if ($use) {
                switch ($ident) {
                        case 'LastBoot':
                            $val = $now - $val;
                            break;
                        case 'PowerFactor':
                            $val = floatval($val) / 10;
                            break;
                        case 'CurrentPhase1':
                        case 'CurrentPhase2':
                        case 'CurrentPhase3':
                        case 'MaxChargingCurrent':
                        case 'MaxSupportedCurrent':
                            $val = floatval($val) / 1000;
                            break;
                        case 'TotalEnergy':
                        case 'ChargedEnergy':
                            $val = floatval($val) / 10000;
                            break;
                        case 'ActivePower':
                            $val = floatval($val) / 1000000;
                            break;
                        default:
                            break;
                    }
                $this->SendDebug(__FUNCTION__, 'set variable ' . $ident . ' to "' . $val . '" from field "' . $var . '"', 0);
                $this->SaveValue($ident, $val, $is_changed);
            } else {
                $this->SendDebug(__FUNCTION__, 'ignore field ' . $var . '="' . $val . '"', 0);
            }
        }

        switch ($jdata['ID']) {
            case '1':
                $product = $this->GetArrayElem($jdata, 'Product', '');
                $serial = $this->GetArrayElem($jdata, 'Serial', '');
                $s = $product . ' (#' . $serial . ')';
                $this->SetSummary($s);
                break;
            case '2':
                $ident = 'ErrorCode';
                $error1 = $this->GetArrayElem($jdata, 'Error 1', 0);
                $error2 = $this->GetArrayElem($jdata, 'Error 2', 0);
                if ($error1 > 0 && $error2 > 0) {
                    $this->LogMessage('got both error: Error 1=' . $error1 . ', Error 2=' . $error, KL_WARNING);
                }
                if ($error2 > 0) {
                    $var = 'Error 2';
                    $val = $error2;
                } else {
                    $var = 'Error 1';
                    $val = $error2;
                }
                $this->SendDebug(__FUNCTION__, 'set variable ' . $ident . ' to "' . $val . '" from field "' . $var . '"', 0);
                $this->SaveValue($ident, $val, $is_changed);
                $this->SetValue('ErrorText', $this->ErrorCode2Text($val));

                $b = $this->checkAction('SwitchEnableCharging', false);
                $this->MaintainAction('EnableCharging', $b);

                $b = $this->checkAction('SetMaxChargingCurrent', false);
                $this->MaintainAction('MaxChargingCurrent', $b);

                $b = $this->checkAction('SetChargingEnergyLimit', false);
                $this->MaintainAction('ChargingEnergyLimit', $b);

                $cable = $this->GetValue('CableState');
                $b = ($cable == self::$CABLE_LOCKED_IN_VEHICLE);
                $this->SetValue('UnlockPlug', $b);
                $b = $this->checkAction('UnlockPlug', false);
                $this->MaintainAction('UnlockPlug', $b);
                break;
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

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);

        $now = time();
        $is_changed = false;

        $reload_reports = false;
        foreach ($jdata as $var => $val) {
            $fnd = true;
            $ign = false;
            $use = false;
            switch ($var) {
                case 'State':
                    $ident = 'ChargingState';
                    break;
                case 'Plug':
                    $ident = 'CableState';
                    break;
                case 'Max curr':
                    $ident = 'MaxChargingCurrent';
                    break;
                case 'E pres':
                    $ident = 'ChargedEnergy';
                    break;
                case 'Enable sys':
                    $ident = 'EnableCharging';
                    break;
                default:
                    $fnd = false;
                    break;
            }
            if ($ign) {
                continue;
            }
            if ($fnd == false) {
                $this->SendDebug(__FUNCTION__, 'unused field ' . $var . '="' . $val . '"', 0);
                continue;
            }
            if ($use == false) {
                $use = in_array($ident, self::$fixedVariables);
            }
            if ($use == false) {
                foreach ($use_fields as $field) {
                    if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                        $use = (bool) $this->GetArrayElem($field, 'use', false);
                        break;
                    }
                }
            }
            if ($use) {
                switch ($ident) {
                        case 'ChargingState':
                        case 'CableState':
                            if ($this->GetValue($ident) != $val) {
                                $reload_reports = true;
                            }
                            break;
                        case 'MaxChargingCurrent':
                            $val = floatval($val) / 1000;
                            break;
                        case 'ChargedEnergy':
                            $val = floatval($val) / 10000;
                            break;
                        case 'EnableCharging':
                            $val = boolval($val);
                            break;
                        default:
                            break;
                    }
                $this->SendDebug(__FUNCTION__, 'set variable ' . $ident . ' to "' . $val . '" from field "' . $var . '"', 0);
                $this->SaveValue($ident, $val, $is_changed);
            } else {
                $this->SendDebug(__FUNCTION__, 'ignore field ' . $var . '="' . $val . '"', 0);
            }
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
                $state = $this->GetValue('ChargingState');
                switch ($state) {
                    case self::$STATE_READY:
                    case self::$STATE_READY:
                    case self::$STATE_CHARGING:
                    case self::$STATE_SUSPENDED:
                        $enabled = true;
                        break;
                    default:
                        if ($verbose) {
                            $this->SendDebug(__FUNCTION__, 'wrong ChargingState ' . $state, 0);
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
                $cable = $this->GetValue('CableState');
                switch ($cable) {
                    case self::$CABLE_LOCKED_IN_VEHICLE:
                        $enabled = true;
                        break;
                    default:
                        if ($verbose) {
                            $this->SendDebug(__FUNCTION__, 'wrong CableState ' . $cable, 0);
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

        // enable state
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
}
