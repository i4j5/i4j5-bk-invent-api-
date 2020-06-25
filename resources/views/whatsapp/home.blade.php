@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center py-4">
            @if (isset($status->qrCode))
                <img src="{{$status->qrCode}}">
            @endif
            <h1>
                {{$status->accountStatus}}
            </h1>
        </div>
    </div>
</div>
@endsection

