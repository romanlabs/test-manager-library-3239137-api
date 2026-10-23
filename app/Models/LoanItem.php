<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    protected $table = 'loan_item';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'loan_id', 'book_id', 'book_title', 'isbn',
        'category_name', 'due_at', 'returned_at'
    ];
    protected $casts = [
        'due_at' => 'datetime:Y-m-d\TH:i:s\Z',
        'returned_at' => 'datetime:Y-m-d\TH:i:s\Z'
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
