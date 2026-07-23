<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row table (id is always 1) holding the hash of the most recent
 * audit log entry. Every write locks this row with lockForUpdate() inside
 * a transaction so concurrent audit log writes are serialized into one
 * chain instead of racing and silently forking. See LogsActivity::auditLog().
 */
class AuditChainState extends Model
{
    public $timestamps = false;

    protected $table = 'audit_chain_state';

    protected $fillable = ['last_hash'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
