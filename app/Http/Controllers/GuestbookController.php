<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GuestbookController extends Controller
{
    private const COLORS = [
        '#6366f1', '#8b5cf6', '#ec4899', '#f97316',
        '#14b8a6', '#0ea5e9', '#22c55e', '#eab308',
    ];

    public function index()
    {
        $messages = Message::latest()->take(20)->get();
        $visits   = Cache::increment('page_visits');
        $total    = Message::count();

        return view('guestbook', compact('messages', 'visits', 'total'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'body' => 'required|string|max:500',
        ]);

        $data['avatar_color'] = self::COLORS[array_rand(self::COLORS)];

        Message::create($data);

        return redirect('/')->with('success', 'Message posted!');
    }
}
