<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $searchBy = trim((string) $request->query('search_by', 'all'));
        $showDiscontinued = (bool) $request->boolean('show_discontinued', false);

        $query = Medicine::query()
            ->with(['manufacturer', 'packages'])
            ->orderBy('name')
            ->orderBy('strength');

        if (!$showDiscontinued) {
            $query->where('is_discontinued', false);
        }

        if ($search !== '') {
            $query->search($search, $searchBy);
        }

        $medicines = $query->paginate(50)->withQueryString();

        return view('pharmacy.medicines.index', compact('medicines', 'search', 'searchBy', 'showDiscontinued'));
    }

    public function clearAll()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            \App\Models\StockMovement::query()->delete();
            \App\Models\StockBatch::query()->delete();
            Medicine::query()->delete();
        });
        return redirect()->route('pharmacy.medicines.index')->with('success', 'All medicines and related stock have been removed.');
    }

    public function create()
    {
        return view('pharmacy.medicines.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'therapeutic_class' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $request->validate([
            'name' => [
                Rule::unique('medicines')->where(function ($q) use ($validated) {
                    return $q->where('name', $validated['name'])
                        ->where('strength', $validated['strength'] ?? null);
                }),
            ],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $medicine = Medicine::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'medicine_create',
            'entity_type' => 'medicine',
            'entity_id' => $medicine->id,
            'meta' => [
                'name' => $medicine->name,
                'brand_name' => $medicine->brand_name,
                'strength' => $medicine->strength,
            ],
        ]);

        return redirect()->route('pharmacy.medicines.index')->with('success', 'Medicine added.');
    }

    public function edit(Medicine $medicine)
    {
        return view('pharmacy.medicines.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'therapeutic_class' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $request->validate([
            'name' => [
                Rule::unique('medicines')
                    ->ignore($medicine->id)
                    ->where(function ($q) use ($validated) {
                        return $q->where('name', $validated['name'])
                            ->where('strength', $validated['strength'] ?? null);
                    }),
            ],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $medicine->update($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'medicine_update',
            'entity_type' => 'medicine',
            'entity_id' => $medicine->id,
            'meta' => [
                'name' => $medicine->name,
                'brand_name' => $medicine->brand_name,
                'strength' => $medicine->strength,
            ],
        ]);

        return redirect()->route('pharmacy.medicines.index')->with('success', 'Medicine updated.');
    }

    public function destroy(Medicine $medicine)
    {
        $id = $medicine->id;
        $name = $medicine->name;
        $strength = $medicine->strength;
        $medicine->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'medicine_delete',
            'entity_type' => 'medicine',
            'entity_id' => $id,
            'meta' => [
                'name' => $name,
                'strength' => $strength,
            ],
        ]);

        return redirect()->route('pharmacy.medicines.index')->with('success', 'Medicine deleted.');
    }
}
