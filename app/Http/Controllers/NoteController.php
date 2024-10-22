<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;

class NoteController extends Controller
{

    public function add_note(Request $request)
    {
        $request->validate([
            'call_id' => 'required|integer',
            'notes' => 'required|string',
        ]);

        $note = Note::where('call_id', $request->call_id)->first();
        if (!$note) {
            $note = new Note();
            $note->call_id = $request->call_id;
        }
        $note->notes = $request->notes;
        $note->save();

        return response()->json([
            'message' => 'Note has been added Successfully!'
        ], 200);
    }

    public function fetch_note(Request $request)
    {
        $note = Note::where('call_id', $request->call_id)->pluck('notes')->first();
        return response()->json([
            'message' => 'Note fetched successfully',
            'note' => $note
        ]);
    }
}
