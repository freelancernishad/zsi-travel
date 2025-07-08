<?php

namespace App\Http\Controllers\Api\Admin\Blogs\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
     /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return response()->json($categories, 200);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        // Validate status change
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create($request->only(['name', 'slug', 'parent_id']));
        return response()->json($category, 201);
    }

    /**
     * Display the specified category with its children.
     */
    public function show($id)
    {
        $category = Category::with('children')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category, 200);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, $id)
    {

        // Validate status change
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->update($request->only(['name', 'slug', 'parent_id']));

        return response()->json(['message' => 'Category updated successfully', 'category' => $category], 200);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Delete the category along with its children
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    /**
     * Get all categories for dropdown or other purposes.
     */
    public function list()
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }


    /**
     * Reassign child categories and update the parent_id of the specified category.
     */
    public function reassignAndUpdateParent(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $request->validate([
            'new_parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
        ]);

        $newParentId = $request->input('new_parent_id'); // The new parent ID from the request

        // Reassign child categories
        if ($category->children()->exists()) {
            foreach ($category->children as $child) {
                $child->update(['parent_id' => $category->parent_id]);
            }
        }

        if($newParentId){
            // Update the category's parent_id
            $category->update(['parent_id' => $newParentId]);
        }else{
            $category->update(['parent_id' => null]);

        }

        return response()->json(['message' => 'Category updated successfully, and children reassigned', 'category' => $category], 200);
    }


}
