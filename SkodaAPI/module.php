<?php

declare(strict_types=1);

class SkodaAPI extends IPSModule
{
    private const API_BASE_URL = 'https://public.api.connect.skoda-auto.cz/api/v1';

    public function Create(): void
    {
        parent::Create();

        // API Configuration Properties
        $this->RegisterPropertyString('APIKey', '');
        $this->RegisterPropertyString('VIN', '');
        $this->RegisterPropertyInteger('UpdateInterval', 300);

        // Feature & Include Toggles
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

        // Variable Creation Flags (Register variables only if enabled in config)
        $this->RegisterPropertyBoolean('EnableVarAirConState', true);
        $this->RegisterPropertyBoolean('EnableVarTargetTemp', true);
        $this->RegisterPropertyBoolean('EnableVarSoC', true);
        $this->RegisterPropertyBoolean('EnableVarBatteryCareSoC', true);
        $this->RegisterPropertyBoolean('EnableVarRangeKm', true);
        $this->RegisterPropertyBoolean('EnableVarChargePower', true);
        $this->RegisterPropertyBoolean('EnableVarChargeRate', true);
        $this->RegisterPropertyBoolean('EnableVarMaxCurrentAmpere', true);
        $this->RegisterPropertyBoolean('EnableVarChargeState', true);
        $this->RegisterPropertyBoolean('EnableVarOdometer', true);

        // Register Dynamic Variable Profiles
        $this->RegisterCustomProfiles();

        // Enable custom HTML visualization
        $this->SetVisualizationType(1);

        // Setup Polling Timer
        $this->RegisterTimer('UpdateDataTimer', 0, 'SKODA_UpdateData($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('UpdateDataTimer', max(30, $interval) * 1000);

        // Conditionally maintain status variables according to configuration
        $this->MaintainVariables();

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

    private function RegisterCustomProfiles(): void
    {
        // SKODA.Percent
        if (!IPS_VariableProfileExists('SKODA.Percent')) {
            IPS_CreateVariableProfile('SKODA.Percent', 1); // Integer
            IPS_SetVariableProfileText('SKODA.Percent', '', ' %');
            IPS_SetVariableProfileValues('SKODA.Percent', 0, 100, 1);
            IPS_SetVariableProfileIcon('SKODA.Percent', 'Battery');
        }

        // SKODA.Temperature
        if (!IPS_VariableProfileExists('SKODA.Temperature')) {
            IPS_CreateVariableProfile('SKODA.Temperature', 2); // Float
            IPS_SetVariableProfileText('SKODA.Temperature', '', ' °C');
            IPS_SetVariableProfileValues('SKODA.Temperature', 14.0, 30.0, 0.5);
            IPS_SetVariableProfileIcon('SKODA.Temperature', 'Temperature');
        }

        // SKODA.DistanceKm
        if (!IPS_VariableProfileExists('SKODA.DistanceKm')) {
            IPS_CreateVariableProfile('SKODA.DistanceKm', 2); // Float
            IPS_SetVariableProfileText('SKODA.DistanceKm', '', ' km');
            IPS_SetVariableProfileValues('SKODA.DistanceKm', 0, 2000, 1);
            IPS_SetVariableProfileIcon('SKODA.DistanceKm', 'Distance');
        }

        // SKODA.ChargingRate
        if (!IPS_VariableProfileExists('SKODA.ChargingRate')) {
            IPS_CreateVariableProfile('SKODA.ChargingRate', 2); // Float
            IPS_SetVariableProfileText('SKODA.ChargingRate', '', ' km/h');
            IPS_SetVariableProfileValues('SKODA.ChargingRate', 0, 200, 0.1);
            IPS_SetVariableProfileIcon('SKODA.ChargingRate', 'Speedometer');
        }

        // SKODA.MaxChargeCurrentAcAmpere
        if (!IPS_VariableProfileExists('SKODA.MaxChargeCurrentAcAmpere')) {
            IPS_CreateVariableProfile('SKODA.MaxChargeCurrentAcAmpere', 1); // Integer
            IPS_SetVariableProfileText('SKODA.MaxChargeCurrentAcAmpere', '', ' A');
            IPS_SetVariableProfileValues('SKODA.MaxChargeCurrentAcAmpere', 5, 32, 1);
            IPS_SetVariableProfileIcon('SKODA.MaxChargeCurrentAcAmpere', 'Electricity');
        }

        // SKODA.AirConState
        if (!IPS_VariableProfileExists('SKODA.AirConState')) {
            IPS_CreateVariableProfile('SKODA.AirConState', 3); // String
            IPS_SetVariableProfileIcon('SKODA.AirConState', 'Climate');
        }

        // SKODA.ChargingState
        if (!IPS_VariableProfileExists('SKODA.ChargingState')) {
            IPS_CreateVariableProfile('SKODA.ChargingState', 3); // String
            IPS_SetVariableProfileIcon('SKODA.ChargingState', 'Plug');
        }

        // SKODA.Odometer
        if (!IPS_VariableProfileExists('SKODA.Odometer')) {
            IPS_CreateVariableProfile('SKODA.Odometer', 1); // Integer
            IPS_SetVariableProfileText('SKODA.Odometer', '', ' km');
            IPS_SetVariableProfileIcon('SKODA.Odometer', 'Gauge');
        }
    }

    private function MaintainVariables(): void
    {
        $this->MaintainVariable('AirConState', 'Klimatisierung Status', 3, 'SKODA.AirConState', 10, $this->ReadPropertyBoolean('EnableVarAirConState'));
        $this->MaintainVariable('TargetTemperature', 'Soll-Temperatur', 2, 'SKODA.Temperature', 11, $this->ReadPropertyBoolean('EnableVarTargetTemp'));
        $this->MaintainVariable('StateOfCharge', 'Batterie Ladezustand', 1, 'SKODA.Percent', 20, $this->ReadPropertyBoolean('EnableVarSoC'));
        $this->MaintainVariable('BatteryCareModeTarget', 'Battery Care Zielwert', 1, 'SKODA.Percent', 21, $this->ReadPropertyBoolean('EnableVarBatteryCareSoC'));
        $this->MaintainVariable('CruisingRangeKm', 'Reichweite', 2, 'SKODA.DistanceKm', 22, $this->ReadPropertyBoolean('EnableVarRangeKm'));
        $this->MaintainVariable('ChargePowerKw', 'Ladeleistung', 2, '~Power', 23, $this->ReadPropertyBoolean('EnableVarChargePower'));
        $this->MaintainVariable('ChargingRateKmH', 'Ladegeschwindigkeit', 2, 'SKODA.ChargingRate', 24, $this->ReadPropertyBoolean('EnableVarChargeRate'));
        $this->MaintainVariable('MaxChargeCurrentAmpere', 'Max. Ladestrom AC', 1, 'SKODA.MaxChargeCurrentAcAmpere', 25, $this->ReadPropertyBoolean('EnableVarMaxCurrentAmpere'));
        $this->MaintainVariable('ChargingState', 'Ladezustand Status', 3, 'SKODA.ChargingState', 26, $this->ReadPropertyBoolean('EnableVarChargeState'));
        $this->MaintainVariable('OdometerKm', 'Kilometerstand', 1, 'SKODA.Odometer', 30, $this->ReadPropertyBoolean('EnableVarOdometer'));
    }

    public function UpdateData(): void
    {
        $apiKey = $this->ReadPropertyString('APIKey');
        $vin = $this->ReadPropertyString('VIN');

        if (empty($apiKey) || empty($vin)) {
            $this->SetStatus(201); // Missing configuration
            return;
        }

        $includes = $this->GetActiveIncludes();
        $endpoint = sprintf('%s/vehicles/%s?include=%s', self::API_BASE_URL, $vin, implode(',', $includes));

        $response = $this->FetchFromAPI($endpoint, 'GET');
        if ($response !== null) {
            $this->SetStatus(102);
            $this->ProcessVehicleResponse($response);
        }
    }

    public function ProcessVehicleResponse(array $data): void
    {
        $vehicle = $data['vehicle'] ?? [];

        // 1. AirConditioning
        if (isset($vehicle['airConditioning'])) {
            $ac = $vehicle['airConditioning'];
            if ($this->ReadPropertyBoolean('EnableVarAirConState') && isset($ac['state'])) {
                $this->SetValue('AirConState', (string)$ac['state']);
            }
            if ($this->ReadPropertyBoolean('EnableVarTargetTemp') && isset($ac['targetTemperature']['value'])) {
                $this->SetValue('TargetTemperature', (float)$ac['targetTemperature']['value']);
            }
        }

        // 2. Charging & Battery
        if (isset($vehicle['charging'])) {
            $ch = $vehicle['charging'];
            if (isset($ch['settings'])) {
                $st = $ch['settings'];
                if ($this->ReadPropertyBoolean('EnableVarBatteryCareSoC') && isset($st['batteryCareModeTargetValueInPercent'])) {
                    $this->SetValue('BatteryCareModeTarget', (int)$st['batteryCareModeTargetValueInPercent']);
                }
                if ($this->ReadPropertyBoolean('EnableVarMaxCurrentAmpere') && isset($st['maxChargeCurrentAcAmpere'])) {
                    $this->SetValue('MaxChargeCurrentAmpere', (int)$st['maxChargeCurrentAcAmpere']);
                }
            }
            if (isset($ch['status'])) {
                $cs = $ch['status'];
                if (isset($cs['battery'])) {
                    if ($this->ReadPropertyBoolean('EnableVarRangeKm') && isset($cs['battery']['remainingCruisingRangeInMeters'])) {
                        $this->SetValue('CruisingRangeKm', (float)($cs['battery']['remainingCruisingRangeInMeters'] / 1000.0));
                    }
                    if ($this->ReadPropertyBoolean('EnableVarSoC') && isset($cs['battery']['stateOfChargeInPercent'])) {
                        $this->SetValue('StateOfCharge', (int)$cs['battery']['stateOfChargeInPercent']);
                    }
                }
                if ($this->ReadPropertyBoolean('EnableVarChargePower') && isset($cs['chargePowerInKw'])) {
                    $this->SetValue('ChargePowerKw', (float)$cs['chargePowerInKw']);
                }
                if ($this->ReadPropertyBoolean('EnableVarChargeRate') && isset($cs['chargingRateInKilometersPerHour'])) {
                    $this->SetValue('ChargingRateKmH', (float)$cs['chargingRateInKilometersPerHour']);
                }
                if ($this->ReadPropertyBoolean('EnableVarChargeState') && isset($cs['state'])) {
                    $this->SetValue('ChargingState', (string)$cs['state']);
                }
            }
        }

        // 3. Odometer
        if (isset($vehicle['odometer']['mileageInKm']) && $this->ReadPropertyBoolean('EnableVarOdometer')) {
            $this->SetValue('OdometerKm', (int)$vehicle['odometer']['mileageInKm']);
        }

        // 4. Forward routing arrays to dedicated Submodules
        $this->BroadcastToChildren($vehicle);

        // 5. Push the full vehicle payload into the HTML visualization
        $this->UpdateVisualizationValue(json_encode($vehicle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function StartCharging(): bool
    {
        return $this->ExecuteRemoteAction('startCharging');
    }

    public function StopCharging(): bool
    {
        return $this->ExecuteRemoteAction('stopCharging');
    }

    public function SetChargingLimit(int $limitPercent): bool
    {
        return $this->ExecuteRemoteAction('setChargingLimit', ['targetStateOfChargeInPercent' => $limitPercent]);
    }

    public function SetChargeMode(string $mode): bool
    {
        return $this->ExecuteRemoteAction('setChargeMode', ['chargeMode' => $mode]);
    }

    public function StartAirConditioning(): bool
    {
        return $this->ExecuteRemoteAction('startAirConditioning');
    }

    public function StopAirConditioning(): bool
    {
        return $this->ExecuteRemoteAction('stopAirConditioning');
    }

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'TargetTemperature':
                $this->SetValue($Ident, $Value);
                break;
            case 'BatteryCareModeTarget':
                $this->SetChargingLimit((int)$Value);
                break;
            case 'StartCharging':
                $this->StartCharging();
                break;
            case 'StopCharging':
                $this->StopCharging();
                break;
            case 'SetChargeLimit':
                $this->SetChargingLimit((int)$Value);
                break;
            case 'SetChargeMode':
                $this->SetChargeMode((string)$Value);
                break;
            case 'StartAirConditioning':
                $this->StartAirConditioning();
                break;
            case 'StopAirConditioning':
                $this->StopAirConditioning();
                break;
            default:
                $this->SendDebug('RequestAction', 'Unhandled Ident: ' . $Ident, 0);
                break;
        }
    }

    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString, true);
        $action = $data['action'] ?? '';
        $payload = $data['payload'] ?? [];

        $result = $this->ExecuteRemoteAction($action, $payload);
        return json_encode(['success' => $result]);
    }

    private function ExecuteRemoteAction(string $action, array $payload = []): bool
    {
        $vin = $this->ReadPropertyString('VIN');
        $endpoint = sprintf('%s/vehicles/%s/operations/%s', self::API_BASE_URL, $vin, $action);

        $response = $this->FetchFromAPI($endpoint, 'POST', $payload);
        return $response !== null;
    }

    private function GetActiveIncludes(): array
    {
        $map = [
            'IncludeInfo' => 'info',
            'IncludeStatus' => 'status',
            'IncludeFuelStatus' => 'fuelStatus',
            'IncludeOdometer' => 'odometer',
            'IncludeParkingPosition' => 'parkingPosition',
            'IncludeAirConditioning' => 'airConditioning',
            'IncludeAuxiliaryHeating' => 'auxiliaryHeating',
            'IncludeActiveVentilation' => 'activeVentilation',
            'IncludeCharging' => 'charging',
            'IncludeChargingProfiles' => 'chargingProfiles'
        ];

        $includes = [];
        foreach ($map as $property => $includeKey) {
            if ($this->ReadPropertyBoolean($property)) {
                $includes[] = $includeKey;
            }
        }
        return $includes;
    }

    private function FetchFromAPI(string $url, string $method, ?array $body = null): ?array
    {
        $apiKey = $this->ReadPropertyString('APIKey');
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'X-API-Key: ' . $apiKey,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($result, true) ?? [];
        }

        $this->SendDebug('API Error', sprintf('HTTP %d: %s', $httpCode, (string)$result), 0);
        return null;
    }

    private function BroadcastToChildren(array $vehicleData): void
    {
        $this->SendDataToChildren(json_encode([
            'DataID' => '{A5129F78-831C-409B-B12D-9B1D2C345671}',
            'Buffer' => $vehicleData
        ]));
    }
}