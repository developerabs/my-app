@extends('landlord.layouts.frontend')

@section('content')
    <section class="bg-white md:py-20 scroll-mt-16" id="clients">
        <div class="container mx-auto p-6">
            <div style="margin-bottom: 80px;"
                class="text-center after:content-[''] after:w-10 after:h-1 after:bg-gradient-to-r after:from-[#00ADEE] after:to-[#3E458E] after:block after:mx-auto after:mt-2 hover:after:w-60 hover:after:transition-all after:duration-300">
                <h2 class="text-2xl md:text-3xl font-bold uppercase text-center">{{ $page->title }}</h2>
                {{-- <p class="italic text-sm md:text-lg">Lorem ipsum dolor sit amet consectetur adipisicing elit.</p> --}}
            </div>
            {!! $page->content !!}
        </div>
    </section>
@endsection