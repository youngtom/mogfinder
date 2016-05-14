<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>MogCollector</title>

    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sb-admin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    @yield('css')
    <link href="{{ asset('font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <div id="wrapper">
        <nav id="top-nav" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ url('/') }}">MogCollector</a>
            </div>
            
            <ul class="nav navbar-right top-nav">
                @if (Auth::guest())
                    <li><a href="{{ url('/login') }}">Login</a></li>
                    <li><a href="{{ url('/register') }}">Register</a></li>
                @else
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            {{ Auth::user()->name }} <span class="caret"></span>
                        </a>

                        <ul class="dropdown-menu" role="menu">
	                        <li><a href="{{ url('/upload-data') }}"><i class="fa fa-btn fa-upload"></i> Upload Data</a></li>
                            <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i> Logout</a></li>
                        </ul>
                    </li>
                @endif
            </ul>
            
            <form id="search-form" class="navbar-form navbar-right" action="{{ url('/search') }}">
                <div class="form-group">
	                <input type="text" class="form-control typeahead" name="q" placeholder="Search" />
                </div>
                <button type="submit" class="btn btn-default hidden">Submit</button>
            </form>
            
            <div class="collapse navbar-collapse navbar-ex1-collapse">
                <ul class="nav navbar-nav side-nav">
                    <li class="{{ set_active('home') }}">
                        <a href="{{ url('/dashboard') }}"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li class="{{ set_active('wardrobe*') }}">
                        <a href="javascript:;" data-toggle="collapse" data-target="#demo"><i class="fa fa-fw fa-suitcase"></i> Wardrobe <i class="fa fa-fw fa-caret-down"></i></a>
                        <ul id="demo" class="<?=(set_active('wardrobe*')) ? '' : 'collapse'?>">
                            <li>
                                <a href="{{ url('/wardrobe') }}">Overview</a>
                            </li>
                            <li>
                                <a href="{{ url('/wardrobe/zones') }}">Zone Overview</a>
                            </li>
                            <li>
                                <a href="{{ url('/wardrobe/auctions') }}">Auction Search</a>
                            </li>
                            <li>
                                <a href="{{ url('/wardrobe/duplicates') }}">Duplicates</a>
                            </li>
                        </ul>
                    </li>
                    <li class="{{ set_active('upload-data') }}">
                        <a href="{{ url('/upload-data') }}"><i class="fa fa-fw fa-upload"></i> Upload Data</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div id="page-wrapper">
			@yield('content')
        </div>
    </div>
    
    <footer class="footer">
	    <p class="text-muted pull-right">&copy;<?=date('Y')?> tritus. All images &reg; <a href="http://www.blizzard.com" target="_blank">Blizzard Entertainment</a>.</p>
    </footer>

    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
	<script src="{{ asset('js/typeahead/typeahead.bundle.min.js') }}"></script>
	<script src="{{ asset('js/main.js') }}"></script>
	<script>var wowhead_tooltips = { "colorlinks": false, "iconizelinks": true, "renamelinks": false }</script>
	@yield('js')
    
	
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	
	  ga('create', 'UA-75407673-1', 'auto');
	  ga('send', 'pageview');	
	</script>
</body>

</html>
