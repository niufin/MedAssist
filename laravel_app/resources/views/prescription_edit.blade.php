<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prescription - {{ $c->patient_name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .suggestion-list { position: absolute; background: white; border: 1px solid #ddd; width: 100%; max-height: 150px; overflow-y: auto; z-index: 50; }
        .suggestion-item { padding: 8px; cursor: pointer; font-size: 12px; }
        .suggestion-item:hover { background-color: #f0f9ff; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    @include('layouts.navigation_blue')

    <div class="p-2 sm:p-6">
        <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-blue-900 text-white p-3 sm:p-4 flex justify-between items-center">
                <h1 class="text-lg sm:text-xl font-bold"><i class="fa-solid fa-file-prescription mr-2"></i> Edit Prescription</h1>
                <a href="{{ route('dashboard', ['id' => $c->id]) }}" class="text-blue-200 hover:text-white bg-blue-800 px-3 py-1 rounded text-sm font-bold flex-shrink-0"><i class="fa-solid fa-arrow-left mr-1"></i> <span class="hidden sm:inline">Back</span><span class="inline sm:hidden">Back</span></a>
            </div>

        <form action="{{ route('prescription.update', $c->id) }}" method="POST" class="p-3 sm:p-6">
            @csrf
            
            <!-- Patient Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6 bg-slate-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Patient Name</label>
                    <p class="font-bold text-gray-800">{{ $c->patient_name }}</p>
                    @if($c->patient && $c->patient->mrn)
                        <p class="text-[10px] text-gray-500">MRN: {{ $c->patient->mrn }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Age / Gender</label>
                    <p class="font-bold text-gray-800">{{ $c->patient_age }} / {{ $c->patient_gender }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Date</label>
                    <p class="font-bold text-gray-800">{{ now()->format('d M, Y') }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Assign Pharmacist</label>
                    <select name="pharmacist_id" class="w-full mt-1 p-1 border border-gray-300 rounded text-sm bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="">-- All Pharmacists --</option>
                        @foreach($pharmacists as $ph)
                            <option value="{{ $ph->id }}" {{ $c->pharmacist_id == $ph->id ? 'selected' : '' }}>{{ $ph->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Diagnosis -->
            <div class="mb-4">
                 <label class="block text-sm font-bold text-gray-700 mb-1">Final Diagnosis</label>
                 <input type="text" name="diagnosis" value="{{ $data['diagnosis'] ?? '' }}" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 font-bold text-blue-900" placeholder="e.g. Acute Viral Fever">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- LEFT COLUMN: Clinical Notes -->
                <div class="md:col-span-1 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Clinical Notes (Symptoms, Vitals)</label>
                        <textarea name="clinical_notes" rows="8" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-mono bg-yellow-50" placeholder="C/O: Fever...&#10;O/E: BP 120/80...">{{ $data['clinical_notes'] ?? '' }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Investigations Needed</label>
                        <textarea name="investigations" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-mono bg-blue-50" placeholder="1. CBC&#10;2. X-Ray Chest">{{ $data['investigations'] ?? '' }}</textarea>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Medicines -->
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-gray-700 mb-3 border-b pb-2">Rx - Medicines (NLEM Standard)</h2>
                    
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm text-left text-gray-600 border border-gray-200 rounded-lg">
                            <thead class="bg-gray-100 uppercase text-gray-500 text-xs">
                                <tr>
                                    <th class="px-4 py-3">Composition + Brand (Optional)</th>
                                    <th class="px-4 py-3 w-24">Dosage</th>
                                    <th class="px-4 py-3 w-24">Freq</th>
                                    <th class="px-4 py-3 w-24">Duration</th>
                                    <th class="px-4 py-3 w-32">Instructions</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="med-table">
                                @if(isset($data['medicines']) && count($data['medicines']) > 0)
                                    @foreach($data['medicines'] as $idx => $med)
                                        @include('partials.med_row', ['idx' => $idx, 'med' => $med])
                                    @endforeach
                                @else
                                    <tr id="empty-row"><td colspan="6" class="p-4 text-center italic text-gray-400">No medicines added yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                        <button type="button" onclick="addRow()" class="mt-2 bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded text-sm font-bold transition"><i class="fa-solid fa-plus"></i> Add Medicine</button>
                    </div>

                    <!-- Advice -->
                    <h2 class="text-lg font-bold text-gray-700 mb-3 border-b pb-2">General Advice & Investigations</h2>
                    <textarea name="advice" rows="5" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm mb-6" placeholder="Rest, Diet, Follow-up...">{{ $data['advice'] ?? '' }}</textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-4 border-t pt-4 mt-4">
                <a href="{{ route('dashboard', ['id' => $c->id]) }}" class="text-gray-500 hover:text-gray-700 font-bold py-3 px-4 text-center">Cancel</a>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-save"></i> Save & Print PDF
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Hidden Template for JS -->
    <template id="row-template">
        <tr class="border-b hover:bg-gray-50 med-row">
            <td class="p-2 relative">
                <div class="space-y-2">
                    <input type="text" name="medicines[{i}][composition_name]" class="w-full p-2 border rounded composition-input" placeholder="Composition (enter directly), e.g. Paracetamol" autocomplete="off">
                    <div class="relative">
                        <input type="text"
                               name="medicines[{i}][brand_name]"
                               class="w-full p-2 border rounded brand-input"
                               placeholder="Brand (optional) – click to select"
                               onfocus="openBrandModal(this)"
                               onclick="openBrandModal(this)"
                               autocomplete="off">
                        <input type="hidden" name="medicines[{i}][brand_medicine_id]" class="brand-medicine-id">
                        <input type="hidden" name="medicines[{i}][brand_strength]" class="brand-strength">
                        <input type="hidden" name="medicines[{i}][brand_dosage_form]" class="brand-dosage-form">
                        <input type="hidden" name="medicines[{i}][brand_composition_text]" class="brand-composition-text">
                    </div>
                </div>
            </td>
            <td class="p-2"><input type="text" name="medicines[{i}][dosage]" class="w-full p-2 border rounded" placeholder="500mg"></td>
            <td class="p-2"><input type="text" name="medicines[{i}][frequency]" class="w-full p-2 border rounded" placeholder="1-0-1"></td>
            <td class="p-2"><input type="text" name="medicines[{i}][duration]" class="w-full p-2 border rounded" placeholder="3 days"></td>
            <td class="p-2"><input type="text" name="medicines[{i}][instruction]" class="w-full p-2 border rounded" placeholder="After food"></td>
            <td class="p-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-3 rounded-lg transition shadow-sm border border-red-100">
                    <i class="fa-solid fa-trash-can text-lg"></i>
                </button>
            </td>
        </tr>
    </template>

    <x-modal name="brand-picker" maxWidth="2xl">
        <div class="p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-lg font-bold text-gray-900">Select Brand</div>
                    <div id="brandPickerSubtitle" class="text-xs text-gray-500 mt-1"></div>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700 font-bold" onclick="closeBrandModal()">×</button>
            </div>

            <div class="mt-4">
                <input id="brandPickerQuery" type="text" class="w-full border-gray-300 rounded shadow-sm text-sm" placeholder="Filter brands / company...">
            </div>

            <div id="brandPickerStatus" class="mt-3 text-xs text-gray-500 hidden"></div>

            <div id="brandPickerResults" class="mt-3 border border-gray-200 rounded max-h-[60vh] overflow-auto"></div>

            <div class="mt-3 flex justify-end">
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-sm" onclick="closeBrandModal()">Close</button>
            </div>
        </div>
    </x-modal>

    <script>
        const medicinesDB = @json($medicines_db ?? []);
        let rowCount = {{ isset($data['medicines']) ? count($data['medicines']) : 0 }};

        function addRow() {
            document.getElementById('empty-row')?.remove();
            const template = document.getElementById('row-template').innerHTML;
            const newRow = template.replace(/{i}/g, rowCount++);
            document.getElementById('med-table').insertAdjacentHTML('beforeend', newRow);
        }

        function normalizeMedicineSearchText(text) {
            const t = (text || '').toString().trim();
            if (!t) return '';
            const cleaned = t
                .replace(/\s+/g, ' ')
                .replace(/^(tab|tablet|cap|capsule|syr|syrup|inj|injection|drop|drops|crm|cream|oint|ointment|gel|soln|solution|susp|suspension)\.?\s+/i, '');
            return cleaned.trim();
        }

        const brandPicker = {
            activeRow: null,
            baseComposition: '',
            mode: 'composition',
            searchTerm: '',
            items: [],
            page: 1,
            perPage: 50,
            hasMore: false,
            loading: false,
            query: '',
            typingTimer: null,
        };

        function openBrandModal(input) {
            const row = input?.closest('tr');
            if (!row) return;
            const composition = (row.querySelector('.composition-input')?.value || '').trim();
            const base = normalizeMedicineSearchText(composition);
            brandPicker.activeRow = row;
            brandPicker.baseComposition = base;
            brandPicker.mode = 'composition';
            brandPicker.searchTerm = '';
            brandPicker.page = 1;
            brandPicker.items = [];
            brandPicker.hasMore = false;
            brandPicker.query = '';

            const subtitle = document.getElementById('brandPickerSubtitle');
            if (subtitle) {
                subtitle.textContent = base ? `Composition: ${base}` : 'Enter composition to load relevant brands.';
            }

            const q = document.getElementById('brandPickerQuery');
            if (q) {
                q.value = '';
                setTimeout(() => q.focus(), 50);
            }

            const results = document.getElementById('brandPickerResults');
            if (results) results.scrollTop = 0;

            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'brand-picker' }));
            if (base) {
                fetchBrandPage(true);
            } else {
                renderBrandPicker();
            }
        }

        function closeBrandModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'brand-picker' }));
        }

        async function fetchBrandPage(reset) {
            if (brandPicker.loading) return;
            if (!brandPicker.baseComposition) return;
            if (!reset && !brandPicker.hasMore) return;

            brandPicker.loading = true;
            if (reset) {
                brandPicker.page = 1;
                brandPicker.items = [];
                brandPicker.hasMore = false;
            }
            renderBrandPicker();

            try {
                const baseUrl = '/api/medicines';
                const params = new URLSearchParams();
                params.set('only_brands', '1');
                params.set('page', String(brandPicker.page));
                params.set('per_page', String(brandPicker.perPage));
                if (brandPicker.mode === 'brand') {
                    params.set('q', brandPicker.searchTerm);
                    params.set('search_by', 'brand');
                    params.set('composition', brandPicker.baseComposition);
                } else {
                    params.set('q', brandPicker.baseComposition);
                    params.set('search_by', 'composition');
                }

                const url = `${baseUrl}?${params.toString()}`;
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) throw new Error('network');
                const data = await resp.json();
                const items = Array.isArray(data.items) ? data.items : [];
                brandPicker.hasMore = !!data.has_more;
                brandPicker.page = brandPicker.page + 1;
                brandPicker.items = brandPicker.items.concat(items);
            } catch (e) {
                brandPicker.hasMore = false;
            } finally {
                brandPicker.loading = false;
                renderBrandPicker();
            }
        }

        function renderBrandPicker() {
            const status = document.getElementById('brandPickerStatus');
            const results = document.getElementById('brandPickerResults');
            if (!results) return;

            const parts = [];
            if (!brandPicker.baseComposition) {
                if (status) {
                    status.classList.remove('hidden');
                    status.textContent = 'Enter composition to load relevant brands.';
                }
                results.innerHTML = '';
                return;
            }

            if (status) {
                status.classList.remove('hidden');
                const total = brandPicker.items.length;
                const modeText = brandPicker.mode === 'brand' ? `Search: ${brandPicker.searchTerm}` : 'All relevant brands';
                const hint = brandPicker.mode === 'composition' && normalizeMedicineSearchText(brandPicker.query).length > 0 && normalizeMedicineSearchText(brandPicker.query).length < 2
                    ? ' • type 2+ letters to search'
                    : '';
                status.textContent = brandPicker.loading
                    ? `Loading... (${total} loaded) • ${modeText}${hint}`
                    : `${total} loaded${brandPicker.hasMore ? ' • scroll to load more' : ''} • ${modeText}${hint}`;
            }

            const list = brandPicker.items;
            if (!list.length && !brandPicker.loading) {
                results.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No brands found.</div>';
                return;
            }

            list.forEach(m => {
                const brand = ((m.brand_label_clean || m.brand_label || m.brand_name || m.name) || '').trim();
                if (!brand) return;
                const comp = (m.composition_text || '').trim();
                const strength = (m.strength || '').trim();
                const form = (m.dosage_form || '').trim();
                const company = (m.manufacturer || '').trim();
                const rack = (m.rack_location || '').trim();
                const metaParts = [];
                if (comp) metaParts.push(comp);
                if (strength) metaParts.push(strength);
                if (form) metaParts.push(form);
                if (company) metaParts.push(company);
                if (rack) metaParts.push(`<span class="bg-yellow-100 text-yellow-800 px-1 rounded font-mono">📍 ${rack}</span>`);
                const meta = metaParts.length ? `<div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-2">${metaParts.join(' • ')}</div>` : '';
                parts.push(`<button type="button" class="w-full text-left px-3 py-2 hover:bg-blue-50 border-b border-gray-100" data-mid="${m.id || ''}" data-brand="${encodeURIComponent(brand)}" data-strength="${encodeURIComponent(strength)}" data-form="${encodeURIComponent(form)}" data-comp="${encodeURIComponent(comp)}"><div class="text-sm font-semibold text-gray-900 flex justify-between"><span>${brand}</span>${rack ? `<span class="text-xs font-mono bg-yellow-100 text-yellow-800 px-1 rounded ml-2">📍 ${rack}</span>` : ''}</div>${meta}</button>`);
            });

            results.innerHTML = parts.join('');
        }

        function onBrandPicked(btn) {
            const row = brandPicker.activeRow;
            if (!row) return;
            const brand = decodeURIComponent(btn.getAttribute('data-brand') || '');
            const strength = decodeURIComponent(btn.getAttribute('data-strength') || '');
            const form = decodeURIComponent(btn.getAttribute('data-form') || '');
            const comp = decodeURIComponent(btn.getAttribute('data-comp') || '');
            const id = btn.getAttribute('data-mid') || '';

            const brandInput = row.querySelector('.brand-input');
            if (brandInput) brandInput.value = brand;
            row.querySelector('.brand-medicine-id').value = id;
            row.querySelector('.brand-strength').value = strength;
            row.querySelector('.brand-dosage-form').value = form;
            row.querySelector('.brand-composition-text').value = comp;

            const dosageInput = row.querySelector('input[name*="[dosage]"]');
            if (dosageInput && !dosageInput.value && strength) {
                dosageInput.value = strength;
            }
            const compInput = row.querySelector('.composition-input');
            if (compInput && !compInput.value && comp) {
                compInput.value = comp;
            }

            closeBrandModal();
        }

        document.addEventListener('click', function (e) {
            const btn = e.target?.closest('#brandPickerResults button[data-brand]');
            if (btn) {
                onBrandPicked(btn);
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target && e.target.id === 'brandPickerQuery') {
                brandPicker.query = e.target.value || '';
                if (brandPicker.typingTimer) clearTimeout(brandPicker.typingTimer);
                brandPicker.typingTimer = setTimeout(() => {
                    const term = normalizeMedicineSearchText(brandPicker.query);
                    if (term.length >= 2) {
                        if (brandPicker.mode !== 'brand' || brandPicker.searchTerm !== term) {
                            brandPicker.mode = 'brand';
                            brandPicker.searchTerm = term;
                            fetchBrandPage(true);
                        }
                        return;
                    }
                    if (brandPicker.mode === 'brand') {
                        brandPicker.mode = 'composition';
                        brandPicker.searchTerm = '';
                        fetchBrandPage(true);
                        return;
                    }
                    renderBrandPicker();
                }, 150);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const results = document.getElementById('brandPickerResults');
            if (results) {
                results.addEventListener('scroll', function () {
                    if (!brandPicker.hasMore || brandPicker.loading) return;
                    if (results.scrollTop + results.clientHeight >= results.scrollHeight - 120) {
                        fetchBrandPage(false);
                    }
                });
            }
        });
    </script>
</body>
</html>
