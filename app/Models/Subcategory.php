<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['title', 'category_id', 'status'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
