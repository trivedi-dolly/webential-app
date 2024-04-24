@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @endif

                    {{ __('Hi, :name! You are logged in!', ['name' => Auth::user()->first_name]) }}


                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group">
                                    @foreach($users as $user)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <img src="{{ asset('images/' . $user->profile_picture) }}"
                                            alt="{{ $user->first_name }}'s Profile Picture" class="img-thumbnail mr-3"
                                            style="width: 50px;">
                                        <span>{{ $user->first_name }} {{ $user->last_name }}</span>
                                        @if ($user->id != Auth::id())
                                        @if (Auth::user()->friendshipRequestSent($user))
                                        <button class="btn btn-info" disabled>Friend Request Sent</button>
                                        @else

                                        <form method="post" action="{{ route('send-request',$user) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-info">Send
                                                Request</button>
                                        </form>
                                        @endif
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection