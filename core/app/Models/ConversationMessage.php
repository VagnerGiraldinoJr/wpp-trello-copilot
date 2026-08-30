<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'message_hash',
        'body',
        'sent_at',
        'is_processed',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'is_processed' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public static function hashFor(int $contactId, string $body, ?string $sentAt): string
    {
        return hash('sha256', $contactId.'|'.$body.'|'.$sentAt);
    }
}
