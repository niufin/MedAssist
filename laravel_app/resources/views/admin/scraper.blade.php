@section('title', 'Scraper - Super Admin')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Medicine Scraper') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.scraper.run') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                            <input type="text" name="domain" value="{{ old('domain', '1mg.com') }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., 1mg.com" required>
                            <p class="text-xs text-gray-500 mt-1">Supported: {{ implode(', ', array_keys($supported)) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product URLs (one per line, optional)</label>
                            <textarea name="urls" rows="6" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="https://example.com/product-1&#10;https://example.com/product-2">{{ old('urls') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">If empty for 1mg.com, defaults to a few sample products.</p>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded shadow">Run Scraper</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($lastOutput)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-bold text-gray-800 mb-2">Last Output</h3>
                    <div class="flex items-center justify-between">
                        <code class="text-sm break-all">{{ base_path($lastOutput) }}</code>
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $items ?? 0 }} items</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

