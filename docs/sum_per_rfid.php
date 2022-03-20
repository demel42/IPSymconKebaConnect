<?php

declare(strict_types=1);

$parID = 99999; // Kategorie/Dummy-Instanz, unterhalb die Summen-Variablen liegen sollen

$archivID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; // Archiv-Instanz

$varL = [];
$instIDs = IPS_GetInstanceListByModuleID('{A84E350B-55B7-2841-A6F1-C0B17FA0C4CD}'); // KeConnectP30udp-Instanzen
foreach ($instIDs as $instID) {
    foreach (IPS_GetChildrenIDs($instID) as $id) {
        $obj = IPS_GetObject($id);
        if ($obj['ObjectType'] != 2 /* OBJECTTYPE_VARIABLE */) {
            continue;
        }
        if (preg_match('/^ChargedEnergy_(.*)$/', $obj['ObjectIdent'], $r)) {
            $rfid = $r[1];
            $ids = isset($varL[$rfid]) ? $varL[$rfid] : [];
            $ids[] = $id;
            $varL[$rfid] = $ids;
        }
    }
}

foreach ($varL as $rfid => $ids) {
    $ident = 'ChargedEnergy_' . $rfid;
    @$varID = IPS_GetObjectIDByIdent($ident, $parID);
    if ($varID == false) {
        $varID = IPS_CreateVariable(2 /* VARIABLETYPE_FLOAT */);
        IPS_SetParent($varID, $parID);
        IPS_SetVariableCustomProfile($varID, 'KebaConnect.Energy');
        IPS_SetName($varID, 'Gesamtverbrauch RFID ' . $rfid);
        IPS_SetIdent($varID, $ident);
        AC_SetLoggingStatus($archivID, $varID, true);
        AC_SetAggregationType($archivID, $varID, 1 /* Zähler */);
    }
    $val = 0;
    foreach ($ids as $id) {
        $val += GetValueFloat($id);
    }
    if (GetValueFloat($varID) != $val) {
        SetValueFloat($varID, $val);
    }
}
