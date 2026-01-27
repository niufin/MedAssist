<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Stock Movements') }}
            </h2>
            <a href="{{ route('pharmacy.inventory.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($movements as $mv)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $mv->created_at->format('d M Y, h:i A') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $mv->medicine?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $mv->movement_type }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold {{ $mv->quantity >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $mv->quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $mv->reference_type ? $mv->reference_type . ':' . $mv->reference_id : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        {{ $movements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

