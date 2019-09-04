@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-3 col-xl-2 bd-sidebar">
            {!! $menu !!}
        </div>
        <div class="col-12 col-md-9 col-xl-10 bd-content">
            <h1>{{ $page->name }}</h1>
            <div class="ck-content">{!! $page->content !!}</div>
        </div>
    </div>
</div>
@endsection