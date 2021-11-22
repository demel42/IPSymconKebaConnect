<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php'; // globale Funktionen
require_once __DIR__ . '/../libs/local.php';  // lokale Funktionen

class KeConnectP30udp extends IPSModule
{
    use KebaConnectCommonLib;
    use KebaConnectLocalLib;

    private static $QueueMaxWait = 60;

    private static $Variables = [
        [
            'Ident'           => 'ChargingState',
            'Desc'            => 'Charging state',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.ChargingState',
        ],
        [
            'Ident'           => 'CableState',
            'Desc'            => 'Cable state',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.CableState',
        ],
        [
            'Ident'           => 'ErrorCode',
            'Desc'            => 'Error code',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.Error',
        ],

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
            'Ident'           => 'ActivePower',
            'Desc'            => 'Active power',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Power',
        ],
        [
            'Ident'           => 'TotalEnergy',
            'Desc'            => 'Total energy',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Energy',
        ],
        [
            'Ident'           => 'MaxChargingCurrent',
            'Desc'            => 'Max charging current',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
        ],
        [
            'Ident'           => 'MaxSupportedCurrent',
            'Desc'            => 'Max supported current',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
        ],
        [
            'Ident'           => 'PowerFactor',
            'Desc'            => 'Power factor',
            'VariableType'    => VARIABLETYPE_FLOAT,
        ],

        [
            'Ident'           => 'RFID',
            'Desc'            => 'RFID card',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Ident'           => 'ChargedEnergy',
            'Desc'            => 'Charged energy',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Energy',
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

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);
        $this->RegisterPropertyInteger('update_interval', '5');

        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterTimer('UpdateData', 0, 'KebaConnect_UpdateData(' . $this->InstanceID . ');');
        $this->RegisterTimer('Cycle', 0, 'KebaConnect_Cycle(' . $this->InstanceID . ');');

        $sdata = $this->SetBuffer('Queue', '');

