<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notification History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if($notifications->count() > 0)
                        <div class="flex justify-end mb-4">
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <form action="{{ route('notifications.markRead') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">Mark all as read</button>
                                </form>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <div class="flex items-start p-4 border rounded-lg hover:bg-gray-50 transition {{ $notification->read_at ? 'bg-white' : 'bg-blue-50 border-blue-200' }}">
                                    <div class="flex-shrink-0 mr-4">
                                        @php
                                            $typeColor = match($notification->data['type'] ?? 'info') {
                                                'success' => 'text-green-500 bg-green-100',
                                                'warning' => 'text-yellow-500 bg-yellow-100',
                                                'error' => 'text-red-500 bg-red-100',
                                                default => 'text-blue-500 bg-blue-100'
                                            };
                                        @endphp
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $typeColor }}">
                                            <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-info-circle' }}"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-sm font-semibold text-gray-900">
                                                {{ $notification->data['message'] ?? 'Notification' }}
                                            </h4>
                                            <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">
                                            @if(isset($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" class="text-blue-600 hover:underline">
                                                    View Details <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-10 text-gray-500">
                            <i class="fa-regular fa-bell-slash text-4xl mb-3 text-gray-300"></i>
                            <p>No notifications found.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
