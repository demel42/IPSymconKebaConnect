<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php'; // globale Funktionen
require_once __DIR__ . '/../libs/local.php';  // lokale Funktionen

class KeConnectP30 extends IPSModule
{
    use KebaConnectCommonLib;
    use KebaConnectLocalLib;

    public static $Variables = [
        [
            'Index'           => 1000,
            'Ident'           => 'ChargingState',
            'Desc'            => 'Charging state',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.ChargingState',
        ],
        [
            'Index'           => 1004,
            'Ident'           => 'CableState',
            'Desc'            => 'Cable state',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.CableState',
        ],
        [
            'Index'           => 1006,
            'Ident'           => 'ErrorCode',
            'Desc'            => 'Error code',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_INTEGER,
            'VariableProfile' => 'KebaConnect.Error',
        ],

        [
            'Index'           => 1008,
            'Ident'           => 'CurrentPhase1',
            'Desc'            => 'Charging current phase 1',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1010,
            'Ident'           => 'CurrentPhase2',
            'Desc'            => 'Charging current phase 2',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1012,
            'Ident'           => 'CurrentPhase3',
            'Desc'            => 'Charging current phase 3',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1040,
            'Ident'           => 'VoltagePhase1',
            'Desc'            => 'Voltage phase 1',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],
        [
            'Index'           => 1042,
            'Ident'           => 'VoltagePhase2',
            'Desc'            => 'Voltage phase 2',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],
        [
            'Index'           => 1044,
            'Ident'           => 'VoltagePhase3',
            'Desc'            => 'Voltage phase 3',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Voltage',
        ],

        [
            'Index'           => 1020,
            'Ident'           => 'ActivePower',
            'Desc'            => 'Active power',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Power',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1036,
            'Ident'           => 'TotalEnergy',
            'Desc'            => 'Total energy',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Energy',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1100,
            'Ident'           => 'MaxChargingCurrent',
            'Desc'            => 'Max charging current',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1100,
            'Ident'           => 'MaxSupportedCurrent',
            'Desc'            => 'Max supported current',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Current',
            'Factor'          => 0.0001,
        ],
        [
            'Index'           => 1046,
            'Ident'           => 'PowerFactor',
            'Desc'            => 'Power factor',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Factor',
        ],

        [
            'Index'           => 1500,
            'Ident'           => 'RFID',
            'Desc'            => 'RFID card',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Index'           => 1502,
            'Ident'           => 'ChargedEnergy',
            'Desc'            => 'Charged energy',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_FLOAT,
            'VariableProfile' => 'KebaConnect.Energy',
            'Factor'          => 0.0001,
        ],

        [
            'Index'           => 1016,
            'Ident'           => 'ProductType',
            'Desc'            => 'Product type',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Index'           => 1014,
            'Ident'           => 'SerialNumber',
            'Desc'            => 'Serial number',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
        [
            'Index'           => 1018,
            'Ident'           => 'FirmwareVersion',
            'Desc'            => 'Firmware version',
            'Datatype'        => 'UINT32',
            'VariableType'    => VARIABLETYPE_STRING,
        ],
    ];

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);
        $this->RegisterPropertyInteger('update_interval', '60');

        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterTimer('UpdateData', 0, 'KebaConnect_UpdateData(' . $this->InstanceID . ');');

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
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('ok'), 'Farbe' => -1];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('Error 0x%05x'), 'Farbe' => -1];
        $this->CreateVarProfile('KebaConnect.Error', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $this->RequireParent('{A5F663AB-C400-4FE5-B207-4D67CC030564}');
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
            'caption' => 'KEBA KeConnect P30'
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => 'Update data every X seconds'
        ];
        $formElements[] = [
            'type'    => 'IntervalBox',
            'name'    => 'update_interval',
            'caption' => 'Seconds'
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

    protected function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->SetTimerInterval('UpdateData', $msec);
    }

    private function GetVariable($Index, $Datatype, $VariableType)
    {
        switch ($Datatype) {
            case 'UINT32':
                $n_bytes = 4;
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown datatype ' . $Datatype . ' for variable ' . print_r($var, true), 0);
                break;
            }
        $data = [
            'DataID'   => '{E310B701-4AE7-458E-B618-EC13A1A6F6A8}',
            'Function' => 3, /* READ */
            'Address'  => $Index,
            'Quantity' => $n_bytes,
            'Data'     => '',
        ];
        $jdata = json_encode($data);
        $this->SendDebug(__FUNCTION__, 'request data ' . print_r($jdata, true), 0);
        $data = $this->SendDataToParent($jdata);
        $data = substr($data, 2);
        $this->SendDebug(__FUNCTION__, 'raw data "' . $data . '"', 0);

        $val = null;
        switch ($Datatype) {
        case 'UINT32':
            $val = unpack('N', $data)[1];
            switch ($VariableType) {
                case VARIABLETYPE_BOOLEAN:
                    $val = $val != 0;
                    break;
                case VARIABLETYPE_INTEGER:
                    break;
                case VARIABLETYPE_FLOAT:
                    $val = floatval($val);
                    break;
                case VARIABLETYPE_STRING:
                    $this->SendDebug(__FUNCTION__, 'val=' . $val, 0);
                    $val = strval($val);
                    break;
            }
            break;
        default:
            $this->SendDebug(__FUNCTION__, 'unknown datatype ' . $Datatype . ' for variable ' . print_r($var, true), 0);
            break;
        }
        return $val;
    }

    private function FormatVariable($ident, $val)
    {
        switch ($ident) {
            case 'RFID':
                if ($val == '0') {
                    $val = '';
                }
                break;
            case 'FirmwareVersion':
                $s = dechex(intval($val));
                $major = hexdec(substr($s, 0, 1));
                $minor = hexdec(substr($s, 1, 2));
                $patch = hexdec(substr($s, 3, 2));
                $val = sprintf('%d.%d.%d', $major, $minor, $patch);
                break;
            case 'ProductType':
                $s = '';
                switch (substr($val, 0, 1)) {
                case '3':
                    $s .= 'KC-P30';
                    break;
                }
                switch (substr($val, 3, 1)) {
                case '0':
                    $s .= '-C';
                    break;
                case '1':
                    $s .= '-X';
                    break;
                }
                switch (substr($val, 2, 1)) {
                case '1':
                    $s .= ' 13A';
                    break;
                case '2':
                    $s .= ' 16A';
                    break;
                case '3':
                    $s .= ' 20A';
                    break;
                case '4':
                    $s .= ' 32A';
                    break;
                }
                $val = $s;
                break;
            default:
                break;
        }
        return $val;
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

        $now = time();
        $is_changed = false;

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
            if ($use) {
                $index = $var['Index'];
                $ident = $var['Ident'];
                $dtype = $var['Datatype'];
                $vartype = $var['VariableType'];
                $val = $this->GetVariable($index, $dtype, $vartype);
                if (is_null($val)) {
                    continue;
                }
                $val = $this->FormatVariable($ident, $val);
                if (isset($var['Factor'])) {
                    $val *= floatval($var['Factor']);
                }
                $this->SendDebug(__FUNCTION__, 'variable ' . $ident . ' with value ' . $val, 0);
                $this->SaveValue($ident, $val, $is_changed);
            }
        }

        $this->SetValue('LastUpdate', $now);
        if ($is_changed) {
            $this->SetValue('LastChange', $now);
        }

        $prod = $this->GetVariable(1016, 'UINT32', VARIABLETYPE_STRING);
        $serialNo = $this->GetVariable(1014, 'UINT32', VARIABLETYPE_STRING);
        $s = $this->FormatVariable('ProductType', $prod) . ' (#' . $serialNo . ')';
        $this->SetSummary($s);
    }
}
