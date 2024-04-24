<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(User $user)
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('home', compact('users'));
    }

    public function getRequestList()
    {
        $requests = Friendship::where(function ($query) {
            $query->where('user_id', auth()->user()->id)
                ->orWhere('friend_id', auth()->user()->id);
        })
            ->where('status', 'pending')
            ->with('user')
            ->get();

        return view('request-list', compact('requests'));
    }

    public function sendRequest(User $user)
    {
        Auth::user()->friendship()->create([
            'user_id' => Auth::id(),
            'friend_id' => $user->id
        ]);

        return back();
    }

    public function acceptRequest(Friendship $friendship)
    {
        $friendship->status = 'accepted';
        $friendship->save();

        $userIdToRemove = $friendship->user_id === Auth::id() ? $friendship->friend_id : $friendship->user_id;
        $users = User::where('id', '!=', Auth::id())->where('id', '!=', $userIdToRemove)->get();

        return view('home', compact('users'));
    }

    public function rejectRequest(Friendship $friendship)
    {
        $friendship->status = 'rejected';
        $friendship->save();
        return back();
    }

    public function getFriendsList()
    {
        $userId = auth()->user()->id;

        $friends = Friendship::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhere('friend_id', $userId);
        })
            ->where('status', 'accepted')
            ->with([
                'user' => function ($query) use ($userId) {
                    $query->where('id', '<>', $userId);
                },
                'friend' => function ($query) use ($userId) {
                    $query->where('id', '<>', $userId);
                }
            ])
            ->get();

        return view('friend-list', compact('friends'));
    }

    public function showChat($userId)
    {
        return view('chat', ['userId' => $userId]);
    }
}
