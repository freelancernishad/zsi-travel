<?php

namespace App\Http\Controllers\Api\User\SocialMedia;

use Illuminate\Http\Request;
use App\Models\SocialMediaLink;
use App\Http\Controllers\Controller;

class UserSocialMediaLinkController extends Controller
{
    /**
     * Display a listing of the social media links.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Fetch enabled links, sorted by index_no in ascending order
        $links = SocialMediaLink::where('status', 1)
            ->orderBy('index_no', 'asc')
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

        // Ensure only enabled links are displayed
        if ($link->status !== 1) {
            return response()->json(['message' => 'Link is not enabled'], 403);
        }

        return response()->json($link);
    }
}
