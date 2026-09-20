<?php

declare(strict_types=1);

class SkodaStatus extends IPSModule
{
    public function Create(): void
    {
        parent::Create();

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

        $status = $payload['status'] ?? $payload;
        $this->SetBuffer('status', json_encode($status));
        $this->UpdateVisualizationValue($status);
    }

    public function UpdateData(): void
    {
        // Data processing logic for Skoda Status
    }
}