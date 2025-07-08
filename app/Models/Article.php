<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'banner_image',
        'status',
    ];


    public function saveBannerImage($file)
    {
        // Use your preferred file storage method (e.g., S3, local storage)
        // In this example, I'm using S3 as per your original method

        $filePath = uploadFileToS3($file, 'banner_images');
        $this->banner_image = $filePath; // Store the file path in the banner_image field
        $this->save();

        return $filePath;
    }


    /**
     * Define the many-to-many relationship with the Category model.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }
}
