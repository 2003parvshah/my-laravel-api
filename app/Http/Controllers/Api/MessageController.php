<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Fetch all messages for a conversation
    public function index(Request $request, $conversationId)
    {
        $limit = $request->query('limit', 20); // default 20 messages
        $offset = $request->query('offset', 0); // default 0 offset (last 20)

        $conversation = Conversation::findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with('sender:id,name,email')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Determine name for the conversation
        if ($conversation->type == 'private') {
            $conversation_name = $conversation->creator->id == Auth::id()
                ? $conversation->receiver->name
                : $conversation->creator->name;
        } else if ($conversation->type == 'group') {
            $conversation_name = $conversation->group->name;
        } else {
            $conversation_name = 'Conversation';
        }

        return response()->json([
            'messages' => $messages,
            'conversation_id' => $conversation->id,
            'name' => $conversation_name,
            'type' => $conversation->type
        ]);
    }


    // Send a message in a conversation
    public function store(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = Auth::user();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $type = $request->input('type', 'file');

            $path = str_replace('#user_id#', $user->id, config('constants.s3.base_folder')) . '/messages';
            $relativePath = FileManager::upload($request, $path, 'file'); // store in AWS S3

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => '', // Empty content for file messages
                'type' => $type,
                'file_url' => $relativePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);
        } else {
            $validated = $request->validate([
                'body' => 'required|string|max:5000',
            ]);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => $validated['body'],
                'type' => 'text',
                'file_url' => null,
                'file_name' => null,
                'file_size' => null
            ]);
        }

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => 'Message sent', 'data' => $message], 201);
    }

    public function getOrCreatePrivateConversation(Request $request, $otherUserId)
    {
        $authUser = $request->user();
        $conversation = Conversation::where('type', 'private')
            ->where(function ($query) use ($authUser, $otherUserId) {
                $query->where('created_by', $authUser->id)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($authUser, $otherUserId) {
                $query->where('created_by', $otherUserId)->where('receiver_id', $authUser->id);
            })
            ->first();
        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $authUser->id,
                'receiver_id' => $otherUserId,
            ]);
        }

        return response()->json(['conversation_id' => $conversation->id]);
    }
}
