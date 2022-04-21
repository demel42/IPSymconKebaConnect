<?php

declare(strict_types=1);

trait KebaConnectLocalLib
{
    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

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

    public function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $this->CreateVarProfile('KebaConnect.Current', VARIABLETYPE_FLOAT, ' A', 0, 0, 0, 1, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Power', VARIABLETYPE_FLOAT, ' kW', 0, 0, 0, 2, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Energy', VARIABLETYPE_FLOAT, ' kWh', 0, 0, 0, 2, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.Voltage', VARIABLETYPE_FLOAT, ' V', 0, 0, 0, 0, '', '', $reInstall);
        $this->CreateVarProfile('KebaConnect.PowerFactor', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 1, '', '', $reInstall);

        $this->CreateVarProfile('KebaConnect.MaxCurrent', VARIABLETYPE_INTEGER, ' A', 0, 0, 0, 0, '', '', $reInstall);

        $associations = [
            ['Wert' => self::$STATE_SYSTEM_STARTED, 'Name' => $this->Translate('system started'), 'Farbe' => -1],
            ['Wert' => self::$STATE_NOTREADY, 'Name' => $this->Translate('not ready for charging'), 'Farbe' => -1],
            ['Wert' => self::$STATE_READY, 'Name' => $this->Translate('ready for charging'), 'Farbe' => -1],
            ['Wert' => self::$STATE_CHARGING, 'Name' => $this->Translate('charging'), 'Farbe' => -1],
            ['Wert' => self::$STATE_ERROR, 'Name' => $this->Translate('error occured'), 'Farbe' => -1],
            ['Wert' => self::$STATE_SUSPENDED, 'Name' => $this->Translate('charging suspended'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.ChargingState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [
            ['Wert' => self::$CABLE_NOT_PLUGGED, 'Name' => $this->Translate('not plugged'), 'Farbe' => -1],
            ['Wert' => self::$CABLE_PLUGGED_IN_STATION, 'Name' => $this->Translate('plugged in station'), 'Farbe' => -1],
            ['Wert' => self::$CABLE_LOCKED_IN_STATION, 'Name' => $this->Translate('locked in station'), 'Farbe' => -1],
            ['Wert' => self::$CABLE_PLUGGED_IN_VEHICLE, 'Name' => $this->Translate('plugged in vehicle'), 'Farbe' => -1],
            ['Wert' => self::$CABLE_LOCKED_IN_VEHICLE, 'Name' => $this->Translate('locked in vehicle'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.CableState', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [
            ['Wert' => 0, 'Name' => $this->Translate('no error'), 'Farbe' => -1],
            ['Wert' => 1, 'Name' => $this->Translate('Error 0x%05x'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.Error', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations, $reInstall);

        $associations = [
            ['Wert' => 0, 'Name' => $this->Translate('no limit'), 'Farbe' => -1],
            ['Wert' => 1, 'Name' => $this->Translate('%0.0f kWh'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.EnergyLimit', VARIABLETYPE_FLOAT, '', 0, 100, 1, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('no'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('yes'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.EnableCharging', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('-'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('unlock'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.UnlockPlug', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('disconnected'), 'Farbe' => -1],
            ['Wert' => true, 'Name' => $this->Translate('connected'), 'Farbe' => -1],
        ];
        $this->CreateVarProfile('KebaConnect.ComBackend', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);
    }
}
