@extends('landlord.layouts.frontend')

@section('content')
    @foreach ($sections as $section)
        {!! $section !!}

    @endforeach
@endsection