        $this->CreateVarProfile('KebaConnect.Current', VARIABLETYPE_FLOAT, ' A', 0, 0, 0, 3, '');
        $this->CreateVarProfile('KebaConnect.Power', VARIABLETYPE_FLOAT, ' W', 0, 0, 0, 3, '');
        $this->CreateVarProfile('KebaConnect.Energy', VARIABLETYPE_FLOAT, ' kWh', 0, 0, 0, 1, '');
        $this->CreateVarProfile('KebaConnect.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 0, '');
        $this->CreateVarProfile('KebaConnect.Factor', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 2, '');

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('system started'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('not read for charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => 2, 'Name' => $this->Translate('read for charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => 3, 'Name' => $this->Translate('charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => 4, 'Name' => $this->Translate('error occured'), 'Farbe' => -1];
        $associations[] = ['Wert' => 5, 'Name' => $this->Translate('charging suspended'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.ChargingState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('not plugged'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('plugged in station'), 'Farbe' => -1];
        $associations[] = ['Wert' => 3, 'Name' => $this->Translate('locked in station'), 'Farbe' => -1];
        $associations[] = ['Wert' => 5, 'Name' => $this->Translate('plugged in vehicle'), 'Farbe' => -1];
        $associations[] = ['Wert' => 7, 'Name' => $this->Translate('locked in vehicle'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.CableState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('no error'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('Error 0x%05x'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.Error', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $this->RequireParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        foreach (self::$Variables as $var) {
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

        $vpos = 20;
        $this->MaintainVariable('LastChange', $this->Translate('Last change'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateData', 0);
            $this->SetStatus(IS_INACTIVE);
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
        $this->SetUpdateInterval();
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
            'type'    => 'Label',
            'caption' => 'Update data every X minutes'
        ];
        $formElements[] = [
            'type'    => 'IntervalBox',
            'name'    => 'update_interval',
            'caption' => 'Minutes'
        ];

        foreach (self::$Variables as $var) {
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
            'caption'  => 'Variables',
            'expanded' => true
        ];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'KebaConnect_UpdateData($id);'
        ];

        return $formActions;
    }

    public function Cycle()
    {
        $this->CheckAction();
        $this->SetTimer();
    }

    public function SetTimer()
    {
        $msec = 0;
        $sdata = $this->GetBuffer('Queue');
        if ($sdata != false) {
            $actions = json_decode($sdata, true);
            if (count($actions) > 0) {
                $msec = 60 * 1000;
            }
        }
        $this->SetTimerInterval('Cycle', $msec);
    }

    public function AddAction(string $cmd)
    {
        $this->SendDebug(__FUNCTION__, 'cmd=' . $cmd, 0);

        $time_start = microtime(true);
        $n_actions = 0;
        if (IPS_SemaphoreEnter($this->semaphoreID, $this->semaphoreTM)) {
            $sdata = $this->GetBuffer('Queue');
            $new_actions = [];
            if ($sdata != false) {
                $actions = json_decode($sdata, true);
                // $this->SendDebug(__FUNCTION__, 'actions#' . count($actions), 0);
                // $this->SendDebug(__FUNCTION__, 'actions=' . print_r($actions, true), 0);
                foreach ($actions as $action) {
                    $exec_ts = isset($action['exec_ts']) ? $action['exec_ts'] : 0;
                    if ($exec_ts > 0 && $exec_ts < time() - self::$QueueMaxWait) {
                        continue;
                    }
                    $new_actions[] = $action;
                }
            }
            $action = [
                'creation'  => time(),
                'cmd'       => $cmd,
                'exec_ts'   => 0,
            ];
            $new_actions[] = $action;
            // $this->SendDebug(__FUNCTION__, 'new_actions#' . count($new_actions), 0);
            // $this->SendDebug(__FUNCTION__, 'new_actions=' . print_r($new_actions, true), 0);
            $sdata = json_encode($new_actions);
            $this->SetBuffer('Queue', $sdata);
            IPS_SemaphoreLeave($this->semaphoreID);
        } else {
            $this->SendDebug(__FUNCTION__, 'sempahore ' . $this->semaphoreID . ' is not accessable', 0);
        }
        $duration = round(microtime(true) - $time_start, 2);
        if ($duration > 0) {
            $this->SendDebug(__FUNCTION__, 'duration=' . $duration . 's', 0);
        }
    }

    public function DeleteAction()
    {
        $time_start = microtime(true);
        if (IPS_SemaphoreEnter($this->semaphoreID, $this->semaphoreTM)) {
            $sdata = $this->GetBuffer('Queue');
            $new_actions = [];
            if ($sdata != false) {
                $actions = json_decode($sdata, true);
                $n_actions = count($actions);
                // $this->SendDebug(__FUNCTION__, 'actions#' . count($actions), 0);
                // $this->SendDebug(__FUNCTION__, 'actions=' . print_r($actions, true), 0);
                for ($i = 1; $i < $n_actions; $i++) {
                    $new_actions[] = $actions[$i];
                }
            }
            // $this->SendDebug(__FUNCTION__, 'new_actions#' . count($new_actions), 0);
            // $this->SendDebug(__FUNCTION__, 'new_actions=' . print_r($new_actions, true), 0);
            $sdata = json_encode($new_actions);
            $this->SetBuffer('Queue', $sdata);
            IPS_SemaphoreLeave($this->semaphoreID);
        } else {
            $this->SendDebug(__FUNCTION__, 'sempahore ' . $this->semaphoreID . ' is not accessable', 0);
        }
        $duration = round(microtime(true) - $time_start, 2);
        if ($duration > 0) {
            $this->SendDebug(__FUNCTION__, 'duration=' . $duration . 's', 0);
        }
    }

    public function CheckAction()
    {
        $n_actions = 0;
        $time_start = microtime(true);
        if (IPS_SemaphoreEnter($this->semaphoreID, $this->semaphoreTM)) {
            $sdata = $this->GetBuffer('Queue');
            if ($sdata != '') {
                $actions = json_decode($sdata, true);
                $n_actions = count($actions);
                $new_actions = [];
                if ($n_actions > 0) {
                    $action = $actions[0];
                    $this->SendDebug(__FUNCTION__, 'action=' . print_r($action, true), 0);
                    $exec_ts = isset($action['exec_ts']) ? $action['exec_ts'] : 0;
                    if ($exec_ts == 0) {
                        $action['exec_ts'] = time();
                        $this->SendData($action['cmd']);
                        $new_actions[] = $action;
                    } elseif ($exec_ts < time() - self::$QueueMaxWait) {
                        $s = 'no answer to command "' . $action['cmd'] . '" started ' . date('Y-m-d H:i:s', $action['exec_ts']);
                        $this->SendDebug(__FUNCTION__, $s, 0);
                        $this->LogMessage($s, KL_WARNING);
                    } else {
                        $new_actions[] = $action;
                    }
                    for ($i = 1; $i < $n_actions; $i++) {
                        $new_actions[] = $actions[$i];
                    }
                }
                $this->SendDebug(__FUNCTION__, 'new_actions#' . count($new_actions), 0);
                //$this->SendDebug(__FUNCTION__, 'new_actions=' . print_r($new_actions, true), 0);
                $sdata = json_encode($new_actions);
                $this->SetBuffer('Queue', $sdata);
            }
            IPS_SemaphoreLeave($this->semaphoreID);
        } else {
            $this->SendDebug(__FUNCTION__, 'sempahore ' . $this->semaphoreID . ' is not accessable', 0);
        }
        $duration = round(microtime(true) - $time_start, 2);
        if ($duration > 0) {
            $this->SendDebug(__FUNCTION__, 'duration=' . $duration . 's', 0);
        }

        return $n_actions > 0 ? true : false;
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 60 * 1000 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

    public function ReceiveData($data)
    {
        $this->SendDebug(__FUNCTION__, 'got data "' . $data . '"', 0);
        $jdata = json_decode($data, true);
        $buffer = $jdata['Buffer'];
        if ($buffer == 'TCHOK :done') {
            // TCHOK fail
            $this->DeleteAction();
            $this->Cycle();
            $this->SendDebug(__FUNCTION__, 'got command ack', 0);
        } else {
            $this->DecodeData($buffer);
        }
    }

    private function SendData(string $cmd)
    {
        $r = IPS_GetConfiguration($this->GetConnectionID());
        $cfg = json_decode($r, true);

        $data = [
            'DataID'     => '{8E4D9B23-E0F2-1E05-41D8-C21EA53B8706}',
            'Buffer'     => utf8_encode($cmd),
            'ClientIP'   => $cfg['Host'],
            'ClientPort' => $cfg['Port'],
            'Broadcast'  => false,
        ];
        $jdata = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'request data ' . print_r($jdata, true), 0);
        $this->SendDataToParent($jdata);
    }

    public function UpdateData()
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
        $this->AddAction('report 1');
        $this->AddAction('report 2');
        $this->AddAction('report 3');
        $this->Cycle();
    }

    public function DecodeData(string $data)
    {
        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);

        if (isset($jdata['ID'])) {
            $this->SendDebug(__FUNCTION__, 'report ' . $jdata['ID'], 0);

            $now = time();
            $is_changed = false;

            foreach ($jdata as $var => $val) {
                $fnd = true;
                $ign = false;
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
                        case 'Error 1':
                        case 'Error 2':
                            $ign = true;
                            break;
                        case 'Max curr':
                            $ident = 'MaxChargingCurrent';
                            break;
                        case 'Curr HW':
                            $ident = 'MaxSupportedCurrent';
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
                    // $this->SendDebug(__FUNCTION__, 'unused field ' . $var . '="' . $val . '"', 0);
                    continue;
                }
                $use = false;
                foreach ($use_fields as $field) {
                    if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                        $use = (bool) $this->GetArrayElem($field, 'use', false);
                        break;
                    }
                }
                if ($use) {
                    switch ($ident) {
                        case 'LastBoot':
                            $val = $now - $val;
                            break;
                        case 'MaxChargingCurrent':
                        case 'MaxSupportedCurrent':
                        case 'CurrentPhase1':
                        case 'CurrentPhase2':
                        case 'CurrentPhase3':
                        case 'ActivePower':
                        case 'ChargedEnergy':
                        case 'TotalEnergy':
                            $val = floatval($val) * 0.0001;
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
                break;
            }

            $this->SetValue('LastUpdate', $now);
            if ($is_changed) {
                $this->SetValue('LastChange', $now);
            }
            $this->DeleteAction();
            $this->Cycle();
        } else {
            $this->SendDebug(__FUNCTION__, 'broadcast message', 0);

            $now = time();
            $is_changed = false;

            foreach ($jdata as $var => $val) {
                $fnd = true;
                $ign = false;
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
                $use = false;
                foreach ($use_fields as $field) {
                    if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                        $use = (bool) $this->GetArrayElem($field, 'use', false);
                        break;
                    }
                }
                if ($use) {
                    switch ($ident) {
                        case 'MaxChargingCurrent':
                        case 'ChargedEnergy':
                            $val = floatval($val) * 0.0001;
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
            // wenn 'State' => "report 2" abrufen, ggfs schneller zyklus
            // $this->AddAction('report 2');
            // $this->Cycle();

            if ($is_changed) {
                $this->SetValue('LastChange', $now);
            }
        }
    }

    private function GetConnectionID()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $cID = $inst['ConnectionID'];
        return $cID;
    }

    public function GetConfigurationForParent()
    {
        $r = IPS_GetConfiguration($this->GetConnectionID());
        $this->SendDebug(__FUNCTION__, print_r($r, true), 0);
        $j = [
            'BindPort'          => 7090,
            'Port'              => 7090,
            'EnableBroadcast'   => true,
            'EnableReuseAddress'=> false,

        ];
        $d = json_encode($j);
        $this->SendDebug(__FUNCTION__, print_r($j, true), 0);
        return $d;
    }
}

/*

report 1
"ID"           = 1
"Product-ID"   = Model name (variant)
"Serial"       = Serial number
"Firmware"     = Firmware version
"COM-module”   = Communication module is installed
"Sec"          = Current system clock since restart of the charging station.

report 2
"ID"           = 2
"State"        = Current state of the charging station 0 : starting
"Error 1"      = Detail code for state 4; exceptions see FAQ on www.kecontact.com
"Error 2"      = Detail code for state 4 exception #6 see FAQ on www.kecontact.com
"Plug"         = Current condition of the loading connection
"Enable sys"   = Enable state for charging (contains Enable input, RFID, UDP,..).
"Enable user"  = Enable condition via UDP.
"Max curr"     = Current preset value via Control pilot in milliampere.
"Max curr %"   = Current preset value via Control pilot in 0,1% of the PWM value
"Curr HW"      = Highest possible charging current of the charging connection. Contains device maximum, DIP-switch setting, cable coding and temperature reduction.
"Curr user"    = Current preset value of the user via UDP; Default = 63000mA. = Current preset value for the Failsafe function.
"Curr FS"      = Current preset value for the Failsafe function.
"Tmo FS"       = Current preset value for the Failsafe function.
"Curr timer"   = Current preset value for the Failsafe function.
"Tmo CT"       = Shows the remaining time until the current value is accepted.
"Setenergy”    = Shows the set energy limit.
"Output"       = State of the output X2.
"Input"        = State of the potential free Enable input X1.
"Serial"       = Serial number
"Sec"          = Current system clock since restart of the charging station.

report 3
"ID"           = 3
"U1"|"U2"|"U3" = Current voltage in V.
"I1"|"I2"|"I3" = Current current value of the 3 phases in mA.
"P"            = Current power in mW (Real Power).
"PF"           = Power factor in 0,1% (cosphi)
"E pres"       = Power consumption of the current loading session in 0,1Wh; Reset with new loading session (state = 2).
"E total"      = Total power consumption (persistent) without current loading session 0,1Wh; Is summed up after each completed charging session (state = 0).
"Serial"       = Serial number


Fehler 1  Der Stecker wurde während des Ladevorgangs abgesteckt (weiß / weiß / weiß / rot)
Fehler 2  Der Stecker wurde nicht erkannt (weiß / weiß / rot / weiß)
Fehler 3  Temperaturabschaltung (weiß / weiß / rot / rot)
Fehler 4  Der Stecker konnte nicht verriegelt werden (weiß / rot / weiß / weiß)
Fehler 5  Die Stromladestation hat einen unzulässigen Verbraucher erkannt (weiß / rot / weiß / rot)
Fehler 8  Der Stecker an der Ladestation liefert einen ungültigen Zustand (rot / weiß / weiß / weiß)

Fehler 4003  Überstrom im Fahrzeug erkannt (blau / blau / rot / rot)
Fehler 8005  Fehlerstrom im Fahrzeug erkannt (blau / rot / blau / rot)

 */
