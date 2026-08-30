<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demand extends Model
{
    use HasFactory;

    public const STATUS_CREATED = 'created';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'contact_id',
        'trello_card_id',
        'title',
        'summary',
        'status',
        'final_message',
        'whatsapp_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function scopePendingWhatsappDelivery($query)
    {
        return $query->whereNotNull('final_message')->whereNull('whatsapp_sent_at');
    }
}
