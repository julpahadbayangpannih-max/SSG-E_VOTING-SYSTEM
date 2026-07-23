<?php

namespace App\Services;

/**
 * A minimal, dependency-free Merkle tree over SHA-256 leaf hashes.
 *
 * Used to publish a single "root" hash that publicly commits to the exact
 * set of ballot commitments tallied in an election, without exposing any
 * individual vote. A voter can later prove their own ballot's commitment
 * was part of that exact set using a short inclusion proof — without
 * revealing the commitment (and therefore the vote) to anyone but
 * themselves.
 *
 * Design notes:
 *   - Leaves are sorted lexicographically before the tree is built. This
 *     makes the root a pure function of the *set* of commitments (order of
 *     casting never matters, and the tree can be rebuilt identically at any
 *     time from the ballots table), and avoids leaking ballot-casting order
 *     through tree position.
 *   - An odd node at any level is promoted (paired with itself) rather than
 *     duplicated-and-rehashed differently — the standard, simplest odd-leaf
 *     rule, and it matches what buildProof()/verifyProof() below expect.
 *   - Every hash in this class is a lowercase 64-char hex SHA-256 digest.
 */
class MerkleTree
{
    /**
     * Compute the Merkle root over a set of leaf hashes.
     *
     * @param string[] $leaves hex SHA-256 hashes
     */
    public static function buildRoot(array $leaves): ?string
    {
        $level = static::sortedUnique($leaves);

        if (empty($level)) {
            return null;
        }

        while (count($level) > 1) {
            $level = static::nextLevel($level);
        }

        return $level[0];
    }

    /**
     * Build an inclusion proof for one leaf: the sibling hashes needed to
     * walk from that leaf up to the root, plus which side each sibling is
     * on. Returns null if the leaf isn't in the set.
     *
     * @param string[] $leaves
     * @return array{path: array<int, array{hash: string, side: string}>}|null
     */
    public static function buildProof(array $leaves, string $targetLeaf): ?array
    {
        $level = static::sortedUnique($leaves);
        $index = array_search($targetLeaf, $level, true);

        if ($index === false) {
            return null;
        }

        $path = [];

        while (count($level) > 1) {
            $isRightNode = $index % 2 === 1;
            $pairIndex = $isRightNode ? $index - 1 : $index + 1;

            if (isset($level[$pairIndex])) {
                $path[] = [
                    'hash' => $level[$pairIndex],
                    'side' => $isRightNode ? 'left' : 'right',
                ];
            }
            // else: odd node out at this level, promoted as-is — no sibling to record.

            $level = static::nextLevel($level);
            $index = intdiv($index, 2);
        }

        return ['path' => $path];
    }

    /**
     * Recompute the root implied by a leaf + its proof, and check it
     * matches the expected (published) root.
     *
     * @param array{path: array<int, array{hash: string, side: string}>} $proof
     */
    public static function verifyProof(string $leaf, array $proof, string $expectedRoot): bool
    {
        $hash = $leaf;

        foreach ($proof['path'] as $step) {
            $hash = $step['side'] === 'left'
                ? hash('sha256', $step['hash'] . $hash)
                : hash('sha256', $hash . $step['hash']);
        }

        return hash_equals($expectedRoot, $hash);
    }

    /**
     * @param string[] $level
     * @return string[]
     */
    private static function nextLevel(array $level): array
    {
        $next = [];

        for ($i = 0; $i < count($level); $i += 2) {
            if (isset($level[$i + 1])) {
                $next[] = hash('sha256', $level[$i] . $level[$i + 1]);
            } else {
                // Odd one out: promote unchanged rather than self-pairing,
                // so buildProof()'s "no sibling recorded" case above stays
                // consistent with what actually happened at this level.
                $next[] = $level[$i];
            }
        }

        return $next;
    }

    /**
     * @param string[] $leaves
     * @return string[]
     */
    private static function sortedUnique(array $leaves): array
    {
        $leaves = array_values(array_unique($leaves));
        sort($leaves, SORT_STRING);

        return $leaves;
    }
}
