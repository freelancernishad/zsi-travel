<?php

namespace App\Http\Controllers\Api\Admin\Blogs\Articles;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ArticlesController extends Controller
{
    /**
     * Display a listing of the articles.
     */
    public function index()
    {
        $articles = Article::with('categories')->get(); // Fetch articles with categories
        return response()->json($articles, 200);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:articles,slug',
            'content' => 'required|string',
            'category_ids' => 'required|array|exists:categories,id',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate the banner image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create the article
        $article = Article::create($request->only(['title', 'slug', 'content']));

        // Attach the selected categories to the article
        $article->categories()->attach($request->category_ids);

        // Handle the banner image upload if provided
        if ($request->hasFile('banner_image')) {
            $article->saveBannerImage($request->file('banner_image'));
        }

        return response()->json(['message' => 'Article created successfully', 'article' => $article], 201);
    }

    /**
     * Display the specified article with its categories.
     */
    public function show($id)
    {
        $article = Article::with('categories')->find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return response()->json($article, 200);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:articles,slug,' . $id,
            'content' => 'required|string',
            'category_ids' => 'required|array|exists:categories,id',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate the banner image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // Update the article
        $article->update($request->only(['title', 'slug', 'content']));

        // Sync the categories (replace all old categories)
        $article->categories()->sync($request->category_ids);

        // Handle the banner image upload if provided
        if ($request->hasFile('banner_image')) {
            $article->saveBannerImage($request->file('banner_image'));
        }

        return response()->json(['message' => 'Article updated successfully', 'article' => $article], 200);
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // Delete the article along with the categories association
        $article->categories()->detach();
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully'], 200);
    }

    /**
     * Associate a category with the article.
     */
    public function addCategory(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $article->categories()->attach($request->category_id);

        return response()->json(['message' => 'Category added to the article', 'article' => $article], 200);
    }

    /**
     * Remove a category from the article.
     */
    public function removeCategory(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $article->categories()->detach($request->category_id);

        return response()->json(['message' => 'Category removed from the article', 'article' => $article], 200);
    }


      /**
     * Get articles by category id or slug (including child categories at all levels).
     */
    public function getArticlesByCategory(Request $request)
    {
        // Validate the incoming request for category ID or slug.
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'slug' => 'nullable|exists:categories,slug'
        ]);

        $categoryId = $request->input('category_id');
        $categorySlug = $request->input('slug');

        // If both are null, return error.
        if (!$categoryId && !$categorySlug) {
            return response()->json(['message' => 'Category ID or Slug is required'], 400);
        }

        // If category ID is provided, fetch articles by category ID, including child categories.
        if ($categoryId) {
            // Get the category and its descendants (children, grandchildren, etc.)
            $category = Category::with('children')->find($categoryId);

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            // Get all articles associated with the category and all its descendants
            $articles = Article::whereHas('categories', function ($query) use ($category) {
                $query->whereIn('categories.id', $category->descendantsAndSelf()->pluck('id'));
            })->get();

            return response()->json($articles, 200);
        }

        // If category slug is provided, fetch articles by category slug, including child categories.
        if ($categorySlug) {
            // Get the category by slug and its descendants
            $category = Category::with('children')->where('slug', $categorySlug)->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            // Get all articles associated with the category and all its descendants
            $articles = Article::whereHas('categories', function ($query) use ($category) {
                $query->whereIn('categories.id', $category->descendantsAndSelf()->pluck('id'));
            })->get();

            return response()->json($articles, 200);
        }
    }

}
