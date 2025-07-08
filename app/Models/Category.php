<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'parent_id'];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children'); // Recursive relationship
    }

    /**
     * Define the many-to-many relationship with the Article model.
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

       /**
     * Get all descendants of a category (including self).
     */
    public function descendantsAndSelf()
    {
        // Start with the current category (self) and include all children recursively
        $descendants = collect([$this]);

        foreach ($this->children as $child) {
            $descendants = $descendants->merge($child->descendantsAndSelf());
        }

        return $descendants;
    }
}

