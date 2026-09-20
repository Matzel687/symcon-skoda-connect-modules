<?php

declare(strict_types=1);

class SkodaParking extends IPSModule
{
    public function Create(): void
    {
        parent::Create();

        // Register Properties
        $this->RegisterPropertyBoolean('EnableVarParkState', true);
        $this->RegisterPropertyBoolean('EnableVarAddress', true);
        $this->RegisterPropertyBoolean('EnableVarLatitude', true);
        $this->RegisterPropertyBoolean('EnableVarLongitude', true);
        $this->RegisterPropertyBoolean('EnableVarCapturedTimestamp', true);

        $this->ConnectParent('{A5129F78-831C-409B-B12D-9B1D2C345671}');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

    }

    public function UpdateData(): void
    {
        // Data processing logic for Skoda Parking
    }
}