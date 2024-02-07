<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;

class NoteController extends Controller
{

    public function add_note(Request $request)
    {
        $request->validate([
            'call_sid' => 'required|string',
            'notes' => 'required|string',
        ]);

        $note = Note::where('sid', $request->call_sid)->first();
        if (!$note) {
            $note = new Note();
            $note->sid = $request->call_sid;
        }
        $note->notes = $request->notes;
        $note->save();

        return response()->json([
            'message' => 'Note has been added Successfully!'
        ], 200);
    }

    public function fetch_note(Request $request)
    {
        $note = Note::where('sid', $request->sid)->pluck('notes')->first();
        return response()->json([
            'message' => 'Note fetched successfully',
            'note' => $note
        ]);
    }
}
