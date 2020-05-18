@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-3 col-xl-2">
            <div class="bd-sidebar-wrapper">
                <button class="btn d-md-none collapsed" type="button" data-toggle="collapse" data-target="#bd-sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" role="img" focusable="false">
                        <title>Menu</title><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M4 7h22M4 15h22M4 23h22"></path>
                    </svg>
                </button>
                <div class="bd-sidebar collapse" id="bd-sidebar">{!! $sidebar !!}</div>
            </div>
        </div>
        <div class="col-12 col-md-9 col-xl-10 bd-content">
            <h1>{{ $page->name }}</h1>
            <div class="ck-content">{!! $page->content !!}</div>
        </div>
    </div>
</div>
@endsection