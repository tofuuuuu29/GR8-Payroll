<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    use HasFactory;

    protected $table = 'login_logs';
    
    protected $fillable = [
        'account_id',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            Account::class,
            'id', // Foreign key on accounts table
            'id', // Foreign key on employees table
            'account_id', // Local key on login_logs table
            'employee_id' // Local key on accounts table
        );
    }

    public static function recordForAccount(Account $account, string $ipAddress = null, string $userAgent = null): self
    {
        return self::create([
            'account_id' => $account->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
