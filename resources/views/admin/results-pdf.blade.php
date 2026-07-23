<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JRMSU SSG Election Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a2e; font-size: 13px; background: #fff; }
        .header { background: #001f3f; color: white; padding: 28px 40px 20px; text-align: center; }
        .header h1 { font-size: 22px; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 4px; }
        .header p  { font-size: 12px; color: rgba(255,255,255,0.7); }
        .header .badge { display: inline-block; margin-top: 10px; background: #ffc107; color: #001f3f; font-weight: 700; font-size: 11px; padding: 3px 14px; border-radius: 999px; }
        .content { padding: 30px 40px; }
        .position-block { margin-bottom: 30px; }
        .position-title { font-size: 15px; font-weight: 700; color: #001f3f; border-left: 4px solid #ffc107; padding-left: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th { background: #f1f5f9; text-align: left; padding: 8px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; }
        td { padding: 9px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        tr:last-child td { border-bottom: none; }
        .winner { font-weight: 700; color: #15803d; }
        .rank-badge { display: inline-block; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 999px; margin-right: 6px; }
        .rank-1 .rank-badge { background: #fef3c7; color: #92400e; }
        .rank-2 .rank-badge { background: #e5e7eb; color: #374151; }
        .rank-3 .rank-badge { background: #fde68a; color: #78350f; }
        .votes-bar { display: inline-block; height: 8px; background: #ffc107; border-radius: 4px; min-width: 4px; }
        .footer { text-align: center; color: #94a3b8; font-size: 10px; padding: 20px 40px 30px; border-top: 1px solid #e2e8f0; }
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print" style="background:#001f3f;color:white;padding:10px 20px;display:flex;gap:10px;align-items:center;">
    <span style="font-size:13px;display:inline-flex;align-items:center;gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="height:16px;width:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
        Print this page to save as PDF (Ctrl+P / Cmd+P)
    </span>
    <button onclick="window.print()" style="background:#ffc107;color:#001f3f;border:none;padding:6px 18px;border-radius:6px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="height:16px;width:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
        Print / Save PDF
    </button>
    <a href="{{ route('admin.results.index') }}" style="color:rgba(255,255,255,0.7);font-size:12px;margin-left:10px;">← Back to Results</a>
</div>

<div class="header">
    @if(!empty($brand['logo_url']))
        <img src="{{ $brand['logo_url'] }}" alt="Logo" style="height:56px;width:56px;border-radius:50%;object-fit:cover;margin-bottom:10px;border:2px solid rgba(255,255,255,0.4);">
    @endif
    <h1>{{ $brand['school_name'] ?? 'JRMSU Siocon Campus' }} — SSG Election</h1>
    <p>{{ $brand['tagline'] ?? 'Official Ballot · E-Voting System' }}</p>
    <div class="badge">Official Election Results</div>
    @if($election)<p style="margin-top:6px; font-size:12px;">{{ $election->title }}</p>@endif
    <p style="margin-top:8px; font-size:11px; color:rgba(255,255,255,0.6);">Exported: {{ $exportedAt }}</p>
</div>

<div class="content">
    @foreach($results as $positionName => $candidates)
    @php $maxVotes = $candidates->max('voteCount'); @endphp
    <div class="position-block">
        <div class="position-title">{{ $positionName }}</div>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Party List</th>
                    <th>Votes</th>
                    <th style="width:200px">Distribution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($candidates->sortByDesc('voteCount')->values() as $i => $c)
                <tr class="rank-{{ $i + 1 }} {{ $i === 0 && $c['voteCount'] > 0 ? 'winner' : '' }}">
                    <td>
                        @if($i < 3)<span class="rank-badge">{{ $i + 1 }}{{ ['st','nd','rd'][$i] }}</span>@endif
                        {{ $c['candidateName'] }}
                    </td>
                    <td>{{ $c['partyList'] ?? '—' }}</td>
                    <td>{{ $c['voteCount'] }}</td>
                    <td>
                        @if($maxVotes > 0)
                        <span class="votes-bar" style="width:{{ round(($c['voteCount'] / $maxVotes) * 160) }}px"></span>
                        @endif
                        <span style="margin-left:6px;font-size:11px;color:#64748b;">
                            {{ $maxVotes > 0 ? round(($c['voteCount'] / $maxVotes) * 100) : 0 }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

<div class="footer">
    This document is an official export from the JRMSU Siocon SSG E-Voting System. &nbsp;|&nbsp; Generated: {{ $exportedAt }}
</div>

</body>
</html>
