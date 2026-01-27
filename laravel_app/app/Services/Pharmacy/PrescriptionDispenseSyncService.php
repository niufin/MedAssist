<?php

namespace App\Services\Pharmacy;

use App\Models\Consultation;
use App\Models\DispenseItem;
use App\Models\DispenseOrder;
use App\Models\Medicine;
use App\Models\PharmacyStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class PrescriptionDispenseSyncService
{
    private function normalizeMedicineKey(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/i', '', $name) ?? '';
        return $name;
    }

    public function syncForConsultation(Consultation $consultation): void
    {
        $consultation->loadMissing('doctor');

        $hospitalAdminId = $consultation->doctor?->hospital_admin_id;
        if (empty($hospitalAdminId)) {
            return;
        }

        $pData = $consultation->prescription_data;
        $meds = is_array($pData) ? ($pData['medicines'] ?? []) : [];
        if (!is_array($meds) || empty($meds)) {
            return;
        }

        $rxByKey = [];
        foreach ($meds as $m) {
            if (!is_array($m)) {
                continue;
            }
            $name = trim((string) ($m['name'] ?? ($m['brand_name'] ?? ($m['composition_name'] ?? ''))));
            if ($name === '') {
                continue;
            }
            $key = $this->normalizeMedicineKey($name);
            if ($key === '') {
                continue;
            }
            $rxByKey[$key] = [
                'name' => $name,
                'dosage' => $m['dosage'] ?? null,
                'frequency' => $m['frequency'] ?? null,
                'duration' => $m['duration'] ?? null,
                'instruction' => $m['instruction'] ?? null,
            ];
        }

        if (empty($rxByKey)) {
            return;
        }

        DB::transaction(function () use ($consultation, $hospitalAdminId, $rxByKey) {
            $store = PharmacyStore::firstOrCreate(
                ['hospital_admin_id' => (int) $hospitalAdminId],
                ['name' => 'Pharmacy']
            );

            $orders = DispenseOrder::where('pharmacy_store_id', $store->id)
                ->where('consultation_id', $consultation->id)
                ->with('items')
                ->orderByDesc('id')
                ->get();

            if ($orders->isEmpty()) {
                return;
            }

            $existingKeys = [];
            foreach ($orders as $o) {
                foreach ($o->items as $it) {
                    $key = $this->normalizeMedicineKey((string) $it->medicine_name);
                    if ($key !== '') {
                        $existingKeys[$key] = true;
                    }
                }
            }

            $newKeys = array_values(array_diff(array_keys($rxByKey), array_keys($existingKeys)));
            if (empty($newKeys)) {
                return;
            }

            $openOrder = $orders->firstWhere('status', 'open');
            if ($openOrder) {
                $this->insertDispenseItems($openOrder->id, $rxByKey, $newKeys);
                return;
            }

            $hasDispensedOrder = $orders->contains(fn (DispenseOrder $o) => $o->status === 'dispensed');
            if (!$hasDispensedOrder) {
                return;
            }

            try {
                $newOrder = DispenseOrder::create([
                    'pharmacy_store_id' => $store->id,
                    'consultation_id' => $consultation->id,
                    'patient_id' => $consultation->patient_id,
                    'doctor_id' => $consultation->doctor_id,
                    'pharmacist_id' => $consultation->pharmacist_id,
                    'status' => 'open',
                ]);
            } catch (QueryException $e) {
                $msg = $e->getMessage();
                if (($e->getCode() === '23000' || $e->getCode() === 23000) && str_contains($msg, 'dispense_orders_pharmacy_store_id_consultation_id_unique')) {
                    return;
                }
                throw $e;
            }

            $this->insertDispenseItems($newOrder->id, $rxByKey, $newKeys);
        });
    }

    private function insertDispenseItems(int $orderId, array $rxByKey, array $newKeys): void
    {
        $now = now();
        $rows = [];

        foreach ($newKeys as $key) {
            $m = $rxByKey[$key] ?? null;
            if (!$m) {
                continue;
            }

            $name = (string) ($m['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $medicine = Medicine::where('name', $name)
                ->orWhere('brand_name', $name)
                ->first();

            $rows[] = [
                'dispense_order_id' => $orderId,
                'medicine_id' => $medicine?->id,
                'medicine_name' => $name,
                'dosage' => $m['dosage'] ?? null,
                'frequency' => $m['frequency'] ?? null,
                'duration' => $m['duration'] ?? null,
                'instruction' => $m['instruction'] ?? null,
                'quantity' => 1,
                'dispensed_quantity' => 0,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DispenseItem::insert($rows);
        }
    }
}
