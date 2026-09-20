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
        $this->SetVisualizationType(1);
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->SetVisualizationType(1);
    }

    public function GetVisualizationTile(): string
    {
        $file = __DIR__ . '/module.html';
        if (!is_file($file)) {
            return '<div>Visualization file not found</div>';
        }

        $content = file_get_contents($file);
        return $content === false ? '<div>Visualization file could not be loaded</div>' : $content;
    }

    public function ReceiveData($JSONString): void
    {
        $data = json_decode($JSONString, true);
        if (!is_array($data)) {
            return;
        }

        $payload = $data['Buffer'] ?? $data;
        if (!is_array($payload)) {
            return;
        }

        $parking = $payload['parkingPosition'] ?? $payload;
        $this->SetBuffer('parkingPosition', json_encode($parking));
        $this->UpdateVisualizationValue($parking);
    }

    public function UpdateData(): void
    {
        // Data processing logic for Skoda Parking
    }
}