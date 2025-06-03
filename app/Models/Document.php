<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'employee_id',
        'document_name',
        'document_type',
        'issue_date',
        'expiry_date',
        'document'
    ];

    /**
     * Define the relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
