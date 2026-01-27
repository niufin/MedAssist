<!DOCTYPE html>
<html>
<head>
    <title>Prescription - {{ $c->patient_name }}</title>
    <style>
        @page { margin: 15px; }
        body { font-family: 'Helvetica', sans-serif; padding: 0; color: #2d3748; background-color: #fff; line-height: 1.3; font-size: 12px; }
        
        .header { text-align: center; border-bottom: 2px solid #3182ce; padding: 10px; margin-bottom: 10px; background-color: #f7fafc; }
        .clinic-name { font-size: 22px; font-weight: 800; color: #2c5282; text-transform: uppercase; letter-spacing: 1px; }
        .doctor-name { font-size: 12px; color: #4a5568; margin-top: 3px; font-weight: 500; }
        
        .container { padding: 0 15px; }

        .meta-table { width: 100%; margin-bottom: 10px; background: #fff; border-collapse: separate; border-spacing: 0; }
        .meta-table td { padding: 4px 0; border-bottom: 1px solid #edf2f7; }
        .label { font-weight: 700; color: #718096; font-size: 10px; text-transform: uppercase; }
        .value { font-weight: 600; color: #2d3748; font-size: 12px; }

        /* Two Column Layout */
        .main-layout { width: 100%; }
        .col-left { width: 30%; vertical-align: top; padding-right: 15px; border-right: 1px dashed #cbd5e0; }
        .col-right { width: 70%; vertical-align: top; padding-left: 15px; }

        .notes-section { font-size: 11px; color: #4a5568; white-space: pre-line; line-height: 1.4; }
        .notes-header { font-size: 11px; font-weight: 700; color: #2c5282; text-transform: uppercase; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }

        .rx-symbol { font-size: 32px; font-weight: bold; font-family: serif; color: #3182ce; margin-bottom: 10px; margin-top: 0; }
        
        .content-box { min-height: auto; }
        
        .diagnosis-box { background-color: #ebf8ff; border-left: 3px solid #3182ce; padding: 8px; margin-bottom: 15px; border-radius: 0 4px 4px 0; }
        .diagnosis-label { font-weight: bold; color: #2c5282; font-size: 10px; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .diagnosis-text { font-size: 13px; color: #2a4365; font-weight: 600; }

        .med-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .med-table th { background: #edf2f7; text-align: left; padding: 6px; font-size: 10px; font-weight: 700; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e0; }
        .med-table td { padding: 6px; border-bottom: 1px solid #edf2f7; font-size: 11px; vertical-align: middle; }
        .med-table tr:last-child td { border-bottom: none; }
        .med-table tr:nth-child(even) { background-color: #f7fafc; }
        
        .med-name { font-weight: 700; color: #2d3748; font-size: 12px; }
        .med-instruction { font-style: italic; color: #718096; font-size: 10px; }
        .dosage-badge { background: #ebf4ff; color: #4299e1; padding: 1px 4px; border-radius: 3px; font-weight: 600; font-size: 10px; }

        h3 { color: #2c5282; font-size: 13px; border-bottom: 1px solid #edf2f7; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; font-weight: 700; }
        
        .advice-box { background: #fff; padding: 0; line-height: 1.4; color: #4a5568; font-size: 11px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; color: #a0aec0; border-top: 1px solid #edf2f7; padding: 8px; background: #fff; }
        .signature-section { text-align: right; margin-top: 20px; margin-right: 10px; }
        .signature-line { border-top: 1px solid #cbd5e0; width: 150px; display: inline-block; margin-bottom: 3px; }
        .signature-text { font-weight: 700; color: #4a5568; font-size: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="clinic-name">{{ $doctor->medical_center_name ?? config('clinic.clinic_name', 'DR. AI MEDICAL CENTER') }}</div>
        <div class="doctor-name">{{ $doctor->name }} @if($doctor->degrees) ({{ $doctor->degrees }}) @endif</div>
        <div class="doctor-name">
            @if($doctor->license_number) Reg: {{ $doctor->license_number }} @endif
            @if($doctor->contact_number) @if($doctor->license_number) | @endif Ph: {{ $doctor->contact_number }} @endif
        </div>
        @if(config('clinic.address'))<div class="doctor-name">{{ config('clinic.address') }}</div>@endif
    </div>

    <div class="container">
        <table class="meta-table">
            <tr>
                <td width="15%"><span class="label">Patient Name</span></td>
                <td width="45%"><span class="value">{{ $c->patient_name }}</span></td>
                <td width="15%"><span class="label">Date</span></td>
                <td><span class="value">{{ $c->updated_at->format('d M, Y') }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Age / Gender</span></td>
                <td><span class="value">{{ $c->patient_age }} / {{ $c->patient_gender }}</span></td>
                <td><span class="label">MRN</span></td>
                <td><span class="value">{{ $c->patient->mrn ?? $c->mrn ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Visit ID</span></td>
                <td><span class="value">#{{ $c->id }}</span></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        @php 
            $rawPrescription = $c->prescription_data;
            if (is_array($rawPrescription)) {
                $pData = $rawPrescription;
            } elseif (is_string($rawPrescription) && $rawPrescription !== '') {
                $pData = json_decode($rawPrescription, true) ?: [];
            } else {
                $pData = [];
            }
        @endphp

        <table class="main-layout">
            <tr>
                <!-- LEFT COLUMN: Clinical Notes -->
                <td class="col-left">
                    <div class="notes-header">Clinical Notes & Vitals</div>
                    <div class="notes-section" style="margin-bottom: 20px;">
                        @if(!empty($pData['clinical_notes']))
                            @php
                                $notesRaw = (string) $pData['clinical_notes'];
                                $notesRaw = str_replace("\r\n", "\n", $notesRaw);
                                $notesRaw = str_replace("\r", "\n", $notesRaw);

                                $co = null;
                                $oe = null;

                                if (preg_match('/C\/O:\s*(.*?)\s*O\/E:\s*(.*)/is', $notesRaw, $m)) {
                                    $co = trim($m[1]);
                                    $oe = trim($m[2]);
                                } elseif (preg_match('/C\/O:\s*(.*)/is', $notesRaw, $m)) {
                                    $co = trim($m[1]);
                                } elseif (preg_match('/O\/E:\s*(.*)/is', $notesRaw, $m)) {
                                    $oe = trim($m[1]);
                                } else {
                                    $co = trim($notesRaw);
                                }
                            @endphp
                            @if(!empty($co))
                                <div><strong>C/O:</strong> {!! nl2br(e($co)) !!}</div>
                            @endif
                            @if(!empty($oe))
                                <div style="margin-top: 10px;"><strong>O/E:</strong> {!! nl2br(e($oe)) !!}</div>
                            @endif
                        @else
                            <span style="color: #cbd5e0; font-style: italic;">No notes recorded.</span>
                        @endif
                    </div>

                    @if(!empty($pData['investigations']))
                        <div class="notes-header">Investigations</div>
                        <div class="notes-section" style="margin-bottom: 20px;">
                            @php
                                $invRaw = (string) $pData['investigations'];
                                $invRaw = str_replace("\r\n", "\n", $invRaw);
                                $invRaw = str_replace("\r", "\n", $invRaw);
                                $invRaw = str_replace([';', '•'], "\n", $invRaw);

                                $parts = preg_split("/\n+/", $invRaw) ?: [];
                                $items = [];
                                foreach ($parts as $p) {
                                    $p = trim((string) $p);
                                    if ($p === '') continue;
                                    $p = preg_replace('/^\s*\d+\s*[\)\.\-:]\s*/', '', $p);
                                    $p = preg_replace('/\s+/', ' ', $p);
                                    if ($p === '') continue;
                                    $items[] = $p;
                                }

                                $abbr = function (string $s): string {
                                    $s = trim($s);
                                    $repl = [
                                        '/\bcomplete blood count\b/i' => 'CBC',
                                        '/\bcbc\b/i' => 'CBC',
                                        '/\bfasting blood sugar\b/i' => 'FBS',
                                        '/\brandom blood sugar\b/i' => 'RBS',
                                        '/\bblood sugar\b/i' => 'RBS',
                                        '/\bhba1c\b/i' => 'HbA1c',
                                        '/\bliver function tests?\b/i' => 'LFT',
                                        '/\bkidney function tests?\b/i' => 'KFT',
                                        '/\bthyroid function tests?\b/i' => 'TFT',
                                        '/\bc-reactive protein\b/i' => 'CRP',
                                        '/\berythrocyte sedimentation rate\b/i' => 'ESR',
                                        '/\bx-?ray chest\b/i' => 'CXR',
                                        '/\bchest x-?ray\b/i' => 'CXR',
                                        '/\belectrocardiogram\b/i' => 'ECG',
                                        '/\bechocardiography\b/i' => 'Echo',
                                        '/\burine routine(?: and microscopy)?\b/i' => 'Urine R/M',
                                        '/\burine r\/m\b/i' => 'Urine R/M',
                                        '/\burine culture(?: and sensitivity)?\b/i' => 'Urine C/S',
                                        '/\bculture(?: and sensitivity)?\b/i' => 'C/S',
                                        '/\bnasal swab for viral pcr\b/i' => 'Viral PCR',
                                        '/\bviral pcr\b/i' => 'Viral PCR',
                                        '/\bthroat swab\b/i' => 'Throat swab',
                                    ];
                                    $s = preg_replace(array_keys($repl), array_values($repl), $s);
                                    $s = preg_replace('/\s+/', ' ', (string) $s);
                                    return trim((string) $s);
                                };

                                $items2 = [];
                                foreach ($items as $it) {
                                    $it = $abbr((string) $it);
                                    if ($it === '') continue;
                                    $items2[] = $it;
                                }
                                $items = $items2;
                                $invText = implode(', ', $items);
                            @endphp
                            {{ $invText }}
                        </div>
                    @endif
                </td>

                <!-- RIGHT COLUMN: Rx & Diagnosis -->
                <td class="col-right">
                    <div class="rx-symbol">Rx</div>

                    <div class="content-box">
                        @if($pData)
                            @if(!empty($pData['diagnosis']))
                                <div class="diagnosis-box">
                                    <span class="diagnosis-label">Diagnosis</span>
                                    <div class="diagnosis-text">{{ $pData['diagnosis'] }}</div>
                                </div>
                            @endif

                            @if(isset($pData['medicines']) && count($pData['medicines']) > 0)
                                <table class="med-table">
                                    <thead>
                                        <tr>
                                            <th width="35%">Medicine Name</th>
                                            <th width="15%">Dosage</th>
                                            <th width="15%">Frequency</th>
                                            <th width="15%">Duration</th>
                                            <th width="20%">Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pData['medicines'] as $med)
                                            @php
                                                $brandName = trim((string) ($med['brand_name'] ?? ''));
                                                $compositionName = trim((string) ($med['composition_name'] ?? $med['name'] ?? ''));
                                                $displayName = $brandName !== '' ? $brandName : $compositionName;
                                                $replacements = [
                                                    '/^Tablet\s+/i' => 'Tab. ',
                                                    '/^Tablets\s+/i' => 'Tab. ',
                                                    '/^Capsule\s+/i' => 'Cap. ',
                                                    '/^Capsules\s+/i' => 'Cap. ',
                                                    '/^Syrup\s+/i' => 'Syr. ',
                                                    '/^Injection\s+/i' => 'Inj. ',
                                                    '/^Suspension\s+/i' => 'Susp. ',
                                                    '/^Ointment\s+/i' => 'Oint. ',
                                                    '/^Solution\s+/i' => 'Sol. ',
                                                    '/^Drops\s+/i' => 'Drops ',
                                                ];
                                                if ($brandName === '') {
                                                    $displayName = preg_replace(array_keys($replacements), array_values($replacements), $displayName);
                                                    $displayName = trim((string) $displayName);
                                                }

                                                $detailParts = [];
                                                if ($brandName !== '') {
                                                    $detailComp = trim((string) ($med['brand_composition_text'] ?? ''));
                                                    if ($detailComp === '') {
                                                        $detailComp = $compositionName;
                                                    }
                                                    if ($detailComp !== '') {
                                                        $detailParts[] = $detailComp;
                                                    }
                                                    $detailStrength = trim((string) ($med['brand_strength'] ?? ''));
                                                    if ($detailStrength !== '') {
                                                        $detailParts[] = $detailStrength;
                                                    }
                                                    $detailForm = trim((string) ($med['brand_dosage_form'] ?? ''));
                                                    if ($detailForm !== '') {
                                                        $detailParts[] = $detailForm;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="med-name">{{ $displayName }}</div>
                                                    @if(!empty($detailParts))
                                                        <div class="med-instruction">({{ implode(' • ', $detailParts) }})</div>
                                                    @endif
                                                </td>
                                                <td><span class="dosage-badge">{{ $med['dosage'] }}</span></td>
                                                <td style="font-weight: 600; color: #4a5568;">{{ $med['frequency'] }}</td>
                                                <td>{{ $med['duration'] }}</td>
                                                <td class="med-instruction">{{ $med['instruction'] ?? $med['instructions'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                @if(!empty($pData['advice']))
                                    <h3>Advice</h3>
                                    <div class="advice-box">
                                        {!! nl2br(e($pData['advice'])) !!}
                                    </div>
                                @endif
                            @elseif($c->ai_analysis)
                                {!! Str::markdown($c->ai_analysis) !!}
                            @else
                                <p style="color: #718096; font-style: italic;">No final diagnosis generated yet.</p>
                            @endif
                        @endif
                    </div>

                    <div class="signature-section">
                        <div class="signature-line"></div>
                        <p class="signature-text">Authorized Signature</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ config('clinic.footer', 'Generated by MedAssist System. This is a computer-generated document.') }}
    </div>

</body>
</html>
