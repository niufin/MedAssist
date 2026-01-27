<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedAssist - Interactive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .ui-frozen { pointer-events: none; opacity: 0.6; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes bounce-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .animate-bounce-in { animation: bounce-in 0.3s ease-out forwards; }
    </style>
</head>
    @php
        $activeModel = null;
        if (isset($session) && $session->chat_history) {
            $decoded = json_decode($session->chat_history, true) ?? [];
            for ($i = count($decoded) - 1; $i >= 0; $i--) {
                $row = $decoded[$i] ?? null;
                if (is_array($row) && ($row['role'] ?? null) === 'assistant') {
                    $activeModel = $row['model'] ?? null;
                    if ($activeModel && strtolower($activeModel) === 'system') {
                        $activeModel = 'SYSTEM';
                    }
                    break;
                }
            }
        }
        
        // Navigation Logic
        $user = Auth::user();
        $activeHospitalId = session('active_hospital_admin_id');
        $activeHospitalName = null;
        if ($user) {
            if ($user->isHospitalAdmin()) {
                $activeHospitalId = $user->id;
                $activeHospitalName = $user->name;
            } elseif ($user->isSuperAdmin() && $activeHospitalId) {
                $activeHospitalName = \App\Models\User::where('id', (int) $activeHospitalId)->value('name');
            }
        }
    @endphp
    <body class="bg-slate-100 h-screen flex flex-col font-sans overflow-hidden" 
      x-data="{ sidebarOpen: false, rightPanelOpen: false }"
      @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
      @toggle-right-panel.window="rightPanelOpen = !rightPanelOpen">
    
    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen || rightPanelOpen" 
         @click="sidebarOpen = false; rightPanelOpen = false"
         class="fixed inset-0 bg-black/50 z-30 lg:hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
    </div>

    @include('layouts.navigation_blue', ['has_sidebar' => true, 'has_right_panel' => true])
    @if(!empty($db_error))
    <div class="bg-red-50 text-red-700 text-xs px-4 py-2 border-b border-red-200">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $db_error }}
    </div>
    @endif
    @if(session('status'))
    <div class="bg-emerald-50 text-emerald-700 text-xs px-4 py-2 border-b border-emerald-200">
        <i class="fa-solid fa-circle-check mr-1"></i> {{ session('status') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-orange-50 text-orange-700 text-xs px-4 py-2 border-b border-orange-200">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ session('error') }}
    </div>
    @endif

    <div class="flex flex-1 overflow-hidden relative">
        
        <!-- Left Sidebar: Patient History -->
        <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 lg:z-auto w-72 bg-white border-r border-gray-200 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:w-72 shadow-2xl lg:shadow-md shrink-0 h-full">
            
            <div class="p-3 border-b border-gray-100 bg-slate-50 sticky top-0 z-10 flex justify-between items-center shrink-0">
                <form action="{{ route('dashboard') }}" method="GET" class="relative flex-1 mr-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 bg-white" onchange="this.form.submit()">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                </form>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="px-4 py-2 border-b bg-slate-50 font-bold text-gray-500 text-xs uppercase flex justify-between items-center shrink-0">
                <span><i class="fa-solid fa-clock-rotate-left mr-1"></i> Patient History</span>
                <div class="flex gap-1">
                    @if(auth()->user() && (auth()->user()->isDoctor() || auth()->user()->isHospitalAdmin() || auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()))
                    <a href="{{ route('consultations.backfill') }}" class="text-xs text-blue-500 hover:text-blue-700 hover:bg-blue-50 px-2 py-1 rounded transition font-bold" title="Backfill Patient IDs by Name"><i class="fa-solid fa-link"></i> Sync</a>
                    @endif
                    @if($history->count() > 0)
                    <form action="{{ route('consultations.destroyAll') }}" method="POST" onsubmit="return confirm('⚠️ WARNING: Delete ALL history?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded transition font-bold" title="Clear All"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="overflow-y-auto flex-1 p-2 space-y-2 custom-scrollbar">
                @if($history->isEmpty()) <p class="text-center text-xs text-gray-400 mt-10 italic">No patients found.</p> @endif
                @foreach($history as $record)
                <div class="group relative">
                    <a href="{{ route('dashboard', ['id' => $record->id]) }}" class="block p-3 rounded-lg border transition {{ isset($session) && $session->id == $record->id ? 'bg-blue-50 border-blue-400 shadow-sm' : 'bg-white border-gray-100 hover:bg-gray-50' }}">
                        <div class="flex justify-between items-start mb-1">
                            @php
                                $iconClass = 'fa-user text-gray-400'; 
                                $ageNum = (int) filter_var($record->patient_age, FILTER_SANITIZE_NUMBER_INT);
                                $genderLower = strtolower($record->patient_gender ?? '');
                                if ($ageNum > 0 && $ageNum < 13) $iconClass = 'fa-child text-green-500'; 
                                elseif (str_contains($genderLower, 'female') || str_contains($genderLower, 'woman')) $iconClass = 'fa-person-dress text-pink-500'; 
                                elseif (str_contains($genderLower, 'male') || str_contains($genderLower, 'man')) $iconClass = 'fa-person text-blue-500'; 
                            @endphp
                            <div class="flex items-center gap-2 overflow-hidden">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center shrink-0"><i class="fa-solid {{ $iconClass }} text-xs"></i></div>
                                <div class="truncate">
                                    <span class="text-xs font-bold text-gray-800 block truncate">{{ $record->patient_name ?? 'New Patient' }}</span>
                                    <span class="text-[10px] text-gray-400 block truncate">
                                        MRN: {{ $record->patient->mrn ?? $record->mrn ?? 'N/A' }} • {{ $record->patient_age }} • {{ $record->patient_gender }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[9px] font-mono text-gray-400 shrink-0 ml-1">{{ $record->created_at->format('M d') }}</span>
                        </div>
                    </a>
                    <form action="{{ route('consultation.destroy', $record->id) }}" method="POST" class="absolute top-2 right-2 lg:hidden lg:group-hover:block" onsubmit="return confirm('Delete this record?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-500 p-2 bg-white/90 rounded shadow-sm transition"><i class="fa-solid fa-trash-can text-sm"></i></button>
                    </form>
                </div>
                @endforeach
            </div>
            
            <!-- Mobile Logout (Sidebar Footer) -->
            <div class="p-4 border-t border-gray-200 lg:hidden">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="flex-1 flex flex-col bg-slate-100 relative min-w-0">
            <div class="flex-1 overflow-y-auto p-6 space-y-6" id="chat-container">
                @if(auth()->user()->isHospitalAdmin() && isset($summary) && is_array($summary))
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-sm font-bold text-gray-700 mb-3">Hospital Summary</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <div class="bg-blue-50 p-2 rounded-lg">
                            <p class="text-xs text-gray-500">Doctors</p>
                            <p class="text-lg font-bold text-blue-700">{{ $summary['doctors'] ?? 0 }}</p>
                        </div>
                        <div class="bg-green-50 p-2 rounded-lg">
                            <p class="text-xs text-gray-500">Patients</p>
                            <p class="text-lg font-bold text-green-700">{{ $summary['patients'] ?? 0 }}</p>
                        </div>
                        <div class="bg-purple-50 p-2 rounded-lg">
                            <p class="text-xs text-gray-500">Pharmacists</p>
                            <p class="text-lg font-bold text-purple-700">{{ $summary['pharmacists'] ?? 0 }}</p>
                        </div>
                        <div class="bg-orange-50 p-2 rounded-lg">
                            <p class="text-xs text-gray-500">Lab Assistants</p>
                            <p class="text-lg font-bold text-orange-700">{{ $summary['lab_assistants'] ?? 0 }}</p>
                        </div>
                        <div class="bg-indigo-50 p-2 rounded-lg">
                            <p class="text-xs text-gray-500">Consultations</p>
                            <p class="text-lg font-bold text-indigo-700">{{ $summary['consultations'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                @endif
                @if(isset($session) && $session->chat_history)
                    @foreach(json_decode($session->chat_history, true) as $msg)
                        <div class="flex {{ $msg['role'] == 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] flex flex-col items-start {{ $msg['role'] == 'user' ? 'items-end' : '' }}">
                                <div class="p-4 rounded-xl shadow-sm text-sm leading-relaxed {{ $msg['role'] == 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none border border-gray-200' }}">
                                    @if($msg['role'] == 'assistant')
                                        @php
                                            $rawModel = $msg['model'] ?? null;
                                            $label = $rawModel ?: 'SYSTEM';
                                            if (strtolower($label) === 'system') {
                                                $label = 'SYSTEM';
                                            }
                                        @endphp
                                        <div class="mb-2 flex items-center justify-between text-xs uppercase border-b border-gray-100 pb-1 w-full gap-4">
                                            <span class="font-bold text-blue-600 flex items-center gap-1"><i class="fa-solid fa-robot"></i> MedAssist</span>
                                            <span class="text-[9px] text-gray-400 font-normal tracking-wide bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100">{{ $label }}</span>
                                        </div>
                                        <div class="prose prose-sm max-w-none">{!! Str::markdown($msg['content']) !!}</div>
                                    @else
                                        <div class="whitespace-pre-wrap">{{ $msg['content'] }}</div>
                                    @endif
                                </div>
                                @if(isset($msg['sources']) && count($msg['sources']) > 0)
                                    <div class="mt-1 ml-1">
                                        <details class="group relative">
                                            <summary class="list-none cursor-pointer text-[10px] text-gray-400 hover:text-blue-600 transition flex items-center gap-1 select-none">
                                                <i class="fa-solid fa-book-medical"></i> <span>{{ count($msg['sources']) }} References</span> <i class="fa-solid fa-chevron-down text-[8px] group-open:rotate-180 transition"></i>
                                            </summary>
                                            <div class="mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-0 w-64 md:w-80 overflow-hidden z-10 absolute left-0">
                                                <div class="bg-gray-50 px-3 py-2 border-b border-gray-100 text-[10px] font-bold text-gray-500 uppercase tracking-wide">Evidence Used</div>
                                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                                    @foreach($msg['sources'] as $src)
                                                        <div class="p-3 border-b border-gray-50 last:border-0 hover:bg-blue-50 transition">
                                                            <p class="text-[10px] font-bold text-blue-800 truncate mb-1"><i class="fa-regular fa-file-pdf mr-1"></i> {{ $src['source'] }}</p>
                                                            <p class="text-[10px] text-gray-600 italic leading-snug">"...{{ Str::limit($src['content'], 90) }}..."</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60">
                        <div class="bg-white p-6 rounded-full shadow-sm mb-4"><i class="fa-solid fa-stethoscope text-5xl text-blue-200"></i></div>
                        <p class="text-lg font-medium text-gray-500">Ready for a new consultation</p>
                        <p class="text-xs text-gray-400 mt-2">Click "New Patient" to begin</p>
                    </div>
                @endif
            </div>

            <div class="p-4 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20">
                
                @if(isset($session) && in_array($session->status, ['asking_name','asking_age','asking_gender']))
                    <form action="{{ route('intake.submit') }}" method="POST" class="mb-4 bg-slate-50 border border-dashed border-blue-200 rounded-xl p-3 space-y-2" data-ai-loading="true" data-ai-title="Submitting intake form..." data-ai-subtitle="Summarizing patient details and starting AI review">
                        @csrf
                        <input type="hidden" name="consultation_id" value="{{ $session->id }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <h3 class="text-xs font-bold text-blue-800">Patient Intake Form</h3>
                            <span class="text-[10px] text-gray-500">Optional shortcut alongside chat</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <input type="text" name="patient_name" value="{{ old('patient_name', $session->patient_name) }}" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500" placeholder="Name">
                            <input type="text" name="patient_age" value="{{ old('patient_age', $session->patient_age) }}" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500" placeholder="Age">
                            @php
                                $genderValue = old('patient_gender', $session->patient_gender);
                            @endphp
                            <select name="patient_gender" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 bg-white">
                                <option value="">Gender</option>
                                <option value="Male" @if($genderValue === 'Male') selected @endif>Male</option>
                                <option value="Female" @if($genderValue === 'Female') selected @endif>Female</option>
                                <option value="Other" @if($genderValue === 'Other') selected @endif>Other</option>
                            </select>
                        </div>
                        <div class="mt-2">
                            @php
                                $symptomsValue = $session->symptoms === 'Pending Intake' ? '' : $session->symptoms;
                            @endphp
                            <textarea name="symptoms" rows="2" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 resize-y" placeholder="Key symptoms or chief complaints">{{ old('symptoms', $symptomsValue) }}</textarea>
                        </div>
                        <div class="mt-2 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm">
                                <i class="fa-solid fa-bolt"></i>
                                <span>Submit Intake Form</span>
                            </button>
                        </div>
                    </form>
                @endif
                
                @if(isset($session) && $session->labReports->count() > 0)
                    <div class="flex flex-wrap gap-2 mb-3">
                        <form action="{{ route('analyze.reports') }}" method="POST" class="flex-1 sm:flex-none" data-ai-loading="true" data-ai-title="Analyzing lab reports..." data-ai-subtitle="Summarizing key lab findings and abnormalities">
                            @csrf <input type="hidden" name="consultation_id" value="{{ $session->id }}">
                            <button type="submit" class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm text-xs flex items-center justify-center gap-2">
                                <i class="fa-solid fa-microscope"></i> Analyze Lab Reports
                            </button>
                        </form>
                    </div>
                @endif

                <form action="{{ route('chat.send') }}" method="POST" id="chat-form" class="flex flex-col gap-3">
                    @csrf
                    @if(isset($session)) <input type="hidden" name="consultation_id" value="{{ $session->id }}"> @endif
                    
                    <div class="w-full relative flex gap-2">
                        @if(isset($session) && ($session->status == 'consulting' || $session->status == 'finished'))
                            <button type="button" onclick="document.getElementById('report-upload').click()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl px-4 py-3 transition border border-gray-300 flex-shrink-0" title="Upload Report"><i class="fa-solid fa-paperclip"></i></button>
                        @endif
                        <textarea name="message" id="message-input" rows="2" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none bg-gray-50 text-gray-800 placeholder-gray-400 shadow-inner transition-all text-sm" placeholder="Type symptoms, answer questions, or enter 'Name, age, gender' to skip..."></textarea>
                    </div>
                    
                    <div class="flex flex-row items-center gap-2 overflow-x-auto pb-1">
                        <button type="submit" id="send-btn" name="mode" value="chat" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center justify-center gap-2 whitespace-nowrap"><i class="fa-regular fa-paper-plane"></i> Send</button>
                        
                        @if(isset($session) && $session->status == 'consulting')
                            <button type="submit" id="end-btn" name="mode" value="final" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-xl shadow-md hover:shadow-lg transition-all text-xs flex items-center justify-center gap-2 whitespace-nowrap" onclick="return confirm('Generate Final Diagnosis Report?')"><i class="fa-solid fa-file-medical"></i> End Consult</button>
                        @endif

                        @if(isset($session) && $session->status == 'finished')
                            <button type="submit" formaction="{{ route('chat.update_prescription') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center justify-center gap-2 whitespace-nowrap" title="Update Prescription based on new symptoms">
                                <i class="fa-solid fa-arrows-rotate"></i> Update Rx
                            </button>

                            <!-- Consolidated Action Buttons -->
                            <a href="{{ route('prescription.edit', $session->id) }}" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-3 rounded-xl shadow-md hover:shadow-lg transition-all text-xs flex items-center justify-center gap-1 whitespace-nowrap">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <a href="{{ route('prescription.preview', $session->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3 rounded-xl shadow-md hover:shadow-lg transition-all text-xs flex items-center justify-center gap-1 whitespace-nowrap">
                                <i class="fa-regular fa-eye"></i> Preview
                            </a>
                            <a href="{{ route('prescription.generate', $session->id) }}" class="no-loader bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3 rounded-xl shadow-md hover:shadow-lg transition-all text-xs flex items-center justify-center gap-1 whitespace-nowrap">
                                <i class="fa-solid fa-print"></i> PDF
                            </a>
                            @if(!empty($session->prescription_path))
                                <a href="{{ route('prescription.download', $session->id) }}" class="no-loader bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-3 rounded-xl shadow-md hover:shadow-lg transition-all text-xs flex items-center justify-center gap-1 whitespace-nowrap" download>
                                    <i class="fa-solid fa-file-arrow-down"></i> Saved
                                </a>
                            @endif
                        @endif
                    </div>
                </form>
                
                @if(isset($session))
                <form action="{{ route('upload.report') }}" method="POST" enctype="multipart/form-data" id="upload-form" data-ai-loading="true" data-ai-title="Reading uploaded reports..." data-ai-subtitle="Extracting text and preparing documents for analysis">
                    @csrf <input type="hidden" name="consultation_id" value="{{ $session->id }}">
                    <input
                        type="file"
                        name="reports[]"
                        id="report-upload"
                        class="hidden"
                        multiple
                        accept=".pdf, .jpg, .jpeg, .png, .bmp, .tiff"
                        onchange="document.getElementById('upload-overlay').classList.remove('hidden'); this.form.submit();">
                </form>
                @endif
            </div>
        </div>

        @if(isset($session))
        <!-- Right Sidebar: Lab Reports & Context -->
        <div :class="rightPanelOpen ? 'translate-x-0' : 'translate-x-full'" class="fixed inset-y-0 right-0 z-40 w-72 bg-white border-l border-gray-200 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:w-72 shadow-2xl lg:shadow-none shrink-0 h-full">
            
            <!-- Mobile Header for Right Panel -->
            <div class="lg:hidden p-3 border-b bg-slate-50 flex justify-between items-center shrink-0">
                <span class="font-bold text-gray-700 text-sm">Context & Reports</span>
                <button @click="rightPanelOpen = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 custom-scrollbar">
                <div class="p-4 border-b bg-slate-50 font-bold text-gray-500 text-xs uppercase flex items-center justify-between sticky top-0 z-10">
                    <span><i class="fa-solid fa-flask mr-1"></i> Lab Reports</span>
                    @if($session->labReports->count() > 0)
                    <span class="bg-purple-100 text-purple-700 px-1.5 rounded text-[9px]">{{ $session->labReports->count() }}</span>
                    @endif
                </div>
                <div class="p-4 space-y-3 border-b border-gray-100">
                    @if($session->labReports && $session->labReports->count() > 0)
                        @foreach($session->labReports as $report)
                        <div class="p-3 bg-white border border-gray-200 rounded-lg hover:border-purple-300 transition group relative">
                            <a href="{{ route('lab.report.view', $report->id) }}" target="_blank" class="block">
                                <p class="font-bold text-gray-800 truncate text-xs mb-1 group-hover:text-purple-600">
                                    <i class="fa-regular fa-file-pdf mr-1 text-red-500"></i> 
                                    {{ $report->notes ? Str::limit($report->notes, 20) : 'Lab Report' }}
                                </p>
                                <p class="text-[10px] text-gray-500">
                                    {{ $report->created_at->format('d M, h:i A') }}
                                </p>
                            </a>
                        </div>
                        @endforeach
                    @else
                        <p class="text-[10px] text-gray-400 italic">No lab reports attached.</p>
                    @endif
                </div>

                <div class="p-4 border-b bg-slate-50 font-bold text-gray-500 text-xs uppercase flex items-center justify-between sticky top-0 z-10">
                    <span><i class="fa-solid fa-user-tag mr-1"></i> Patient Link</span>
                </div>
                <div class="p-4 space-y-3 border-b border-gray-100">
                    <div class="text-[11px] text-gray-600">
                        @if($session->patient)
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="font-bold text-gray-800">{{ $session->patient->name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $session->patient->email }}</div>
                                    @if($session->patient->mrn)
                                        <div class="text-[10px] text-gray-400">MRN: {{ $session->patient->mrn }}</div>
                                    @endif
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-semibold">Linked</span>
                            </div>
                            <form action="{{ route('doctor.patients.new_consultation', $session->patient->id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-3 rounded-lg shadow-sm flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-plus"></i> New Consultation
                                </button>
                            </form>
                        @else
                            <div class="mb-2 text-[11px] text-gray-500">
                                Link this consultation to a registered patient account using email or MRN.
                            </div>
                        @endif
                    </div>
                    <form action="{{ route('consultations.attachPatient', $session->id) }}" method="POST" class="space-y-2">
                        @csrf
                        <input
                            type="text"
                            name="identifier"
                            class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500"
                            placeholder="Enter patient email or MRN"
                        >
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1.5 px-3 rounded-lg shadow-sm flex items-center justify-center gap-1"
                        >
                            <i class="fa-solid fa-link"></i>
                            <span>{{ $session->patient ? 'Update Link' : 'Attach Patient' }}</span>
                        </button>
                    </form>
                </div>

                <!-- EXISTING SEARCH SECTION -->
                <div class="p-4 border-b bg-slate-50 font-bold text-gray-500 text-xs uppercase flex items-center justify-between sticky top-0 z-10">
                    <span><i class="fa-solid fa-layer-group mr-1"></i> Latest Search</span>
                    <span class="bg-blue-100 text-blue-700 px-1.5 rounded text-[9px]">Live</span>
                </div>
                <div class="p-4 space-y-3">
                    @php
                        $allSources = $sidebar_sources ?? [];
                        if (!$allSources || count($allSources) === 0) {
                            $allSources = $session->ai_sources ? json_decode($session->ai_sources, true) : [];
                        }
                    @endphp
                    @foreach($allSources as $source)
                        <div class="p-3 bg-slate-50 border border-gray-200 rounded-lg hover:border-blue-300 transition group">
                            <p class="font-bold text-blue-900 truncate text-xs mb-1 group-hover:text-blue-600"><i class="fa-regular fa-file-pdf mr-1"></i> {{ $source['source'] ?? 'Unknown PDF' }}</p>
                            @if(!empty($source['content']))
                            <p class="text-[10px] text-gray-600 italic leading-relaxed">"...{{ Str::limit($source['content'], 120) }}..."</p>
                            @endif
                        </div>
                    @endforeach
                    @if(empty($allSources) || count($allSources) === 0)
                        <p class="text-[10px] text-gray-400 italic">No references yet</p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <div id="ai-overlay" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center animate-bounce-in max-w-sm w-full">
            <i class="fa-solid fa-robot text-5xl text-blue-600 mb-4 fa-beat-fade"></i>
            <h2 id="ai-overlay-title" class="text-xl font-bold text-gray-800 text-center">Talking to MedAssist...</h2>
            <p id="ai-overlay-subtitle" class="text-sm text-gray-500 mt-2 text-center mb-6">Processing data and generating recommendations</p>
            
            <!-- Steps Container -->
            <div id="ai-steps" class="w-full space-y-3">
                <!-- Step 1: Analyzing -->
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-blue-500" id="step-icon-1"></i>
                    </div>
                    <span class="font-medium" id="step-text-1">Analyzing input...</span>
                </div>
                <!-- Step 2: Context -->
                <div class="flex items-center gap-3 text-sm text-gray-400 opacity-50 transition-all duration-500" id="step-row-2">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <i class="fa-regular fa-circle text-gray-300" id="step-icon-2"></i>
                    </div>
                    <span class="font-medium" id="step-text-2">Retrieving medical context...</span>
                </div>
                <!-- Step 3: Generating -->
                <div class="flex items-center gap-3 text-sm text-gray-400 opacity-50 transition-all duration-500" id="step-row-3">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <i class="fa-regular fa-circle text-gray-300" id="step-icon-3"></i>
                    </div>
                    <span class="font-medium" id="step-text-3">Generating response...</span>
                </div>
            </div>
        </div>
    </div>

    <div id="upload-overlay" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center animate-bounce-in">
            <i class="fa-solid fa-file-waveform text-5xl text-blue-600 mb-4 fa-beat-fade"></i>
            <h2 class="text-xl font-bold text-gray-800">Reading Report...</h2>
            <p class="text-sm text-gray-500 mt-2">Extracting text via OCR Analysis</p>
        </div>
    </div>

    <script>
        const container = document.getElementById('chat-container');
        if(container) container.scrollTop = container.scrollHeight;
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const healthEl = document.getElementById('health-status');
        const aiModelBadge = document.getElementById('ai-model-badge');
        const aiModelTooltipText = document.getElementById('ai-model-tooltip-text');
        const aiOverlay = document.getElementById('ai-overlay');
        const aiOverlayTitle = document.getElementById('ai-overlay-title');
        const aiOverlaySubtitle = document.getElementById('ai-overlay-subtitle');
        
        // Helper to update step UI
        const setStepActive = (stepNum) => {
            const row = document.getElementById(`step-row-${stepNum}`);
            const icon = document.getElementById(`step-icon-${stepNum}`);
            const text = document.getElementById(`step-text-${stepNum}`);
            
            // Activate current row
            if (row) {
                row.classList.remove('opacity-50', 'text-gray-400');
                row.classList.add('opacity-100', 'text-gray-600');
            }
            // Set spinner
            if (icon) {
                icon.className = 'fa-solid fa-circle-notch fa-spin text-blue-500';
            }
            // Complete previous steps
            for (let i = 1; i < stepNum; i++) {
                const prevRow = document.getElementById(`step-row-${i}`); // might be undefined for step 1 if not wrapped
                const prevIcon = document.getElementById(`step-icon-${i}`);
                if (prevIcon) prevIcon.className = 'fa-solid fa-circle-check text-emerald-500';
            }
        };

        const resetSteps = () => {
            // Reset Step 1
            const icon1 = document.getElementById('step-icon-1');
            if (icon1) icon1.className = 'fa-solid fa-circle-notch fa-spin text-blue-500';
            
            // Reset Step 2 & 3
            [2, 3].forEach(i => {
                const row = document.getElementById(`step-row-${i}`);
                const icon = document.getElementById(`step-icon-${i}`);
                if (row) {
                    row.classList.add('opacity-50', 'text-gray-400');
                    row.classList.remove('opacity-100', 'text-gray-600');
                }
                if (icon) icon.className = 'fa-regular fa-circle text-gray-300';
            });
        };

        const showAiOverlay = (opts = {}) => {
            if (!aiOverlay) return;
            if (aiOverlayTitle && opts.title) {
                aiOverlayTitle.textContent = opts.title;
            }
            if (aiOverlaySubtitle && opts.subtitle) {
                aiOverlaySubtitle.textContent = opts.subtitle;
            }
            
            resetSteps();
            aiOverlay.classList.remove('hidden');
            aiOverlay.classList.add('flex');

            // Simulate progress for better UX
            setTimeout(() => setStepActive(2), 1500); // Activate Context retrieval after 1.5s
            setTimeout(() => setStepActive(3), 3500); // Activate Generation after 3.5s
        };
        if (form) {
            const lockInterface = (btn) => {
                const mode = btn && btn.name === 'mode' ? btn.value : null;
                if (mode === 'final') {
                    showAiOverlay({
                        title: 'Generating final diagnosis...',
                        subtitle: 'Summarizing findings and updating prescription'
                    });
                } else {
                    showAiOverlay({
                        title: 'Chatting with MedAssist...',
                        subtitle: 'Analyzing input and preparing a response'
                    });
                }
                if(input) { input.readOnly = true; input.classList.add('bg-gray-100', 'text-gray-500', 'cursor-not-allowed'); }
                if(btn) { btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>'; btn.classList.add('pointer-events-none', 'opacity-80'); }
                form.classList.add('ui-frozen');
            };
            form.addEventListener('submit', function(e) { lockInterface(e.submitter); });
            if(input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!form.classList.contains('ui-frozen') && sendBtn) sendBtn.click(); }
                });
                input.focus();
            }
        }
        const aiForms = document.querySelectorAll('form[data-ai-loading="true"]');
        aiForms.forEach(f => {
            f.addEventListener('submit', () => {
                const title = f.getAttribute('data-ai-title') || 'Analyzing with MedAssist...';
                const subtitle = f.getAttribute('data-ai-subtitle') || 'Processing data and generating recommendations';
                showAiOverlay({ title, subtitle });
            });
        });
        const updateBadge = (el, status, label) => {
            el.textContent = label + ': ' + status.toUpperCase();
            el.classList.remove('bg-white/10','border-white/20');
            if (status === 'up') { el.classList.add('bg-emerald-600','border-emerald-500'); el.classList.remove('bg-red-600','border-red-500','bg-yellow-600','border-yellow-500'); }
            else if (status === 'down') { el.classList.add('bg-red-600','border-red-500'); el.classList.remove('bg-emerald-600','border-emerald-500','bg-yellow-600','border-yellow-500'); }
            else { el.classList.add('bg-yellow-600','border-yellow-500'); el.classList.remove('bg-emerald-600','border-emerald-500','bg-red-600','border-red-500'); }
        };
        const pollHealth = async () => {
            if(!healthEl) return;
            try {
                const res = await fetch('{{ route('health.status') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Bad status');
                const data = await res.json();
                healthEl.classList.remove('hidden');
                Array.from(healthEl.querySelectorAll('span[data-key]')).forEach(s => {
                    const key = s.getAttribute('data-key');
                    if (key && key in data) updateBadge(s, data[key], key.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase()));
                });
                
                // Update Indexing Status
                const idxEl = document.getElementById('indexing-status');
                const idxText = document.getElementById('indexing-text');
                if (data.ai_indexing_state && data.ai_indexing_state.is_indexing) {
                    if(idxEl) {
                        idxEl.classList.remove('hidden');
                        if(idxText) idxText.textContent = data.ai_indexing_state.progress || 'Indexing...';
                    }
                } else {
                    if(idxEl) idxEl.classList.add('hidden');
                }

                if (aiModelTooltipText && (data.ai_backend || data.ai_doc_chunks !== null && data.ai_doc_chunks !== undefined)) {
                    const backend = data.ai_backend || '';
                    const chunks = data.ai_doc_chunks;
                    let tooltip = '';
                    if (backend) {
                        tooltip += 'Backend: ' + String(backend).toUpperCase();
                    }
                    if (chunks !== null && chunks !== undefined) {
                        tooltip += (tooltip ? ' • ' : '') + 'Context chunks: ' + chunks;
                    }
                    if (data.ai_indexing_state) {
                        tooltip += (tooltip ? ' • ' : '') + 'Files: ' + (data.ai_indexing_state.total_files || 0);
                    }
                    aiModelTooltipText.textContent = tooltip || 'Backend information not available.';
                }
            } catch (e) {
                healthEl.classList.remove('hidden');
                Array.from(healthEl.querySelectorAll('span[data-key]')).forEach(s => updateBadge(s, 'down', s.getAttribute('data-key') || 'Service'));
            }
        };
        pollHealth();
        setInterval(pollHealth, 8000);
    </script>
</body>
</html>
