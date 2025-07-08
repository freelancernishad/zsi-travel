<?php

namespace App\Http\Controllers\Api\User\SupportTicket;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupportTicketApiController extends Controller
{
    // Get all support tickets for the authenticated user
    public function index()
    {
        $tickets = SupportTicket::where('user_id', Auth::id())->orderBy('id', 'desc')->get();
        return response()->json($tickets, 200);
    }

    // Create a new support ticket
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the ticket
        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => $request->priority,
        ]);

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
           $ticket->saveAttachment($request->file('attachment'));
        }

        return response()->json(['message' => 'Ticket created successfully.', 'ticket' => $ticket], 201);
    }

    // Show a specific support ticket
    public function show(SupportTicket $ticket)
    {
        // Ensure the ticket belongs to the authenticated user
        if ($ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json($ticket, 200);
    }

    // Update a support ticket (if needed)
    public function update(Request $request, SupportTicket $ticket)
    {
        // Ensure the ticket belongs to the authenticated user
        if ($ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'priority' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048', // Validate attachment
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the ticket details
        $ticket->update($request->only('subject', 'message', 'priority'));

        // Handle attachment if present
        if ($request->hasFile('attachment')) {
            $ticket->saveAttachment($request->file('attachment'));
        }

        return response()->json(['message' => 'Ticket updated successfully.', 'ticket' => $ticket], 200);
    }
}
