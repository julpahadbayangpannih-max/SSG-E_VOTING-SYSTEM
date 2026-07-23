<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\LogsActivity;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Support\Branding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResultController extends Controller
{
    use LogsActivity, ResolvesCurrentElection;

    /**
     * Which election's results to show: an explicit ?election=ID (used when
     * browsing history) takes priority, otherwise fall back to whichever
     * election the admin is currently managing.
     */
    private function resolveElection(Request $request): ?Election
    {
        if ($request->filled('election')) {
            return Election::find($request->integer('election'));
        }

        return $this->currentElection();
    }

    public function index(Request $request)
    {
        $election = $this->resolveElection($request);
        $elections = Election::orderByDesc('id')->get();
        $results = $election ? $this->getResults($election->id) : [];

        // JS (apiFetch) sends Accept: application/json — return JSON for AJAX calls
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $results]);
        }

        return view('admin.results', compact('results', 'election', 'elections'));
    }

    public function resetVotes(Request $request)
    {
        $election = $this->resolveElection($request);
        $admin = Auth::guard('admin')->user();

        if (! $election) {
            return response()->json(['success' => false, 'message' => 'No election selected.'], 422);
        }

        // History stays untouched on purpose: resetting only ever clears the
        // election currently being managed, and only while it's still
        // draft/open. A closed election's results are permanent record.
        if ($election->isClosed()) {
            return response()->json(['success' => false, 'message' => 'This election is closed. Its results are permanent and cannot be reset.'], 422);
        }

        DB::transaction(function () use ($election) {
            Vote::where('election_id', $election->id)->delete();
            Ballot::where('election_id', $election->id)->delete();
        });

        $this->auditLog($request, 'votes_reset', 'admin', $admin->id, $admin->name, [
            'election_id' => $election->id,
            'note' => 'All votes and ballots wiped for this election.',
        ]);

        return response()->json(['success' => true, 'message' => 'All votes reset successfully.']);
    }

    private function getResults(int $electionId): array
    {
        return Candidate::with('position')
            ->where('candidates.election_id', $electionId)
            ->select([
                'candidates.*',
                DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.candidate_id = candidates.id AND votes.election_id = ' . (int) $electionId . ') as vote_count'),
            ])
            ->get()
            ->map(fn ($c) => [
                'candidateId' => $c->id,
                'candidateName' => $c->name,
                'partyList' => $c->party_list,
                'image' => $c->image_url,
                'positionId' => $c->position_id,
                'positionName' => $c->position->name ?? '',
                'maxVotes' => $c->position->max_votes ?? 1,
                'voteCount' => (int) $c->vote_count,
            ])
            ->sortByDesc('voteCount')
            ->values()
            ->toArray();
    }

    public function exportCsv(Request $request)
    {
        $election = $this->resolveElection($request);
        $results = $election ? $this->getResults($election->id) : [];

        // Group by position
        $byPosition = collect($results)->groupBy('positionName');

        $rows = [];
        $rows[] = ['Position', 'Candidate', 'Party List', 'Votes'];

        foreach ($byPosition as $positionName => $candidates) {
            foreach ($candidates as $c) {
                $rows[] = [
                    $positionName,
                    $c['candidateName'],
                    $c['partyList'] ?? '—',
                    $c['voteCount'],
                ];
            }
        }

        $slug = $election ? Str::slug($election->title) : 'election';
        $filename = "election-results-{$slug}-" . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdfView(Request $request)
    {
        $election = $this->resolveElection($request);
        $results = $election ? collect($this->getResults($election->id))->groupBy('positionName') : collect();
        $exportedAt = now()->timezone('Asia/Manila')->format('F j, Y g:i A');
        $brand = Branding::get();
        $viewData = compact('results', 'exportedAt', 'election', 'brand');

        // Professional, one-click PDF download when barryvdh/laravel-dompdf
        // is installed (composer require barryvdh/laravel-dompdf). Without
        // it, we fall back to the existing print-to-PDF HTML view so this
        // route never breaks on a fresh clone that hasn't added the package.
        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('admin.results-pdf', $viewData)->setPaper('a4');

            $slug = $election ? Str::slug($election->title) : 'election';

            return $pdf->download('election-results-' . $slug . '-' . date('Y-m-d-His') . '.pdf');
        }

        return view('admin.results-pdf', $viewData);
    }
}
