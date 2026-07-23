<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per legitimate audit-log cleanup (see CleanupAuditLogs). Records
 * where the hash chain should resume from after old rows are deleted, so
 * chain verification can tell "rows removed by a documented retention
 * cleanup" apart from "rows removed by someone covering their tracks."
 */
class AuditChainCheckpoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'deleted_up_to_audit_log_id',
        'entries_removed',
        'resuming_prev_hash',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
