<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Warehouse Allocator')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f4f6f8; color: #1f2933; margin: 0; }
        header { background: #12344d; color: #fff; padding: 14px 24px; }
        header a { color: #fff; text-decoration: none; font-weight: 600; }
        main { max-width: 820px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin-top: 0; font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 0; }
        label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 4px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #c4ccd4; border-radius: 6px; font-size: 1rem; }
        .row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px; }
        .row .product { flex: 3; }
        .row .qty { flex: 1; }
        button, .btn { padding: 9px 16px; border: 0; border-radius: 6px; font-size: .95rem; cursor: pointer; background: #12344d; color: #fff; text-decoration: none; display: inline-block; }
        button.secondary, .btn.secondary { background: #e3e8ed; color: #1f2933; }
        button.danger { background: #c0392b; }
        button.confirm { background: #1e8e5a; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: .8rem; font-weight: 600; }
        .badge.full { background: #d4f1e1; color: #146c43; }
        .badge.partial { background: #fff0c9; color: #8a6100; }
        .badge.unfulfillable { background: #fbd9d5; color: #a02a1c; }
        .badge.confirmed { background: #d6e8fb; color: #1b5aa0; }
        .errors { background: #fbd9d5; color: #a02a1c; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .errors ul { margin: 0; padding-left: 18px; }
        .warning { background: #fff0c9; color: #8a6100; padding: 8px 12px; border-radius: 6px; margin-top: 8px; font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e3e8ed; font-size: .95rem; }
        .muted { color: #667785; font-size: .9rem; }
        ul.explain { margin: 0; padding-left: 18px; }
        ul.explain li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <header><a href="{{ route('orders.create') }}">Warehouse Allocator</a></header>
    <main>
        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>