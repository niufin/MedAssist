<tr class="border-b hover:bg-gray-50 med-row">
    <td class="p-2 relative">
        @php
            $composition = $med['composition_name'] ?? $med['name'] ?? '';
        @endphp
        <div class="space-y-2">
            <input type="text" name="medicines[{{ $idx }}][composition_name]" value="{{ $composition }}" class="w-full p-2 border rounded composition-input" placeholder="Composition (enter directly), e.g. Paracetamol" autocomplete="off">
            <div class="relative">
                <input type="text"
                       name="medicines[{{ $idx }}][brand_name]"
                       value="{{ $med['brand_name'] ?? '' }}"
                       class="w-full p-2 border rounded brand-input"
                       placeholder="Brand (optional) – click to select"
                       onfocus="openBrandModal(this)"
                       onclick="openBrandModal(this)"
                       autocomplete="off">
                <input type="hidden" name="medicines[{{ $idx }}][brand_medicine_id]" value="{{ $med['brand_medicine_id'] ?? '' }}" class="brand-medicine-id">
                <input type="hidden" name="medicines[{{ $idx }}][brand_strength]" value="{{ $med['brand_strength'] ?? '' }}" class="brand-strength">
                <input type="hidden" name="medicines[{{ $idx }}][brand_dosage_form]" value="{{ $med['brand_dosage_form'] ?? '' }}" class="brand-dosage-form">
                <input type="hidden" name="medicines[{{ $idx }}][brand_composition_text]" value="{{ $med['brand_composition_text'] ?? '' }}" class="brand-composition-text">
            </div>
        </div>
    </td>
    <td class="p-2"><input type="text" name="medicines[{{ $idx }}][dosage]" value="{{ $med['dosage'] ?? '' }}" class="w-full p-2 border rounded" placeholder="500mg"></td>
    <td class="p-2"><input type="text" name="medicines[{{ $idx }}][frequency]" value="{{ $med['frequency'] ?? '' }}" class="w-full p-2 border rounded" placeholder="1-0-1"></td>
    <td class="p-2"><input type="text" name="medicines[{{ $idx }}][duration]" value="{{ $med['duration'] ?? '' }}" class="w-full p-2 border rounded" placeholder="3 days"></td>
    <td class="p-2"><input type="text" name="medicines[{{ $idx }}][instruction]" value="{{ $med['instruction'] ?? $med['instructions'] ?? '' }}" class="w-full p-2 border rounded" placeholder="After food"></td>
    <td class="p-2 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600"><i class="fa-solid fa-trash"></i></button></td>
</tr>
