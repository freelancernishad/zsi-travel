<?php

namespace App\Http\Controllers\Api\Admin\SupportTicket;

use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminSupportTicketApiController extends Controller
{
    // Get all support tickets for admin
    public function index()
    {
        $tickets = SupportTicket::with('user')->latest()->get();
        return response()->json($tickets);
    }

    // View a specific support ticket
    public function show($id)
    {
        $ticket = SupportTicket::with(['user', 'replies'])->findOrFail($id);
        return response()->json($ticket);
    }

    // Reply to a support ticket
    public function reply(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string',
            'status' => 'required|string|in:open,closed,pending,replay', // Define allowed statuses
            'reply_id' => 'nullable|exists:replies,id', // Check if the parent reply exists
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the support ticket by ID
        $ticket = SupportTicket::findOrFail($id);

        // Update ticket status
        $ticket->status = $request->status;

        // Prepare reply data
        $replyData = [
            'reply' => $request->reply,
            'reply_id' => $request->reply_id, // Set the parent reply ID if provided
        ];

        // Check if the logged-in user is an admin
        if (auth()->guard('admin')->check()) {
            $replyData['admin_id'] = auth()->guard('admin')->id();
        } else {
            $replyData['user_id'] = auth()->guard('user')->id();
        }

        // Create a new reply associated with the support ticket
        $reply = $ticket->replies()->create($replyData);

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $reply->saveAttachment($request->file('attachment'));
        }

        // Save the ticket with the updated status
        $ticket->save();

        return response()->json([
            'message' => 'Reply sent successfully and ticket status updated.',
            'reply' => $reply
        ], 200);
    }

    // Update ticket status
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:open,closed,pending,replay', // Define allowed statuses
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = $request->status;

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $ticket->saveAttachment($request->file('attachment'));
        }

        $ticket->save();

        return response()->json(['message' => 'Ticket status updated successfully.'], 200);
    }
}
