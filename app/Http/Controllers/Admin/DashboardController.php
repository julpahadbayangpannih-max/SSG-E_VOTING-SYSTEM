<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ResolvesCurrentElection;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Position;
use App\Models\Vote;
use App\Models\Voter;

class DashboardController extends Controller
{
    use ResolvesCurrentElection;

    public function index()
    {
        $election = $this->currentElection();
        $electionId = $election?->id;

        $totalApproved = Voter::where('is_approved', true)->count();
        $votedCount = $electionId ? Ballot::where('election_id', $electionId)->count() : 0;

        $stats = [
            'total_voters' => $totalApproved,
            'pending_voters' => Voter::where('is_approved', false)->count(),
            'voted_count' => $votedCount,
            'total_candidates' => $electionId ? Candidate::where('election_id', $electionId)->count() : 0,
            'total_positions' => $electionId ? Position::where('election_id', $electionId)->count() : 0,
            'total_votes' => $electionId ? Vote::where('election_id', $electionId)->count() : 0,
            'turnout_percent' => $totalApproved > 0
                ? round(($votedCount / $totalApproved) * 100, 1)
                : 0,
        ];

        $start = $election?->start_time;
        $end = $election?->end_time;

        $turnoutByCourse = Voter::where('voters.is_approved', true)
            ->leftJoin('ballots', function ($join) use ($electionId) {
                $join->on('ballots.voter_id', '=', 'voters.id')
                    ->where('ballots.election_id', '=', $electionId ?? 0);
            })
            ->selectRaw('voters.course as course, COUNT(DISTINCT voters.id) as total, COUNT(ballots.id) as voted')
            ->groupBy('voters.course')
            ->orderBy('voters.course')
            ->get()
            ->map(fn ($row) => [
                'course' => $row->course,
                'total' => (int) $row->total,
                'voted' => (int) $row->voted,
                'percent' => $row->total > 0 ? round(($row->voted / $row->total) * 100, 1) : 0,
            ])
            ->values();

        return view('admin.dashboard', compact('stats', 'start', 'end', 'turnoutByCourse', 'election'));
    }
}
