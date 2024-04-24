@extends('layouts.app')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h3>Friend Requests</h3>
            <ul class="list-group">
                @foreach($requests as $friendship)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <img src="{{ asset('images/'.$friendship->user->profile_picture) }}"
                            alt="{{ $friendship->user->first_name }}'s Profile Picture" class="img-thumbnail mr-3"
                            style="width: 50px;">
                        <span>{{ $friendship->user->first_name }} {{ $friendship->user->last_name }}</span>
                    </div>
                    <div style="display: flex;">
                        <form action="{{ route('accept-friend-request', $friendship)  }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-success" style="margin-right: 0.5em">Accept</button>
                        </form>
                        <form action="{{ route('reject-friend-request', $friendship) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@endsection