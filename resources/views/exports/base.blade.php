<head>
    <style>
        @page {
            margin: 0px;
            --primary: {{ config('rapidez.quote.colors.primary') }};
        }

        body {
            border-left: 8px solid var(--primary);
            padding: 4.8rem;
            font-family: sans-serif;
            font-size: 12px;
        }

        ul {
            margin: 0.75rem;
            margin-top: 0.25rem;
            padding-left: 0.75rem;
        }

        li {
            margin-top: -1.3rem;
        }

        h3 {
            font-size: 12px;
        }

        .text-primary,
        a {
            color: var(--primary);
        }

        .title {
            font-size: 2rem;
        }
    </style>
    @yield('style', '')
</head>

<body>
    <div style="position:relative">
        <img
            src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(config('rapidez.quote.logo-path'))) }}"
            style="position:absolute;top:0;right:0;max-width:250px;height:auto;"
            height="100"
        />
        <table>
            <tr>
                <td>
                    <b class="title">
                        <span class="text-primary">&lsqb;</span>
                        @lang('Quote')
                        <span class="text-primary">&rsqb;</span>
                    </b>
                </td>
                <td style="font-family:monospace;font-size:12px;padding-left:10px;padding-top:7px">
                    {{ Carbon\Carbon::now(config('app.timezone')) }}</td>
            </tr>
        </table>
        <table style="margin-top:1rem">
            <tr>
                <td style="height: 8rem">
                    @yield('recipient', '')
                </td>
                @hasSection('sender')
                    <td style="width:23rem"></td>
                    <td style="font-size:11px">
                        <div style="margin-top:3rem">@yield('sender', '')</div>
                    </td>
                @endif
            </tr>
        </table>

        @hasSection('subject')    
            <div style="margin-top:2rem;margin-bottom:2rem">
                @lang('Subject:') @yield('subject', '')
            </div>
        @endif

        @yield('text', '')
    </div>

    @hasSection('footer')
        <div style="position:absolute;bottom:0.5rem;left:0;right:0;padding-left:5rem;padding-right:5rem;font-size:10px">
            @yield('footer', '')
        </div>
    @endif
</body>
