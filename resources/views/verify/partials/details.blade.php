<div class="ballot-stub my-4 w-40 mx-auto"></div>
<div class="text-left bg-white/60 dark:bg-black/20 rounded-xl p-4 text-xs space-y-1.5 font-mono">
    <div class="flex justify-between gap-3">
        <span class="text-gray-400">Voted at</span>
        <span class="text-gray-700 dark:text-gray-300">{{ $result['voted_at']?->format('M j, Y g:i A') }}</span>
    </div>
    @if(!empty($result['certified_at']))
    <div class="flex justify-between gap-3">
        <span class="text-gray-400">Certified at</span>
        <span class="text-gray-700 dark:text-gray-300">{{ $result['certified_at']->format('M j, Y g:i A') }}</span>
    </div>
    @endif
    @if(!empty($result['leaf_count']))
    <div class="flex justify-between gap-3">
        <span class="text-gray-400">Ballots in tally</span>
        <span class="text-gray-700 dark:text-gray-300">{{ $result['leaf_count'] }}</span>
    </div>
    @endif
    @if(!empty($result['merkle_root']))
    <div class="flex justify-between gap-3">
        <span class="text-gray-400 shrink-0">Merkle root</span>
        <span class="text-gray-700 dark:text-gray-300 break-all text-right" title="{{ $result['merkle_root'] }}">
            {{ substr($result['merkle_root'], 0, 10) }}…{{ substr($result['merkle_root'], -10) }}
        </span>
    </div>
    @endif
</div>
<p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3">
    This proves your ballot's participation was counted — it never shows which candidates you chose.
</p>
