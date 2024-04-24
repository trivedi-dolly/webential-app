@extends('layouts.app')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h3>Messenger</h3>
            <ul class="list-group">
                @foreach($friends as $friend)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <img src="{{ asset('images/'. ($friend->user ? $friend->user->profile_picture : ($friend->friend ? $friend->friend->profile_picture : ''))) }}"
                            alt="{{ $friend->user ? $friend->user->first_name : ($friend->friend ? $friend->friend->first_name : '') }}'s Profile Picture"
                            class="img-thumbnail mr-3" style="width: 50px;">

                        <span>{{ $friend->user->first_name ?? $friend->friend->first_name }}{{ $friend->user->last_name
                            ?? $friend->friend->last_name}} </span>
                    </div>
                    <div style="display: flex;">
                        <form action="{{ route('chat.show', $friend->user->id ?? $friend->friend_id) }}" method="get">
                            @csrf
                            <input type="hidden" name="friend_id" value="{{ $friend->user->id ?? $friend->friend_id }}">
                            <button type="submit" class="btn btn-info" style="margin-right: 0.5em">Chat</button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@endsection