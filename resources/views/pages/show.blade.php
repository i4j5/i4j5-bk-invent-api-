@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $page->name }}</h1>

    <div class="ck-content">{!! $page->content !!}</div>
</div>
@endsection