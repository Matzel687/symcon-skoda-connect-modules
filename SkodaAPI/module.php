<?php

declare(strict_types=1);

class SkodaAPI extends IPSModule
{
    public function Create(): void
    {
        parent::Create();

        // Register Properties
        $this->RegisterPropertyString('APIKey', '');
        $this->RegisterPropertyString('VIN', '');
        $this->RegisterPropertyInteger('UpdateInterval', 300);
        $this->RegisterPropertyBoolean('SaveRenderUrlLocally', true);
        $this->RegisterPropertyBoolean('IncludeInfo', true);
        $this->RegisterPropertyBoolean('IncludeStatus', true);
        $this->RegisterPropertyBoolean('IncludeFuelStatus', true);
        $this->RegisterPropertyBoolean('IncludeOdometer', true);
        $this->RegisterPropertyBoolean('IncludeParkingPosition', true);
        $this->RegisterPropertyBoolean('IncludeAirConditioning', true);
        $this->RegisterPropertyBoolean('IncludeAuxiliaryHeating', true);
        $this->RegisterPropertyBoolean('IncludeActiveVentilation', true);
        $this->RegisterPropertyBoolean('IncludeCharging', true);
        $this->RegisterPropertyBoolean('IncludeChargingProfiles', true);

        // Timer for polling
        $this->RegisterTimer('UpdateDataTimer', 0, 'SKODA_UpdateData($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('UpdateDataTimer', $interval * 1000);
    }

    public function UpdateData(): void
    {
        // Data processing logic for Skoda API
    }
}