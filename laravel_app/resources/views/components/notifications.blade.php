@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $unreadNotifications = $user ? $user->unreadNotifications : collect();
@endphp

@if(!$user)
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => 'relative hover:opacity-75 transition']) }}>
        <i class="fa-solid fa-bell text-xl"></i>
    </a>
@else
<div class="relative" x-data="{ notificationOpen: false }">
    <button @click="notificationOpen = !notificationOpen" @click.outside="notificationOpen = false" {{ $attributes->merge(['class' => 'relative hover:opacity-75 transition']) }}>
        <i class="fa-solid fa-bell text-xl"></i>
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold px-1.5 rounded-full animate-pulse">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="notificationOpen" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl overflow-hidden z-50 border border-gray-100"
         style="display: none;">
        
        <div class="p-3 bg-gray-50 border-b flex justify-between items-center">
            <span class="text-xs font-bold text-gray-700">Notifications</span>
            @if($unreadCount > 0)
                <form action="{{ route('notifications.markRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-[10px] text-blue-600 hover:text-blue-800 font-semibold">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse($unreadNotifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}" class="block p-3 hover:bg-gray-50 border-b last:border-0 transition group-item text-left">
                    <div class="flex gap-3">
                        <div class="mt-1">
                            @php
                                $typeColor = match($notification->data['type'] ?? 'info') {
                                    'success' => 'text-green-500 bg-green-50',
                                    'warning' => 'text-yellow-500 bg-yellow-50',
                                    'error' => 'text-red-500 bg-red-50',
                                    default => 'text-blue-500 bg-blue-50'
                                };
                            @endphp
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $typeColor }}">
                                <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-info-circle' }}"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-800 font-medium leading-snug">{{ $notification->data['message'] }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-4 text-center text-gray-400 text-xs">
                    No new notifications
                </div>
            @endforelse
        </div>
        
        <div class="p-2 bg-gray-50 border-t text-center">
            <a href="{{ route('notifications.index') }}" class="text-[10px] text-gray-500 hover:text-gray-700">View All History</a>
        </div>
    </div>
</div>
@endif
