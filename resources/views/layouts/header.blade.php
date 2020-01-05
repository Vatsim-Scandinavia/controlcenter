<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', 'Home') | {{ config('app.name') }}</title>

<!-- Custom fonts for this template-->
<link href="https://fonts.googleapis.com/css?family=Roboto:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

<!-- Custom styles for this template-->
<link href="{{ asset('css/app.css' ) }}" rel="stylesheet">
<link href="{{ asset('css/vendor.css' ) }}" rel="stylesheet">
@yield('css')