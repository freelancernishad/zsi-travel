<?php

namespace App\Http\Controllers\Api\Admin\SocialMedia;

use Illuminate\Http\Request;
use App\Models\SocialMediaLink;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AdminSocialMediaLinkController extends Controller
{



        /**
     * Display a listing of the social media links.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Fetch enabled links, sorted by index_no in ascending order
        $links = SocialMediaLink::orderBy('index_no', 'asc')
            ->get();

        return response()->json($links);
    }

    /**
     * Display the specified social media link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the link by ID
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        return response()->json($link);
    }



    /**
     * Store a newly created social media link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:social_media_links,name',
            'url' => 'required|url',
            'icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'hover_icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'index_no' => 'nullable|integer',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Prepare data
        $data = $request->all();

        // Assign the next available index_no if it's not provided
        if ($request->index_no === null) {
            $maxIndex = SocialMediaLink::max('index_no') ?? 0;
            $data['index_no'] = $maxIndex + 1;
        }

        $name = $request->name;
        // Handle file uploads
        if ($request->hasFile('icon')) {
            $data['icon'] = uploadFileToS3($request->file('icon'), "SocialMediaLink/$name/icon");
        }
        if ($request->hasFile('hover_icon')) {
            $data['hover_icon'] = uploadFileToS3($request->file('hover_icon'), "SocialMediaLink/$name/hover_icon");
        }

        // Create the social media link
        $link = SocialMediaLink::create($data);

        return response()->json($link, 201); // Return the created link
    }

    /**
     * Update the specified social media link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:social_media_links,name,' . $id,
            'url' => 'required|url',
            'icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'hover_icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            'index_no' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Find the link
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        // Prepare data
        $data = $request->all();

        // Assign the next available index_no if it's not provided
        if ($request->index_no === null) {
            $maxIndex = SocialMediaLink::where('id', '!=', $id)->max('index_no') ?? 0;
            $data['index_no'] = $maxIndex + 1;
        }

        $name = $request->name;
        // Handle file uploads
        if ($request->hasFile('icon')) {
            $data['icon'] = uploadFileToS3($request->file('icon'), "SocialMediaLink/$name/icon");
        }
        if ($request->hasFile('hover_icon')) {
            $data['hover_icon'] = uploadFileToS3($request->file('hover_icon'), "SocialMediaLink/$name/hover_icon");
        }

        $link->update($data);

        return response()->json($link);
    }


     /**
     * Toggle the status of a social media link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        // Toggle status (1 -> 0 or 0 -> 1)
        $link->status = !$link->status;
        $link->save();

        return $this->index();
        return response()->json($link);
    }

    /**
     * Update the index_no of a social media link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateIndexNo(Request $request, $id)
    {
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        // Validate the new index_no
        $request->validate([
            'index_no' => 'required|integer|min:1',
        ]);

        // If the new index_no is different, handle the reordering
        if ($link->index_no !== $request->index_no) {
            $this->reorderIndexNumbers($link, $request->index_no);
        }

        // Update the index_no for the link
        $link->index_no = $request->index_no;
        $link->save();
        return $this->index();
        return response()->json($link);
    }

    /**
     * Reorder index_no to ensure unique and sequential numbers.
     *
     * @param  \App\Models\SocialMediaLink  $link
     * @param  int  $newIndexNo
     * @return void
     */
    private function reorderIndexNumbers(SocialMediaLink $link, $newIndexNo)
    {
        // If the new index_no is higher or lower, we'll reorder selectively
        if ($newIndexNo > $link->index_no) {
            // Shift down the records with index greater than the current one
            SocialMediaLink::where('index_no', '>', $link->index_no)
                ->where('index_no', '<=', $newIndexNo)
                ->decrement('index_no');
        } elseif ($newIndexNo < $link->index_no) {
            // Shift up the records with index smaller than the current one
            SocialMediaLink::where('index_no', '>=', $newIndexNo)
                ->where('index_no', '<', $link->index_no)
                ->increment('index_no');
        }
    }

}
