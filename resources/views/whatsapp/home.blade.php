@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 text-center py-4">
            @if (isset($status->qrCode))
                <img src="{{$status->qrCode}}">
            @endif
            <h1>
                @if (isset($status->accountStatus))
                    {{$status->accountStatus}}
                    <img src="{{env('WHATSAPP_URL')}}screenshot?token={{env('WHATSAPP_TOKEN')}}">
                @elseif (isset($status->error)) 
                    Ошибка :(   
                @endif
            </h1>
        </div>
        <div class="col-md-12 py-4">
            @if (isset($status->error))
                {{$status->error}}
            @endif
        </div>
    </div>
</div>
@endsection

