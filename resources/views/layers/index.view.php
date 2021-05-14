<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
    <script src="https://use.fontawesome.com/a1ec3b6463.js"></script>
    <title>{{isset($title) ? $title : 'Архивчик'}}</title>
</head>
<body>
@import('includes/navbar')

<div class="container">
    @yield('content')
</div>

<div class="overlay" id="nav">
    <a href="javascript:void(0)" class="close-overlay">&times;</a>

    <div class="overlay-content">
        @import('includes/menu')
    </div>
</div>

<script src="{{asset('js/app.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.2/jquery.matchHeight-min.js"></script>
<script src="{{asset('js/main.js')}}"></script>

@yield('scripts')
</body>
</html>