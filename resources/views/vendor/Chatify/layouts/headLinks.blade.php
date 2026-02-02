<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ config('chatify.name') }}</title>

{{-- Meta tags --}}
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="id" content="{{ $id }}">
<meta name="messenger-color" content="{{ $messengerColor }}">
<meta name="messenger-theme" content="{{ $dark_mode }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="url" content="{{ url('').'/'.config('chatify.routes.prefix') }}" data-user="{{ Auth::user()->id }}">

{{-- scripts --}}
<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/chatify/font.awesome.min.js') }}"></script>
<script src="{{ asset('js/chatify/autosize.js') }}"></script>
@vite(['resources/js/app.js'])
<script src='https://unpkg.com/nprogress@0.2.0/nprogress.js'></script>

{{-- styles --}}
<link rel='stylesheet' href='https://unpkg.com/nprogress@0.2.0/nprogress.css'/>
<link href="{{ asset('css/chatify/style.css') }}" rel="stylesheet" />
<link href="{{ asset('css/chatify/'.$dark_mode.'.mode.css') }}" rel="stylesheet" />
<link href="{{ asset('css/chatify/groups.css') }}" rel="stylesheet" />
@if($dark_mode === 'dark')
<link href="{{ asset('css/chatify/groups.dark.mode.css') }}" rel="stylesheet" />
@endif
<link href="{{ asset('css/chatify/groups.more.css') }}" rel="stylesheet" />
@vite(['resources/css/app.css'])

{{-- Setting messenger primary color to css --}}
<style>
    :root {
        --primary-color: {{ $messengerColor }};
    }
</style>
