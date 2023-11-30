<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class KeConnectP30udp extends IPSModule
{
    use KebaConnect\StubsCommonLib;
    use KebaConnectLocalLib;

    private static $UnicastPort = 7090;
    private static $BroadcastPort = 7092;

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
            'Ident'           => 'ComBackend',
            'Desc'            => 'Communication backend',
            'VariableType'    => VARIABLETYPE_BOOLEAN,
            'VariableProfile' => 'KebaConnect.ComBackend',
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

        [
            'Ident'           => 'PhaseSwitchSource',
            'Desc'            => 'Source for phase switching',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.PhaseSwitchSource',
        ],
        [
            'Ident'           => 'PhaseSwitch',
            'Desc'            => 'Status of phase switching',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.PhaseSwitch',
        ],
    ];

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyBoolean('log_no_parent', true);

        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyString('serialnumber', '');
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyBoolean('save_history', false);
        $this->RegisterPropertyBoolean('show_history', false);
        $this->RegisterPropertyInteger('history_age', 90);
        $this->RegisterPropertyBoolean('save_per_rfid', false);

        $this->RegisterPropertyBoolean('phase_switching', false);
        $this->RegisterPropertyBoolean('with_surplus_control', false);

        $this->RegisterPropertyInteger('standby_update_interval', '300');
        $this->RegisterPropertyInteger('charging_update_interval', '1');

        $this->RegisterAttributeString('Product', '');
        $this->RegisterAttributeString('Firmware', '');

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RequireParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');

        $this->RegisterTimer('StandbyUpdate', 0, $this->GetModulePrefix() . '_StandbyUpdate(' . $this->InstanceID . ');');
        $this->RegisterTimer('ChargingUpdate', 0, $this->GetModulePrefix() . '_ChargingUpdate(' . $this->InstanceID . ');');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function CheckModuleConfiguration()
    {
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

        $save_per_rfid = $this->ReadPropertyBoolean('save_per_rfid');
        if ($save_per_rfid && $save_history == false) {
            $this->SendDebug(__FUNCTION__, '"save_per_rfid" needs "save_history"', 0);
            $r[] = $this->Translate('to save consumption per RFID, history must be saved');
        }

        $phase_switching = $this->ReadPropertyBoolean('phase_switching');
        if ($phase_switching) {
            $series = $this->ExtractSeries();
            if (in_array($series, ['C', 'X']) == false) {
                $this->SendDebug(__FUNCTION__, '"phase_switching" is only supported by series C and X', 0);
                $r[] = $this->Translate('phase switching is only supported by series C and X');
            }
            $version = $this->ExtractVersion();
            if ($this->version2num($version) < $this->version2num('3.10.51')) {
                $this->SendDebug(__FUNCTION__, '"phase_switching" is only supported from version 3.10.51', 0);
                $r[] = $this->Translate('phase switching is only supported from version 3.10.51');
            }
        }

        return $r;
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $r = [];

        $old_val = $this->ReadPropertyString('use_fields');
        $use_fields = json_decode($old_val, true);
        $new_fields = [];
        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $new_fields[] = [
                'ident' => $ident,
                'use'   => $use,
            ];
        }
        $new_val = json_encode($new_fields);

        if ($new_val != $old_val) {
            $field = $this->Translate('available variables');
            $r[] = $this->TranslateFormat('Adjust property "{$field}"', ['{$field}' => $field]);
        }

        if ($this->version2num($oldInfo) < $this->version2num('1.5')) {
            $field = $this->Translate('Update interval in standby');
            $r[] = $this->TranslateFormat('Adjust property "{$field}" from minutes to seconds', ['{$field}' => $field]);
        }

        if ($this->version2num($oldInfo) < $this->version2num('1.9')) {
            $field = $this->Translate('Max charging current');
            $r[] = $this->TranslateFormat('The variable "{$field}" is now a float', ['{$field}' => $field]);
            $r[] = $this->Translate('Adjust variableprofile \'KebaConnect.MaxCurrent\'');
            $field = $this->Translate('Mains connection');
            $r[] = $this->TranslateFormat('Adjust the new variable "{$field}"', ['{$field}' => $field]);
        }

        return $r;
    }

    private function CompleteModuleUpdate(array $oldInfo, array $newInfo)
    {
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $values = [];
        foreach (self::$optionalVariables as $var) {
            $ident = $var['Ident'];
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $values[] = [
                'ident' => $ident,
                'use'   => $use,
            ];
        }
        IPS_SetProperty($this->InstanceID, 'use_fields', json_encode($values));

        if ($this->version2num($oldInfo) < $this->version2num('1.5')) {
            $min = $this->ReadPropertyInteger('standby_update_interval');
            IPS_SetProperty($this->InstanceID, 'standby_update_interval', $min * 60);
        }

        if ($this->version2num($oldInfo) < $this->version2num('1.9')) {
            if (IPS_VariableProfileExists('KebaConnect.MaxCurrent')) {
                IPS_DeleteVariableProfile('KebaConnect.MaxCurrent');
            }
            $this->InstallVarProfiles(false);
        }

        return '';
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('StandbyUpdate', 0);
            $this->MaintainTimer('ChargingUpdate', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('StandbyUpdate', 0);
            $this->MaintainTimer('ChargingUpdate', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('StandbyUpdate', 0);
            $this->MaintainTimer('ChargingUpdate', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
        $phase_switching = $this->ReadPropertyBoolean('phase_switching');

        $vpos = 0;
        $this->MaintainVariable('OperationMode', $this->Translate('Operation mode'), VARIABLETYPE_INTEGER, 'KebaConnect.OperationMode', $vpos++, $with_surplus_control);
        $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
        if ($with_surplus_control) {
            $this->MaintainAction('OperationMode', true);
        }

        $this->MaintainVariable('ChargingState', $this->Translate('Charging state'), VARIABLETYPE_INTEGER, 'KebaConnect.ChargingState', $vpos++, true);
        $this->MaintainVariable('CableState', $this->Translate('Cable state'), VARIABLETYPE_INTEGER, 'KebaConnect.CableState', $vpos++, true);

        $vpos = 10;
        $this->MaintainVariable('ActivePower', $this->Translate('Active power'), VARIABLETYPE_FLOAT, 'KebaConnect.Power', $vpos++, true);
        $this->MaintainVariable('ChargedEnergy', $this->Translate('Power consumption of the current loading session'), VARIABLETYPE_FLOAT, 'KebaConnect.Energy', $vpos++, true);
        $this->MaintainVariable('TotalEnergy', $this->Translate('Total energy'), VARIABLETYPE_FLOAT, 'KebaConnect.Energy', $vpos++, true);

        $vpos = 20;
        $this->MaintainVariable('EnableCharging', $this->Translate('Charging enabled'), VARIABLETYPE_BOOLEAN, 'KebaConnect.YesNo', $vpos++, true);
        $this->MaintainAction('EnableCharging', $with_surplus_control == false);

        $this->MaintainVariable('UnlockPlug', $this->Translate('Unlock plug'), VARIABLETYPE_BOOLEAN, 'KebaConnect.UnlockPlug', $vpos++, true);
        $this->MaintainAction('UnlockPlug', true);

        if ($this->version2num($this->GetModuleVersion()) >= $this->version2num('1.9')) {
            $this->MaintainVariable('MaxChargingCurrent', $this->Translate('Maximum current for charging'), VARIABLETYPE_FLOAT, 'KebaConnect.MaxCurrent', $vpos++, true);
            $this->MaintainAction('MaxChargingCurrent', true);
        }

        $this->MaintainVariable('ChargingEnergyLimit', $this->Translate('Maximum energy for charging'), VARIABLETYPE_FLOAT, 'KebaConnect.EnergyLimit', $vpos++, true);
        $this->MaintainAction('ChargingEnergyLimit', true);

        if ($this->version2num($this->GetModuleVersion()) >= $this->version2num('1.9')) {
            $this->MaintainVariable('MaxSupportedCurrent', $this->Translate('Maximum supported current'), VARIABLETYPE_FLOAT, 'KebaConnect.MaxCurrent', $vpos++, true);
        }

        $this->MaintainVariable('MainsConnectionMode', $this->Translate('Mains connection phase switching'), VARIABLETYPE_INTEGER, 'KebaConnect.MainsPhases', $vpos++, $phase_switching);
        if ($phase_switching) {
            $this->MaintainAction('MainsConnectionMode', true);
        }
        $this->MaintainVariable('MainsConnectionPhases', $this->Translate('Mains connection number of used phases'), VARIABLETYPE_INTEGER, '', $vpos++, true);

        $this->MaintainVariable('SurplusReady', $this->Translate('Ready for surplus charging'), VARIABLETYPE_BOOLEAN, 'KebaConnect.YesNo', $vpos++, $with_surplus_control);
        $this->MaintainVariable('SurplusPower', $this->Translate('Available surplus power'), VARIABLETYPE_FLOAT, 'KebaConnect.SurplusPower', $vpos++, $with_surplus_control);
        if ($with_surplus_control) {
            $this->MaintainAction('SurplusPower', true);
        }

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

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('StandbyUpdate', 0);
            $this->MaintainTimer('ChargingUpdate', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetStandbyUpdateInterval();
            $this->SetChargingUpdateInterval();
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->SetStandbyUpdateInterval();
            $this->SetChargingUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('KEBA KeConnect P30 (UDP)');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => [
                [
                    'type'     => 'ValidationTextBox',
                    'name'     => 'host',
                    'caption'  => 'IP address of the wallbox',
                    'validate' => '^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$',
                ],
                [
                    'type'     => 'ValidationTextBox',
                    'name'     => 'serialnumber',
                    'caption'  => 'Serial number (optional)',
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'standby_update_interval',
                    'suffix'  => 'Seconds',
                    'minimum' => 0,
                    'caption' => 'Update interval in standby',
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'charging_update_interval',
                    'suffix'  => 'Seconds',
                    'minimum' => 0,
                    'caption' => 'Update interval while charging',
                ],
            ],
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

        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'items'    => [
                [
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
                            'width'   => 'auto',
                            'save'    => false,
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
                ],
            ],
            'caption'  => 'Additional variables',
            'expanded' => false,
        ];

        $formElements[] = [
            'type'     => 'ExpansionPanel',
            'caption'  => 'Charging history',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'save_history',
                    'caption' => 'save charging history entries'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'minimum' => 0,
                    'suffix'  => 'days',
                    'name'    => 'history_age',
                    'caption' => 'maximun age of history entries'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'show_history',
                    'caption' => 'show table of charging history'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'save_per_rfid',
                    'caption' => 'save power consumption per RFID'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' ... by activating this switch, additional variables are created on demand and logged as counters',
                ],
            ],
        ];

        $formElements[] = [
            'type'    => 'RowLayout',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'phase_switching',
                    'caption' => 'support phase switching via output X2',
                ],
                [
                    'type'    => 'Label',
                    'italic'  => true,
                    'caption' => '(assumes the wallbox is equipped and configured for this)',
                ],
            ]
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_surplus_control',
            'caption' => 'variables for PV surplus charging control',
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'log_no_parent',
            'caption' => 'Generate message when the gateway is inactive',
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => $this->GetModulePrefix() . '_StandbyUpdate($id);'
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ]
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Test area',
            'expanded'  => false,
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
                            'onClick' => $this->GetModulePrefix() . '_SendDisplayText($id, $txt);'
                        ],
                    ],
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'type'     => 'ValidationTextBox',
                            'validate' => '^([0-9A-Fa-f][0-9A-Fa-f]){1,8}$',
                            'name'     => 'TAG',
                            'caption'  => 'RFID Tag (max 16 Chars)'
                        ],
                        [
                            'type'     => 'ValidationTextBox',
                            'validate' => '^([0-9A-Fa-f][0-9A-Fa-f]){0,10}$',
                            'name'     => 'CLASS',
                            'caption'  => 'RFID Class (max 20 Chars)'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Authorize session',
                            'onClick' => $this->GetModulePrefix() . '_AuthorizeSession($id, $TAG, $CLASS);'
                        ],
                    ],
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'type'     => 'ValidationTextBox',
                            'validate' => '^([0-9A-Fa-f][0-9A-Fa-f]){1,8}$',
                            'name'     => 'TAG',
                            'caption'  => 'RFID Tag (max 16 Chars)'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Deauthorize session',
                            'onClick' => $this->GetModulePrefix() . '_DeauthorizeSession($id, $TAG);'
                        ],
                    ],
                ],
            ]
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();
        $formActions[] = $this->GetModuleActivityFormAction();

        return $formActions;
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

    private function SetStandbyUpdateInterval(int $sec = null)
    {
        if (is_null($sec)) {
            $sec = $this->ReadPropertyInteger('standby_update_interval');
            $msec = $sec > 0 ? $sec * 1000 : 0;
        } else {
            $msec = $sec * 1000;
        }
        $this->MaintainTimer('StandbyUpdate', $msec);
    }

    private function SetChargingUpdateInterval()
    {
        $chargingState = $this->GetValue('ChargingState');
        if ($chargingState == self::$STATE_CHARGING) {
            $sec = $this->ReadPropertyInteger('charging_update_interval');
            $msec = $sec > 0 ? $sec * 1000 : 0;
        } else {
            $msec = 0;
        }
        $this->MaintainTimer('ChargingUpdate', $msec);
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
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return;
        }

        foreach (['report 1', 'report 2', 'report 3', 'report 100'] as $cmd) {
            $buf = $this->ExecuteCmd($cmd);
            if ($buf != false) {
                $this->DecodeReport($buf);
            }
        }

        $this->EvalSurplusReady();

        $save_history = $this->ReadPropertyBoolean('save_history');
        if ($save_history) {
            $this->GetChargingHistory();
        }

        $this->SetStandbyUpdateInterval();
        $this->SetChargingUpdateInterval();
    }

    public function SetMainsConnectionMode(int $connectionMode)
    {
        $this->SendDebug(__FUNCTION__, 'connectionMode=' . $connectionMode, 0);

        switch ($connectionMode) {
            case self::$MAINS_PHASES_DYNAMIC:
                $r = true;
                break;
            case self::$MAINS_PHASES_FIX1:
                $r = $this->SetMainsConnectionPhases(1);
                break;
            case self::$MAINS_PHASES_FIX3:
                $r = $this->SetMainsConnectionPhases(3);
                break;
            default:
                $r = false;
                break;
        }
        if ($r) {
            $this->SetValue('MainsConnectionMode', $connectionMode);
        }
        return $r;
    }

    public function SetMainsConnectionPhases(int $phases)
    {
        $this->SendDebug(__FUNCTION__, 'phases=' . $phases, 0);

        if ($phases == $this->GetValue('MainsConnectionPhases')) {
            return true;
        }

        $varID = $this->GetIDForIdent('MainsConnectionPhases');
        $ts = IPS_GetVariable($varID)['VariableChanged'];
        $age = time() - $ts;
        $too_quick = $age <= (5 * 60);

        $this->SendDebug(__FUNCTION__, 'last change=' . date('d.m.Y H:i:s') . $ts . ' (' . $this->seconds2duration($age) . ') => ' . ($too_quick ? 'too quickly' : 'possible'), 0);
        if ($too_quick) {
            $this->AddModuleActivity('unable to set number of phases of mains connection to ' . $phases . ' - too quickly');
            return false;
        }

        $phase_switching = $this->ReadPropertyBoolean('phase_switching');
        if ($phase_switching) {
            switch ($phases) {
                case 1:
                    $r = $this->CallAction('x2 ' . self::$X2_1P);
                    break;
                case 3:
                    $r = $this->CallAction('x2 ' . self::$X2_3P);
                    break;
                default:
                    $r = false;
                    break;
            }
        } else {
            $r = true;
        }

        if ($r) {
            $this->SetValue('MainsConnectionPhases', $phases);
            $this->AddModuleActivity('set number of phases of mains connection to ' . $phases);
        }
        return $r;
    }

    public function SetSurplusPower(float $power)
    {
        $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
        if ($with_surplus_control) {
            return false;
        }

        $phase_switching = $this->ReadPropertyBoolean('phase_switching');
        $connectionMode = $this->GetValue('MainsConnectionMode');
        $phase_dynamic = $phase_switching && $connectionMode == self::$MAINS_PHASES_DYNAMIC;
        $n_phases = $this->GetValue('MainsConnectionPhases');

        $current = $power / 230;
        if ($n_phases == 3) {
            $current /= $n_phases;
            if ($current < 6 && $phase_dynamic) {
                $n_phases = 1;
                $r = $this->SetMainsConnectionPhases($n_phases);
                if ($r == false) {
                    return $r;
                }
                $current *= $n_phases;
            }
        } else {
            if ($current > 18 && $phase_dynamic) {
                $n_phases = 3;
                $r = $this->SetMainsConnectionPhases($n_phases);
                if ($r == false) {
                    return $r;
                }
                $current /= $n_phases;
            }
        }

        $s = 'mains phases=' . $n_phases;
        $s .= ', power=' . $this->format_float($power, 2) . ' W';
        $s .= ', current=' . $this->format_float($current, 1) . ' A';
        $this->SendDebug(__FUNCTION__, $s, 0);

        $r = $this->SetMaxChargingCurrent($current);
        if ($r) {
            $this->SetValue('SurplusPower', $power);
            $enableCharging = $this->GetValue('EnableCharging');
            $chargingCurrent = $this->GetValue('MaxChargingCurrent');
            if ($chargingCurrent == 0) {
                if ($enableCharging && $this->CallAction('ena 0')) {
                    $this->SetValue('EnableCharging', false);
                    $this->AddModuleActivity('disable charging');
                }
            } else {
                if ($enableCharging == false && $this->CallAction('ena 1')) {
                    $this->SetValue('EnableCharging', true);
                    $this->AddModuleActivity('enable charging');
                }
            }
        }
        return $r;
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

            $s = $this->GetArrayElem($jdata, 'started', '');
            $started = $this->DecodeTstamp($s, 'started');

            $s = $this->GetArrayElem($jdata, 'ended', '');
            $ended = $this->DecodeTstamp($s, 'ended');

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

            $reason = $this->GetArrayElem($jdata, 'reason', 0);

            $new_entry = [
                'Session ID'  => $sessionID,
                'started'     => $started,
                'ended'       => $ended,
                'RFID tag'    => $tag,
                'Curr HW'     => $curr_hw,
                'E start'     => $e_start,
                'E pres'      => $e_pres,
                'reason'      => $reason,
            ];

            if ($ended == 0 && $reason == 0) {
                $this->SendDebug(__FUNCTION__, 'ignore entry w/o "ended"', 0);
            } else {
                $new_entries[] = $new_entry;
            }

            if ($sessionID <= $lastSessionID) {
                $this->SendDebug(__FUNCTION__, 'all new reports processed', 0);
                break;
            }
        }

        $save_per_rfid = $this->ReadPropertyBoolean('save_per_rfid');
        if ($save_per_rfid) {
            $this->SendDebug(__FUNCTION__, 'save_per_rfid', 0);
            foreach ($new_entries as $new_entry) {
                $this->SendDebug(__FUNCTION__, 'save_per_rfid: new_entry=' . print_r($new_entry, true), 0);

                $sessionID = $new_entry['Session ID'];
                $fnd = false;
                foreach ($old_entries as $old_entry) {
                    if ($sessionID == $old_entry['Session ID']) {
                        $fnd = true;
                        break;
                    }
                }
                if ($fnd) {
                    $this->SendDebug(__FUNCTION__, 'save_per_rfid: found old "Session ID" ' . $sessionID . ' -> ignore', 0);
                    continue;
                }

                $tag = $new_entry['RFID tag'];
                if ($tag == '') {
                    $this->SendDebug(__FUNCTION__, 'save_per_rfid: "RFID tag" is empty -> ignore', 0);
                    continue;
                }

                $ident = 'ChargedEnergy_' . $tag;
                $this->SendDebug(__FUNCTION__, 'save_per_rfid: tag=' . $tag . ', ident=' . $ident, 0);

                @$varID = $this->GetIDForIdent($ident);
                if ($varID == false) {
                    $name = $this->Translate('Total power consumption of RFID') . ' ' . $tag;
                    $this->MaintainVariable($ident, $name, VARIABLETYPE_FLOAT, 'KebaConnect.Energy', 1000, true);
                    $this->SetVariableLogging($ident, 1 /* Zähler */);
                    $this->SendDebug(__FUNCTION__, 'save_per_rfid: create var ' . $ident, 0);
                }
                $e_pres = $new_entry['E pres'];
                $old = $this->GetValue($ident);
                $new = $old + $e_pres;
                $this->SetValue($ident, $new);
                $this->SendDebug(__FUNCTION__, 'save_per_rfid: sessionID=' . $sessionID . ': increment var ' . $ident . ' from ' . $old . ' with ' . $e_pres . ' to ' . $new, 0);
            }
        }

        $history_age = $this->ReadPropertyInteger('history_age');
        if ($history_age > 0) {
            $reftstamp = time() - ($history_age * 60 * 60 * 24);
        } else {
            $reftstamp = 0;
        }

        foreach ($old_entries as $old_entry) {
            $fnd = false;
            if ($old_entry['started'] < $reftstamp) {
                continue;
            }
            foreach ($new_entries as $new_entry) {
                if ($old_entry['Session ID'] == $new_entry['Session ID']) {
                    $fnd = true;
                    break;
                }
            }
            if ($fnd == false) {
                $new_entries[] = $old_entry;
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
                $s = date('d.m.Y H:i:s', $entry['started']);
                $e = $entry['ended'] ? date('d.m.Y H:i:s', $entry['ended']) : '-';
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
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return;
        }

        $buf = $this->ExecuteCmd('report 3', 'report 100');
        if ($buf != false) {
            $this->DecodeReport($buf);
        }
        $this->EvalSurplusReady();
    }

    private function ExtractVersion()
    {
        $firmware = $this->ReadAttributeString('Firmware');
        if ($firmware == '') {
            return false;
        }
        $r = explode(' ', $firmware);
        $version = count($r) > 3 ? $r[2] : '';
        return $version;
    }

    private function ExtractModel()
    {
        $product = $this->ReadAttributeString('Product');
        if (strlen($product) < 5) {
            return false;
        }
        $model = substr($product, 3, 3);
        return $model;
    }

    private function ExtractSeries()
    {
        $product = $this->ReadAttributeString('Product');
        if (strlen($product) < 14) {
            return false;
        }
        $code = substr($product, 13, 1);
        $map_series = [
            '0'=> 'E',
            '1'=> 'B',
            '2'=> 'C',
            '3'=> 'A',
            'R'=> 'X',
            'C'=> 'X',
            'E'=> 'X',
            'G'=> 'X',
            'H'=> 'X',
        ];
        $series = isset($map_series[$code]) ? $map_series[$code] : '';
        return $series;
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
            $firmware = $this->GetArrayElem($jdata, 'Firmware', '');
            $this->SetSummary($product . ' (#' . $serial . ')');

            $this->WriteAttributeString('Product', $product);
            $this->WriteAttributeString('Firmware', $firmware);

            $series = $this->ExtractSeries();
            $version = $this->ExtractVersion();

            $this->SendDebug(__FUNCTION__, 'product=' . $product . ', serial=' . $serial . ', firmware=' . $firmware . ' / series=' . $series . ', version=' . $version, 0);

            if (in_array('ProductType', $use_idents)) {
                $this->SaveValue('ProductType', $product, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ProductType" to "' . $product . '" from field "Product"', 0);
            }
            if (in_array('SerialNumber', $use_idents)) {
                $this->SaveValue('SerialNumber', $serial, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "SerialNumber" to "' . $serial . '" from field "Serial"', 0);
            }
            if (in_array('FirmwareVersion', $use_idents)) {
                $this->SaveValue('FirmwareVersion', $firmware, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "FirmwareVersion" to "' . $firmware . '" from field "Firmware"', 0);
            }
            if (in_array('Backend', $use_idents)) {
                $b = boolval($this->GetArrayElem($jdata, 'Backend', 0));
                $this->SaveValue('ComBackend', $b, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "ComBackend" to ' . $this->bool2str($b) . ' from field "Backend"', 0);
            }
            if (in_array('LastBoot', $use_idents)) {
                $sec = intval($this->GetArrayElem($jdata, 'Sec', 0));
                $ts = $now - $sec;
                $this->SaveValue('LastBoot', $ts, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "LastBoot" to ' . date('d.m.Y H:i:s', $ts) . ' from field "Sec"', 0);
            }
            $dsw1 = $this->GetArrayElem($jdata, 'DIP-Sw1', '');
            $dsw2 = $this->GetArrayElem($jdata, 'DIP-Sw2', '');
            $this->SendDebug(__FUNCTION__, 'Dip-Switch 1=' . $this->int2bitmap(hexdec($dsw1), 8) . ', 2=' . $this->int2bitmap(hexdec($dsw2), 8), 0);

            $timeQ = $this->GetArrayElem($jdata, 'timeQ', '');
            $timeQ_map = [
                0 => 'NONE',
                1 => 'NOT_SYNCED',
                2 => 'WEAK',
                3 => 'STRONG',
            ];
            $this->SendDebug(__FUNCTION__, 'timeQ=' . $timeQ . '(' . $timeQ_map[$timeQ] . ')', 0);
            if ($timeQ < 2) {
                $cmd = 'setdatetime ' . time();
                $r = $this->ExecuteCmd($cmd);
                $this->SendDebug(__FUNCTION__, 'cmd=' . $cmd . ' => ' . $r, 0);
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
                $enable_user = boolval($this->GetArrayElem($jdata, 'Enable user', false));
                $this->SaveValue('EnableCharging', $enable_user, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "EnableCharging" to ' . $enable_user . ' from field "Enable user"', 0);
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
                $max_curr = floatval($this->GetArrayElem($jdata, 'Max user', 0));
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

            $auth_on = $this->GetArrayElem($jdata, 'AuthON', 0);
            $auth_req = $this->GetArrayElem($jdata, 'Authreq', 0);
            $this->SendDebug(__FUNCTION__, 'AuthON=' . $auth_on . ', Authreq=' . $auth_req, 0);

            $b = $this->checkAction('SwitchEnableCharging', false);
            $this->MaintainAction('EnableCharging', $b);

            $b = $this->checkAction('SetMaxChargingCurrent', false);
            $this->MaintainAction('MaxChargingCurrent', $b);

            $b = $this->checkAction('SetChargingEnergyLimit', false);
            $this->MaintainAction('ChargingEnergyLimit', $b);

            $b = $this->checkAction('UnlockPlug', false);
            $this->MaintainAction('UnlockPlug', $b);

            $x2src = $this->GetArrayElem($jdata, 'X2 phaseSwitch source', 0);
            if (in_array('PhaseSwitchSource', $use_idents)) {
                $this->SaveValue('PhaseSwitchSource', $x2src, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "PhaseSwitchSource" to ' . $x2src . ' from field "X2 phaseSwitch source"', 0);
            }

            $x2 = $this->GetArrayElem($jdata, 'X2 phaseSwitch', 0);
            if (in_array('PhaseSwitch', $use_idents)) {
                $this->SaveValue('PhaseSwitch', $x2, $is_changed);
                $this->SendDebug(__FUNCTION__, 'set variable "PhaseSwitch" to ' . $x2 . ' from field "X2 phaseSwitch"', 0);
            }

            $phase_switching = $this->ReadPropertyBoolean('phase_switching');
            if ($phase_switching) {
                if ($x2src != self::$X2SRC_UDP) {
                    $r = $this->CallAction('x2src ' . self::$X2SRC_UDP);
                    if ($r) {
                        if (in_array('PhaseSwitchSource', $use_idents)) {
                            $this->SaveValue('PhaseSwitchSource', self::$X2SRC_UDP, $is_changed);
                        }
                        $this->AddModuleActivity('set phase switch source to UDP');
                    }
                }
                $phases = $this->GetValue('MainsConnectionPhases');
                switch ($phases) {
                    case 1:
                        if ($x2 != self::$X2_1P) {
                            $r = $this->CallAction('x2 ' . self::$X2_1P);
                            if ($r) {
                                if (in_array('PhaseSwitch', $use_idents)) {
                                    $this->SaveValue('PhaseSwitch', self::$X2_1P, $is_changed);
                                }
                                $this->AddModuleActivity('force number of phases of mains connection to 1');
                            }
                        }
                        break;
                    case 3:
                        if ($x2 != self::$X2_3P) {
                            $r = $this->CallAction('x2 ' . self::$X2_3P);
                            if ($r) {
                                if (in_array('PhaseSwitch', $use_idents)) {
                                    $this->SaveValue('PhaseSwitch', self::$X2_3P, $is_changed);
                                }
                                $this->AddModuleActivity('force number of phases of mains connection to 3');
                            }
                        }
                        break;
                }
            } else {
                if ($x2src != self::$X2SRC_NONE) {
                    $r = $this->CallAction('x2src ' . self::$X2SRC_NONE);
                    if ($r) {
                        if (in_array('PhaseSwitchSource', $use_idents)) {
                            $this->SaveValue('PhaseSwitchSource', self::$X2SRC_NONE, $is_changed);
                        }
                        $this->AddModuleActivity('set phase switch source to none');
                    }
                }
            }
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
                $s = $this->GetArrayElem($jdata, 'started', '');
                $ts = $this->DecodeTstamp($s, 'started');
                $this->SaveValue('ChargingStarted', $ts, $is_changed);
                $s = $ts ? date('d.m.Y H:i:s', $ts) : '-';
                $this->SendDebug(__FUNCTION__, 'set variable "ChargingStarted" to ' . $s . ' from field "started"', 0);
            }
            if (in_array('ChargingEnded', $use_idents)) {
                $s = $this->GetArrayElem($jdata, 'ended', '');
                $ts = $this->DecodeTstamp($s, 'ended');
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

        if (in_array('ChargingState', $use_idents) && isset($jdata['State'])) {
            $charging_state = $jdata['State'];
            $chg = false;
            $this->SaveValue('ChargingState', $charging_state, $chg);
            if ($chg) {
                $is_changed = true;
                $reload_reports = true;
            }
            $this->SendDebug(__FUNCTION__, 'set variable "ChargingState" to ' . $charging_state . ' from field "State"', 0);
        }
        if (in_array('CableState', $use_idents) && isset($jdata['Plug'])) {
            $cable_state = $jdata['Plug'];
            $chg = false;
            $this->SaveValue('CableState', $cable_state, $chg);
            if ($chg) {
                $is_changed = true;
                $reload_reports = true;
            }
            $this->SendDebug(__FUNCTION__, 'set variable "CableState" to ' . $cable_state . ' from field "CableState"', 0);
        }
        if (in_array('MaxChargingCurrent', $use_idents) && isset($jdata['Max curr'])) {
            $max_curr = floatval($jdata['Max curr']);
            $max_curr /= 1000;
            $this->SaveValue('MaxChargingCurrent', $max_curr, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set variable "MaxChargingCurrent" to ' . $max_curr . ' from field "Max curr"', 0);
        }
        if (in_array('ChargedEnergy', $use_idents) && isset($jdata['E pres'])) {
            $e_pres = floatval($jdata['E pres']);
            $e_pres /= 10000;
            $this->SaveValue('ChargedEnergy', $e_pres, $is_changed);
            $this->SendDebug(__FUNCTION__, 'set variable "ChargedEnergy" to ' . $e_pres . ' from field "E pres"', 0);
        }
        if (in_array('EnableCharging', $use_idents) && isset($jdata['Enable sys'])) {
            $enable_sys = boolval($jdata['Enable sys']);
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
                $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
                if ($with_surplus_control) {
                    $operationMode = $this->GetValue('OperationMode');
                    if ($operationMode == self::$OPERATING_MODE_MANUAL) {
                        $enabled = true;
                    }
                } else {
                    $enabled = true;
                }
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
            case 'AuthorizeSession':
            case 'DeauthorizeSession':
                $enabled = true;
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
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return;
        }

        $r = $this->ExecuteCmd($cmd);
        $this->SendDebug(__FUNCTION__, 'cmd=' . $cmd . ' => ' . $r, 0);
        return $r == "TCH-OK :done\n";
    }

    public function SendDisplayText(string $txt)
    {
        $this->SendDebug(__FUNCTION__, 'text="' . $txt . '"', 0);

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
        $this->SendDebug(__FUNCTION__, '', 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        if ($this->GetValue('EnableCharging') == true) {
            $this->SendDebug(__FUNCTION__, 'force disable charging', 0);
            $r = $this->CallAction('ena 0');
            if ($r == false) {
                return $r;
            }
            $this->AddModuleActivity('force disable charging');
            IPS_Sleep(250);
        }

        $cmd = 'unlock';
        $r = $this->CallAction($cmd);
        if ($r) {
            $this->SetValue('UnlockPlug', false); // Trick, damit der Wert immer "false" bleibt
            $this->AddModuleActivity('unlock plug');
        }
        return $r;
    }

    public function SwitchEnableCharging(bool $enable)
    {
        $this->SendDebug(__FUNCTION__, 'enable=' . $this->bool2str($enable), 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // if ($operationMode == self::$OPERATING_MODE_OFF) {

        // enable charging
        // 0 = Disabled; is indicated with a blue flashing LED
        // 1 = Enabled
        $cmd = 'ena ' . ($enable ? '1' : '0');
        $r = $this->CallAction($cmd);
        if ($r) {
            $this->SetValue('EnableCharging', $enable);
            $this->AddModuleActivity(($enable ? 'en' : 'dis') . 'able charging');
        }
        if ($r) {
            $r = $this->EvalSurplusReady();
        }
        return $r;
    }

    public function AuthorizeSession(string $tag, string $class)
    {
        $this->SendDebug(__FUNCTION__, 'tag="' . $tag . '", classe="' . $class . '"', 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // authorize charging
        // tag   = RFID tag (max 8 hex-bytes = 16 chars)
        // class = RFID class (max 10 hex-bytes = 20 chars)

        if (preg_match('/^([0-9A-Fa-f][0-9A-Fa-f]){1,8}$/', $tag) == false) {
            $this->SendDebug(__FUNCTION__, 'tag="' . $tag . '" is malformed - max 8 hex-bytes = 16 chars', 0);
            return false;
        }
        if (preg_match('/^([0-9A-Fa-f][0-9A-Fa-f]){0,10}$/', $class) == false) {
            $this->SendDebug(__FUNCTION__, 'class="' . $class . '" is malformed - max 10 hex-bytes = 20 chars', 0);
            return false;
        }

        $cmd = 'start ' . $tag . ' ' . $class;
        $r = $this->CallAction($cmd);
        if ($r) {
            $this->AddModuleActivity('authorize RFID ' . $tag . ' ' . $classg);
        }
        return $r;
    }

    public function DeauthorizeSession(string $tag)
    {
        $this->SendDebug(__FUNCTION__, 'tag="' . $tag . '"', 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // deauthorize charging
        // tag   = RFID tag (8 bytes)

        if (preg_match('/^([0-9A-Fa-f][0-9A-Fa-f]){1,8}$/', $tag) == false) {
            $this->SendDebug(__FUNCTION__, 'tag="' . $tag . '" is malformed - max 8 hex-bytes = 16 chars', 0);
            return false;
        }

        $cmd = 'stop ' . $tag;
        $r = $this->CallAction($cmd);
        if ($r) {
            $this->AddModuleActivity('deauthorize RFID ' . $tag);
        }
        return $r;
    }

    public function SetMaxChargingCurrent(float $current)
    {
        $this->SendDebug(__FUNCTION__, 'current=' . $current, 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // maximum allowed loading current in milliampere
        // range: 0 or 6000mA ... 32000mA
        if ($current > 0) {
            $min = 6.0;
            if ($current < $min) {
                $this->SendDebug(__FUNCTION__, 'value ist below minimum (' . $min . ' A) => 0', 0);
                $current = 0;
            }
            $max = 32;
            if ($current > $max) {
                $this->SendDebug(__FUNCTION__, 'value ist above maximum (' . $max . ' A) => 0', 0);
                $current = 0;
            }
        }
        $c = intval($current * 1000);
        $this->SendDebug(__FUNCTION__, 'current=' . $c . ' mA', 0);
        if ($current != $this->GetValue('MaxChargingCurrent')) {
            $delay = 1; // 0 or 1 - 860400 sec
            $cmd = 'currtime ' . $c . ' ' . $delay;
            /*
            $cmd = 'curr ' . $c;
             */
            $r = $this->CallAction($cmd);
            if ($r) {
                $this->SetValue('MaxChargingCurrent', $current);
                $this->AddModuleActivity('set max charging current to ' . $c . ' mA with delay of ' . $delay . 's');
            }
        } else {
            $r = true;
        }
        return $r;
    }

    public function SetChargingEnergyLimit(float $energy)
    {
        $this->SendDebug(__FUNCTION__, 'energy=' . $energy, 0);

        if ($this->checkAction(__FUNCTION__, true) == false) {
            return false;
        }

        // Charging energy limit in 0,1Wh
        $e = $energy * 1000;
        $this->SendDebug(__FUNCTION__, 'energy=' . $e . ' Wh', 0);
        $e = $energy * 10000;
        $cmd = 'setenergy ' . $e;
        $r = $this->CallAction($cmd);
        if ($r) {
            $this->SetValue('ChargingEnergyLimit', $energy);
            $this->AddModuleActivity('set charging energy limit to ' . ($energy * 1000) . ' Wh');
        }
        return $r;
    }

    public function EvalSurplusReady()
    {
        $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
        if ($with_surplus_control == false) {
            return false;
        }
        $surplusReady = false;
        $operationMode = $this->GetValue('OperationMode');
        if ($operationMode == self::$OPERATING_MODE_SURPLUS) {
            $chargingState = $this->GetValue('ChargingState');
            switch ($chargingState) {
                case self::$STATE_READY:
                case self::$STATE_CHARGING:
                case self::$STATE_SUSPENDED:
                    $surplusReady = true;
                    break;
                default:
                    break;
            }
        }
        $this->SetValue('SurplusReady', $surplusReady);
        if ($surplusReady) {
            $surplusPower = $this->GetValue('SurplusPower');
            $r = $this->SetSurplusPower($surplusPower);
        } else {
            $r = true;
        }
        $this->SendDebug(__FUNCTION__, 'operationMode=' . $operationMode . ', surplusReady=' . $this->bool2str($surplusReady), 0);
        return $r;
    }

    public function SetOperationMode(int $operationMode)
    {
        $this->SendDebug(__FUNCTION__, 'operationMode=' . $operationMode, 0);
        $with_surplus_control = $this->ReadPropertyBoolean('with_surplus_control');
        if ($with_surplus_control == false) {
            return false;
        }

        $r = true;
        switch ($operationMode) {
            case self::$OPERATING_MODE_OFF:
                $this->SetValue('OperationMode', $operationMode);
                $this->AddModuleActivity('switch operation-mode to off');
                if ($this->CallAction('ena 0')) {
                    $this->SetValue('EnableCharging', false);
                    $this->AddModuleActivity('disable charging');
                }
                $r = $this->EvalSurplusReady();
                if ($r) {
                    $r = $this->SetMaxChargingCurrent(0);
                }
                break;
            case self::$OPERATING_MODE_MANUAL:
                $this->SetValue('OperationMode', $operationMode);
                $this->AddModuleActivity('switch operation-mode to manual');
                if ($this->CallAction('ena 1')) {
                    $this->SetValue('EnableCharging', true);
                    $this->AddModuleActivity('enable charging');
                }
                break;
            case self::$OPERATING_MODE_SURPLUS:
                $this->SetValue('OperationMode', $operationMode);
                $this->AddModuleActivity('switch operation-mode to surplus loading');
                $r = $this->EvalSurplusReady();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            return;
        }

        $r = false;
        switch ($ident) {
            case 'EnableCharging':
                $r = $this->SwitchEnableCharging((bool) $value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $this->bool2str($value) . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'MaxChargingCurrent':
                $r = $this->SetMaxChargingCurrent((float) $value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'ChargingEnergyLimit':
                $r = $this->SetChargingEnergyLimit((float) $value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'UnlockPlug':
                $r = $this->UnlockPlug();
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                break;
            case 'StandbyUpdate':
                $this->StandbyUpdate();
                break;
            case 'ChargingUpdate':
                $this->ChargingUpdate();
                break;
            case 'MainsConnectionMode':
                $r = $this->SetMainsConnectionMode($value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'SurplusPower':
                $r = $this->SetSurplusPower($value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            case 'OperationMode':
                $r = $this->SetOperationMode($value);
                $this->SendDebug(__FUNCTION__, $ident . '=' . $value . ' => ret=' . $this->bool2str($r), 0);
                if ($r) {
                    $this->SetStandbyUpdateInterval(1);
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
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

    private function DecodeTstamp($value, $field)
    {
        $ts = 0;
        if ($value != false) {
            if (preg_match('/^([0-9]+)000$/', $value, $r)) {
                $ts = $this->GetValue('LastBoot') + (int) $r[1];
                $this->SendDebug(__FUNCTION__, 'field "' . $field . '"=' . $value . ' => ' . $ts . '/' . date('d.m.Y H:i:s', $ts), 0);
            } else {
                $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $value, new DateTimeZone('UTC'));
                if ($d == false) {
                    $this->SendDebug(__FUNCTION__, 'field "' . $field . '"=' . $value . ': parse failed ' . print_r(DateTime::getLastErrors(), true), 0);
                } else {
                    $ts = intval($d->format('U'));
                    $this->SendDebug(__FUNCTION__, 'field "' . $field . '"=' . $value . ' => ' . $ts . '/' . date('d.m.Y H:i:s', $ts), 0);
                }
            }
        }
        return $ts;
    }
}
