<?php

namespace App\Services;

use App\Models\PrinterConfig;
use Illuminate\Database\Eloquent\Collection;

class PrinterConfigService
{
    public function create(array $data): PrinterConfig
    {
        return PrinterConfig::create($data);
    }

    public function update(int $id, array $data): PrinterConfig
    {
        $printer = PrinterConfig::findOrFail($id);
        $printer->update($data);

        return $printer;
    }

    public function delete(int $id): bool
    {
        $printer = PrinterConfig::findOrFail($id);

        return $printer->delete();
    }

    public function getByStore(int $storeId): Collection
    {
        return PrinterConfig::forStore($storeId)->get();
    }

    public function getActiveByStore(int $storeId): Collection
    {
        return PrinterConfig::forStore($storeId)->active()->get();
    }

    public function getDefault(int $storeId): ?PrinterConfig
    {
        return PrinterConfig::forStore($storeId)->default()->first();
    }

    public function setDefault(int $id): PrinterConfig
    {
        $printer = PrinterConfig::findOrFail($id);

        // Unset default for other printers in the same store
        PrinterConfig::forStore($printer->store_id)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        $printer->update(['is_default' => true]);

        return $printer;
    }

    public function testConnection(int $id): array
    {
        $printer = PrinterConfig::findOrFail($id);

        // Simulate connection test
        // In real implementation, this would use printer driver
        $isConnected = $this->simulateConnectionTest($printer);

        return [
            'success' => $isConnected,
            'message' => $isConnected ? 'Printer connected successfully' : 'Failed to connect to printer',
            'printer' => $printer->name,
            'connection_type' => $printer->connection_type,
            'address' => $printer->address,
        ];
    }

    private function simulateConnectionTest(PrinterConfig $printer): bool
    {
        // Simulate connection test based on connection type
        // In production, this would use actual printer drivers
        return match ($printer->connection_type) {
            'usb' => true, // Assume USB is connected
            'bluetooth' => rand(0, 10) > 2, // 80% success rate for demo
            'network' => $this->isValidIp($printer->address),
            default => false,
        };
    }

    private function isValidIp(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false;
    }
}
