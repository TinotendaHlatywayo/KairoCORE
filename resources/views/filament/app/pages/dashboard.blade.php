<x-filament-panels::page>
    @php
        $m = $metrics;
        $students = $m['students'] ?? [];
        $quickActions = $m['quickActions'] ?? [];
        $att = $m['attendance'] ?? [];
        $fees = $m['fees'] ?? [];
        $marks = $m['marks'] ?? [];
        $funnel = $m['funnel'] ?? [];
        $todo = $m['todo'] ?? [];
        $schedule = $m['schedule'] ?? [];
        $mini = $m['mini'] ?? [];
        $pend = $m['pendingAttendance'] ?? [];
        $alerts = $m['alerts'] ?? [];
        $feed = $m['feed'] ?? [];
        $kpis = $m['kpis'] ?? [];

        // Prototype icon set: the only hard-designed part of the sidebar and
        // quick actions kept from tinway-dashboard.html; every label, link
        // and colour pairing below comes from the live navigation registry.
        $icPaths = [
            'home' => 'M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z',
            'Admissions' => 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M12 12v6M9 15h6',
            'Students' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
            'Academics' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5zM4 19.5V21h16',
            'Exams' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
            'Finance' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
            'HR' => 'M3 7h18v13H3zM8 7V4h8v3',
            'Inventory' => 'M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
            'Library' => 'M6 3h12v18l-6-4-6 4z',
            'Boarding' => 'M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 18h18M3 15h18M7 9V6h4v3',
            'Health' => 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z',
            'Comms' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
            'LMS' => 'M12 3L2 8l10 5 10-5zM6 10.5V16c0 1.5 3 3 6 3s6-1.5 6-3v-5.5',
            'Website' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20',
            'Reports' => 'M18 20V10M12 20V4M6 20v-6',
            'System' => 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
            'Billing' => 'M2 5h20v14H2zM2 10h20',
            'cal' => 'M3 5h18v16H3zM3 10h18M8 3v4M16 3v4',
            'pay' => 'M2 5h20v14H2zM2 10h20M6 15h4',
            'mark' => 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
            'mega' => 'M3 11v2a1 1 0 0 0 1 1h3l7 4V6L7 10H4a1 1 0 0 0-1 1zM18 8a5 5 0 0 1 0 8',
        ];
        $ic = fn (string $k): string => '<svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="'.($icPaths[$k] ?? $icPaths['Reports']).'"/></svg>';

        $navPalette = [];
        foreach ($m['nav'] ?? [] as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        $navPalette[] = ['t' => $child['label'], 'g' => $group['label'], 'h' => $child['url']];
                    }
                } else {
                    $navPalette[] = ['t' => $item['label'], 'g' => $group['label'], 'h' => $item['url']];
                }
            }
        }

        $miniTiles = [
            [
                'on' => $mini['books']['visible'] ?? false,
                'label' => __('Books on loan'),
                'value' => $mini['books']['on_loan'] ?? 0,
                'line' => __(':count out this week', ['count' => $mini['books']['week'] ?? 0]),
                'tone' => (($mini['books']['week'] ?? 0) > 0) ? 'up' : 'sub',
                'spark' => $mini['books']['spark'] ?? '',
            ],
            [
                'on' => $mini['clinic']['visible'] ?? false,
                'label' => __('Clinic visits'),
                'value' => $mini['clinic']['week'] ?? 0,
                'line' => (($mini['clinic']['delta'] ?? 0) >= 0 ? '+' : '').($mini['clinic']['delta'] ?? 0).' '.__('vs last week'),
                'tone' => (($mini['clinic']['delta'] ?? 0) > 0) ? 'up' : 'dn',
                'spark' => $mini['clinic']['spark'] ?? '',
            ],
            [
                'on' => $mini['boarders']['visible'] ?? false,
                'label' => __('Boarders'),
                'value' => $mini['boarders']['active'] ?? 0,
                'line' => __('of :count beds', ['count' => $mini['boarders']['beds'] ?? 0]),
                'tone' => 'sub',
                'spark' => $mini['boarders']['spark'] ?? '',
            ],
            [
                'on' => $mini['tickets']['visible'] ?? false,
                'label' => __('Open tickets'),
                'value' => $mini['tickets']['open'] ?? 0,
                'line' => __(':count closed today', ['count' => $mini['tickets']['closed_today'] ?? 0]),
                'tone' => (($mini['tickets']['closed_today'] ?? 0) > 0) ? 'up' : 'sub',
                'spark' => $mini['tickets']['spark'] ?? '',
            ],
        ];

        $statusTone = ['none' => 's-b', 'partial' => 's-w', 'marked' => 's-o'];
        $statusLabel = [
            'none' => __('Not marked'),
            'partial' => __('Partly marked'),
            'marked' => __('Marked'),
        ];
    @endphp

    <div class="tw-dash" data-lang="{{ str_replace('_', '-', app()->getLocale()) }}">
        <style>
            .tw-dash{--ink:#141a33;--mute:#6b7290;--line:#e8eaf3;--bg:#f3f4fa;--card:#fff;--soft:#f5f6fb;--ac:#5b57f0;--ac2:#8b5cf6;--acs:#eeedfe;--ok:#18a272;--warn:#e8920c;--bad:#dc4545;--sh:0 1px 2px rgba(20,26,51,.04),0 8px 24px -12px rgba(20,26,51,.12);--r:20px;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;border-radius:var(--r);margin:8px 0 24px;-webkit-font-smoothing:antialiased}
            html.dark .tw-dash{--ink:#eceefb;--mute:#9aa1c2;--line:#262c4a;--bg:#0d1022;--card:#151a33;--soft:#1b2140;--ac:#7c78ff;--acs:#23284f;--sh:0 1px 2px rgba(0,0,0,.3),0 8px 24px -12px rgba(0,0,0,.5)}
            .tw-dash a{color:inherit;text-decoration:none}
            .tw-dash button{font:inherit;color:inherit}
            .tw-dash :focus-visible{outline:2px solid var(--ac);outline-offset:2px}
            .tw-dash svg.i{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;flex:none}

            /* This page now uses Filament's own sidebar; only the dashboard
               content is rendered here. */

            /* Sidebar */
            .tw-dash .tw-side{position:fixed;left:0;top:0;bottom:0;width:268px;flex:none;background:var(--card);border-right:1px solid var(--line);display:flex;flex-direction:column;z-index:40}
            .tw-dash .brand{display:flex;align-items:center;gap:12px;padding:20px 20px 14px}
            .tw-dash .brand i{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--ac),var(--ac2));color:#fff;display:grid;place-items:center;font-style:normal;font-weight:800;font-size:17px}
            .tw-dash .brand b{display:block;font-size:15.5px;font-weight:800;line-height:1.2}.tw-dash .brand span{font-size:12px;color:var(--mute)}
            .tw-dash .search{padding:6px 16px 10px;position:relative}
            .tw-dash .search svg{position:absolute;left:28px;top:50%;transform:translateY(-62%);color:var(--mute);width:16px;height:16px}
            .tw-dash .search input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--line);border-radius:12px;font:inherit;background:var(--soft);color:var(--ink)}
            .tw-dash .search input:focus{border-color:var(--ac);outline:none;background:var(--card);box-shadow:0 0 0 3px var(--acs)}
            .tw-dash nav{overflow-y:auto;padding:4px 12px 24px;flex:1}
            .tw-dash nav::-webkit-scrollbar{width:6px}.tw-dash nav::-webkit-scrollbar-thumb{background:var(--line);border-radius:3px}
            .tw-dash .home{display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:12px;font-weight:700;background:var(--acs);color:var(--ac);margin-bottom:2px}
            .tw-dash .home+.home{background:none;color:inherit;font-weight:600}
            .tw-dash .lab{font-size:11.5px;color:var(--mute);font-weight:600;padding:10px 10px 4px}
            .tw-dash .g>button{width:100%;display:flex;align-items:center;gap:12px;border:0;background:none;font-weight:600;padding:7px 10px;border-radius:12px;cursor:pointer;text-align:left}
            .tw-dash .g>button:hover{background:var(--soft)}
            .tw-dash .g>button i{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c)}
            .tw-dash .g>button em{margin-left:auto;font-style:normal;color:var(--mute);font-size:12px;transition:transform .15s}
            .tw-dash .g.open>button em{transform:rotate(90deg)}
            .tw-dash .g ul{list-style:none;display:none;margin:2px 0 6px 25px;padding-left:16px;border-left:1px solid var(--line)}
            .tw-dash .g.open ul{display:block;animation:tw-fadein .2s}
            .tw-dash .g li a{display:block;padding:5px 10px;border-radius:8px;color:var(--mute);font-size:13px}
            .tw-dash .g li a:hover{background:var(--acs);color:var(--ac)}
            .tw-dash .g li a[aria-current=page]{color:var(--ac);font-weight:700}
            .tw-dash .g.hide,.tw-dash .g li.hide{display:none}

            /* Content */
            .tw-dash .tw-top{display:flex;align-items:center;gap:10px;padding:6px 4px 0}
            .tw-dash .tw-menu{display:none}
            .tw-dash .ib{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:var(--card);display:grid;place-items:center;cursor:pointer;color:var(--mute)}
            .tw-dash .ib:hover{color:var(--ac);border-color:var(--ac)}
            .tw-dash .date{font-weight:600;color:var(--mute)}
            .tw-dash .sp{flex:1}
            .tw-dash .wrap{padding:6px 4px 40px;max-width:1380px}
            .tw-dash h1{font-size:32px;font-weight:800;letter-spacing:-.025em;line-height:1.15;margin:10px 0 2px}
            .tw-dash .sub{color:var(--mute)}
            .tw-dash .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:20px;margin-top:24px}
            .tw-dash .c4{grid-column:span 4}.tw-dash .c5{grid-column:span 5}.tw-dash .c7{grid-column:span 7}.tw-dash .c8{grid-column:span 8}
            .tw-dash .card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:22px;box-shadow:var(--sh);min-width:0}
            .tw-dash .card h2{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap}
            .tw-dash .card h2 a{margin-left:auto;font-size:12.5px;font-weight:600;color:var(--ac);padding:3px 10px;border-radius:999px;background:var(--acs)}
            .tw-dash .card h2 a:first-child{margin-left:auto}
            .tw-dash .tag{font-size:10.5px;font-weight:600;color:var(--mute);background:var(--soft);border-radius:999px;padding:1px 8px}
            .tw-dash .empty{color:var(--mute);font-size:13px;padding:18px 0 8px}

            /* Hero */
            .tw-dash .dark{background:linear-gradient(135deg,#1d1b5e 0%,#3a2fa8 55%,#6a46d9 100%);color:#fff;border:0;position:relative;overflow:hidden}
            .tw-dash .dark:after{content:"";position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.07)}
            .tw-dash .hero{display:grid;grid-template-columns:1fr 1.1fr;gap:28px;align-items:center;position:relative;z-index:1}
            .tw-dash .big{font-size:68px;font-weight:800;letter-spacing:-.04em;line-height:1}
            .tw-dash .split{display:flex;height:12px;border-radius:7px;overflow:hidden;margin:18px 0 10px;gap:3px}
            .tw-dash .split i{display:block;border-radius:7px}
            .tw-dash .key{display:flex;gap:18px;font-size:13px;color:var(--mute);flex-wrap:wrap}
            .tw-dash .card:not(.dark) .key{color:var(--mute)}
            .tw-dash .key b{color:var(--ink);font-weight:700}.tw-dash .dark .key b{color:#fff}
            .tw-dash .dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px}
            .tw-dash .staff{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:20px}
            .tw-dash .staff div{background:rgba(255,255,255,.12);border-radius:14px;padding:10px 12px}
            .tw-dash .staff b{font-size:22px;display:block;line-height:1.2}.tw-dash .staff span{font-size:12px;color:rgba(255,255,255,.7)}
            .tw-dash .grade-list{display:grid;gap:8px;margin-top:10px;max-height:260px;overflow:auto;padding-right:4px}
            .tw-dash .grade-row{display:grid;grid-template-columns:minmax(0,1fr) 2fr auto;gap:10px;align-items:center}
            .tw-dash .grade-name{font-size:12.5px;color:rgba(255,255,255,.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
            .tw-dash .grade-bar{height:8px;border-radius:999px;background:rgba(255,255,255,.12);overflow:hidden}
            .tw-dash .grade-bar i{display:block;height:100%;border-radius:999px;background:#fff;opacity:.85}
            .tw-dash .grade-count{font-size:12.5px;font-weight:700;color:#fff;min-width:2.5rem;text-align:right}
            .tw-dash .dark .gl{font-size:12.5px;color:rgba(255,255,255,.75);margin-bottom:4px}
            .tw-dash .dark svg text{fill:rgba(255,255,255,.75)}

            /* Quick actions */
            .tw-dash .q{display:grid;grid-template-columns:1fr 1fr;gap:10px}
            .tw-dash .q a{border:1px solid var(--line);border-radius:16px;padding:12px;font-weight:700;font-size:13px;background:var(--soft);display:flex;flex-direction:column;gap:8px;transition:transform .15s,border-color .15s}
            .tw-dash .q a:hover{border-color:var(--ac);transform:translateY(-2px)}
            .tw-dash .q a i{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 15%,transparent);color:var(--c)}
            .tw-dash .q a small{display:block;font-weight:500;color:var(--mute);font-size:11.5px;margin-top:-1px}

            /* Widgets */
            .tw-dash .donut{display:flex;align-items:center;gap:22px}
            .tw-dash .donut svg{width:136px;height:136px;flex:none}
            .tw-dash .donut .mid{font-size:26px;font-weight:800;fill:var(--ink)}
            .tw-dash .num{font-size:32px;font-weight:800;letter-spacing:-.02em;line-height:1.1}
            .tw-dash .rows{list-style:none;padding:0;margin:0}
            .tw-dash .rows li{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--line)}
            .tw-dash .rows li:last-child{border:0;padding-bottom:0}
            .tw-dash .rows .n{min-width:36px;text-align:center;font-weight:800;background:var(--acs);color:var(--ac);border-radius:10px;padding:3px 0}
            .tw-dash .rows .go{margin-left:auto;color:var(--ac);font-weight:700;font-size:12.5px;padding:4px 12px;border-radius:999px;background:var(--acs)}
            .tw-dash .rows small{display:block;color:var(--mute);font-size:12px}
            .tw-dash .t{width:50px;font-weight:700;font-size:12.5px;color:var(--ac);flex:none}
            .tw-dash .bar{display:flex;align-items:center;gap:10px;margin:12px 0;font-size:13px}
            .tw-dash .bar span:first-child{width:90px;flex:none;color:var(--mute)}
            .tw-dash .bar div{flex:1;height:10px;background:var(--soft);border-radius:6px;overflow:hidden}
            .tw-dash .bar div i{display:block;height:100%;border-radius:6px;transform-origin:left;animation:tw-grow .9s cubic-bezier(.2,.8,.2,1) both}
            .tw-dash .bar b{width:42px;text-align:right}
            .tw-dash .prog{height:12px;background:var(--soft);border-radius:7px;overflow:hidden;margin:10px 0 8px}
            .tw-dash .prog i{display:block;height:100%;background:linear-gradient(90deg,var(--ok),#4fd1a1);border-radius:7px;transform-origin:left;animation:tw-grow 1s cubic-bezier(.2,.8,.2,1) both}
            .tw-dash .fun div{transform-origin:left;animation:tw-grow .8s cubic-bezier(.2,.8,.2,1) both;height:38px;border-radius:12px;color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;padding:0 14px;margin-bottom:8px}
            .tw-dash .fun div:nth-child(2){animation-delay:.12s}.tw-dash .fun div:nth-child(3){animation-delay:.24s}
            .tw-dash .mini{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
            .tw-dash .mini>div{background:var(--soft);border-radius:16px;padding:14px}
            .tw-dash .mini .sub{font-size:12.5px}
            .tw-dash .mini svg.spk{width:100%;height:30px;margin-top:6px;display:block}
            .tw-dash .up{color:var(--ok);font-weight:700;font-size:12px}.tw-dash .dn{color:var(--bad);font-weight:700;font-size:12px}
            .tw-dash svg text{font-family:inherit;fill:var(--mute);font-size:11px}
            .tw-dash .gb rect{transform-box:fill-box;transform-origin:bottom;animation:tw-grow-y .8s cubic-bezier(.2,.8,.2,1) both}
            .tw-dash .gb g:hover rect{opacity:1!important;filter:brightness(1.15)}
            @keyframes tw-grow{from{transform:scaleX(0)}}
            @keyframes tw-grow-y{from{transform:scaleY(0)}}
            .tw-dash .seed{display:flex;gap:16px;align-items:center;border-style:dashed;background:color-mix(in srgb,var(--warn) 8%,var(--card));border-color:color-mix(in srgb,var(--warn) 35%,var(--line));margin-top:20px;box-shadow:none;flex-wrap:wrap}
            .tw-dash .seed p{flex:1;color:var(--mute);font-size:13px;min-width:220px}
            .tw-dash .seed button{border:0;background:var(--warn);color:#fff;font-weight:700;padding:10px 18px;border-radius:12px;cursor:pointer}
            .tw-dash,.tw-dash .card,.tw-dash .tw-side{transition:background-color .3s,color .3s,border-color .3s}
            @keyframes tw-rise{from{opacity:0;transform:translateY(14px)}}
            @keyframes tw-fadein{from{opacity:0;transform:translateY(-4px)}}
            @keyframes tw-pop{from{opacity:0;transform:scale(.96) translateY(-4px)}}
            @keyframes tw-draw{from{stroke-dashoffset:1}}
            @keyframes tw-out{to{opacity:0;transform:translateX(30px)}}
            .tw-dash .kpis>.card,.tw-dash .grid>.card{animation:tw-rise .6s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*55ms)}
            .tw-dash .ln{stroke-dasharray:1;animation:tw-draw 1.2s cubic-bezier(.4,0,.2,1) both}
            .tw-dash .ar{animation:tw-fadein 1s .3s both}
            .tw-dash .gb g{cursor:pointer;outline:none}.tw-dash .gb g:focus rect{filter:brightness(1.2)}
            .tw-dash .sg{transition:stroke-width .15s;cursor:pointer;outline:none}.tw-dash .sg:hover,.tw-dash .sg:focus{stroke-width:19}
            .tw-dash .fchart circle{transition:r .15s;cursor:pointer;outline:none}.tw-dash .fchart circle:hover,.tw-dash .fchart circle:focus{r:7}
            .tw-dash .fun div{cursor:default;transition:filter .15s}.tw-dash .fun div:hover{filter:brightness(1.12)}
            .tw-dash #tw-tip{position:fixed;z-index:60;pointer-events:none;background:var(--ink);color:var(--card);font-size:12px;font-weight:600;padding:5px 10px;border-radius:8px;opacity:0;transform:translateY(4px);transition:opacity .12s,transform .12s;white-space:nowrap}.tw-dash #tw-tip.on{opacity:1;transform:none}
            .tw-dash .seg{display:inline-flex;background:var(--soft);border-radius:999px;padding:2px;margin-left:6px}
            .tw-dash .seg button{border:0;background:none;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;cursor:pointer;color:var(--mute)}
            .tw-dash .seg button[aria-pressed=true]{background:var(--card);color:var(--ac);box-shadow:0 1px 3px rgba(0,0,0,.12)}
            .tw-dash #tw-toast{position:fixed;left:50%;bottom:28px;transform:translate(-50%,20px);background:var(--ink);color:var(--card);padding:12px 18px;border-radius:14px;font-weight:600;font-size:13px;display:flex;gap:14px;align-items:center;opacity:0;pointer-events:none;transition:.25s;z-index:80;max-width:calc(100vw - 32px)}
            .tw-dash #tw-toast.on{opacity:1;transform:translate(-50%,0);pointer-events:auto}
            .tw-dash #tw-toast button{border:0;background:none;color:#a9a6ff;font-weight:700;cursor:pointer}
            .tw-dash .x{border:0;background:none;color:var(--mute);cursor:pointer;font-size:18px;line-height:1;padding:2px 6px;border-radius:6px}.tw-dash .x:hover{color:var(--bad);background:var(--soft)}
            .tw-dash .rows li.out{animation:tw-out .3s forwards}
            .tw-dash .seed button:disabled{opacity:.7;cursor:progress}
            .tw-dash .tw-skip{position:absolute;left:-999px;top:8px;background:var(--ac);color:#fff;padding:8px 14px;border-radius:10px;z-index:50}.tw-dash .tw-skip:focus{left:8px}
            .tw-dash .chip{font-size:12px;font-weight:700;color:var(--ac);background:var(--acs);border-radius:999px;padding:3px 12px}
            .tw-dash .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:24px}
            .tw-dash .kpi{display:flex;gap:14px;align-items:center;padding:18px 20px}
            .tw-dash .kpi i{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c);flex:none}
            .tw-dash .kpi i svg{width:22px;height:22px}
            .tw-dash .kpi b{display:block;font-size:26px;font-weight:800;letter-spacing:-.02em;line-height:1.1}
            .tw-dash .kpi span{font-size:12.5px;color:var(--mute)}
            .tw-dash .kpi em{font-style:normal;font-size:12px;font-weight:700;margin-left:auto;align-self:flex-start;text-align:right;white-space:nowrap}
            .tw-dash .pb{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line);font-size:13px}
            .tw-dash .pb:last-child{border:0}.tw-dash .pb b{width:62px;flex:none}.tw-dash .pb span{margin-left:auto;font-size:12px;font-weight:700;padding:2px 10px;border-radius:999px}
            .tw-dash .s-w{background:color-mix(in srgb,var(--warn) 16%,transparent);color:var(--warn)}.tw-dash .s-b{background:color-mix(in srgb,var(--bad) 14%,transparent);color:var(--bad)}.tw-dash .s-o{background:color-mix(in srgb,var(--ok) 14%,transparent);color:var(--ok)}
            .tw-dash .feed li{align-items:flex-start}.tw-dash .feed .dt{width:9px;height:9px;border-radius:50%;background:var(--ac);margin-top:6px;flex:none}
            .tw-dash .feed time{margin-left:auto;font-size:12px;color:var(--mute);white-space:nowrap}
            .tw-dash .tw-veil{position:fixed;inset:0;background:rgba(10,12,30,.5);z-index:39;display:none}
            .tw-dash #tw-cp{position:fixed;inset:0;background:rgba(10,12,30,.5);backdrop-filter:blur(3px);z-index:70;display:grid;place-items:start center;padding-top:14vh}
            .tw-dash #tw-cp[hidden]{display:none}
            .tw-dash .cpbox{width:min(560px,92vw);background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 30px 80px -20px rgba(0,0,0,.5);overflow:hidden;animation:tw-pop .18s}
            .tw-dash .cpbox input{width:100%;border:0;border-bottom:1px solid var(--line);padding:18px 20px;font:inherit;font-size:16px;background:transparent;color:var(--ink);outline:none}
            .tw-dash .cpbox ul{list-style:none;max-height:340px;overflow:auto;padding:8px;margin:0}
            .tw-dash .cpbox li{display:flex;justify-content:space-between;gap:12px;padding:10px 12px;border-radius:10px;cursor:pointer}
            .tw-dash .cpbox li small{color:var(--mute)}.tw-dash .cpbox li.sel{background:var(--acs);color:var(--ac)}
            @media(max-width:1150px){.tw-dash .kpis{grid-template-columns:1fr 1fr}.tw-dash .c4,.tw-dash .c5,.tw-dash .c7,.tw-dash .c8{grid-column:span 12}.tw-dash .mini{grid-template-columns:1fr 1fr}.tw-dash .hero{grid-template-columns:1fr}}
            @media(max-width:560px){.tw-dash .kpis{grid-template-columns:1fr}}
            @media(max-width:1023px){
                .tw-dash .tw-side{left:-290px;transition:left .2s}
                html.tw-nav-open .tw-dash .tw-side{left:0;box-shadow:8px 0 40px rgba(10,12,30,.35)}
                html.tw-nav-open .tw-dash .tw-veil{display:block}
                .tw-dash .tw-menu{display:grid}
                .tw-dash .big{font-size:52px}.tw-dash .donut{flex-direction:column;align-items:flex-start}.tw-dash h1{font-size:26px}}
            @media(prefers-reduced-motion:reduce){.tw-dash *,.tw-dash *::before,.tw-dash *::after{transition:none!important;animation:none!important}}
        </style>

        <a class="tw-skip" href="#tw-content">{{ __('Skip to dashboard') }}</a>

        <script type="application/json" id="tw-nav-json">{!! json_encode($navPalette, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

        <div class="tw-body">
            <header class="tw-top">
                <button class="ib tw-menu" id="tw-menu" aria-label="{{ __('Open menu') }}" aria-controls="tw-nav">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                </button>
                <span class="date" id="tw-date"></span>
                @if (! empty($m['termLabel']))
                    <span class="chip">{{ $m['termLabel'] }}</span>
                @endif
                <div class="sp"></div>
                <button class="ib" id="tw-cpb" aria-label="{{ __('Quick find') }} (Ctrl + K)">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                </button>
            </header>

            <div class="wrap" id="tw-content">
                <h1>{{ $greeting }}</h1>
                <p class="sub">{{ __('Here’s how :school is doing today.', ['school' => $school?->name]) }}</p>

                {{-- KPI strip --}}
                <div class="kpis" id="tw-kpis">
                    @foreach ($kpis as $i => $kpi)
                        <div class="card kpi" style="--c: {{ $kpi['color'] }};--i: {{ $i }}">
                            <i>{!! $ic($kpi['icon']) !!}</i>
                            <div>
                                <b data-countup>{{ $kpi['value'] }}{{ $kpi['suffix'] ?? '' }}</b>
                                <span>{{ $kpi['label'] }}</span>
                            </div>
                            @if ($kpi['delta'] !== null)
                                <em class="{{ $kpi['delta'] >= 0 ? 'up' : 'dn' }}">{{ ($kpi['delta'] >= 0 ? '+' : '').$kpi['delta'] }}{{ $kpi['suffix'] ?? '' }} {{ $kpi['deltaLabel'] }}</em>
                            @elseif ($kpi['note'])
                                <em class="{{ $kpi['noteTone'] === 'dn' ? 'dn' : 'up' }}">{{ $kpi['note'] }}</em>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="grid" style="margin-top:20px">
                    {{-- Students hero --}}
                    @if (! empty($students['visible']))
                        <section class="card dark c7" style="--i:0">
                            <h2>{{ __('Students') }}</h2>
                            <div class="hero">
                                <div>
                                    <div class="big" data-countup>{{ $students['total'] }}</div>
                                    @if ($students['total'] > 0)
                                        <div class="split" role="img" aria-label="{{ __(':girls girls, :boys boys', ['girls' => $students['girls'], 'boys' => $students['boys']]) }}">
                                            <i style="width: {{ $students['total'] > 0 ? round($students['girls'] / $students['total'] * 100) : 50 }}%;background:#ff7ab0"></i>
                                            <i style="width: {{ $students['total'] > 0 ? round($students['boys'] / $students['total'] * 100) : 50 }}%;background:#6db4ff"></i>
                                        </div>
                                        <div class="key">
                                            <span><span class="dot" style="background:#ff7ab0"></span><b>{{ $students['girls'] }}</b> {{ __('girls') }}</span>
                                            <span><span class="dot" style="background:#6db4ff"></span><b>{{ $students['boys'] }}</b> {{ __('boys') }}</span>
                                        </div>
                                    @endif
                                    <div class="staff">
                                        <div><b data-countup>{{ $students['teaching'] }}</b><span>{{ __('Teaching') }}</span></div>
                                        <div><b data-countup>{{ $students['nonTeaching'] }}</b><span>{{ __('Non-teaching') }}</span></div>
                                        <div><b data-countup>{{ $students['admins'] }}</b><span>{{ __('Admins') }}</span></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="gl">{{ __('Students per grade') }}</div>
                                    @if (! empty($students['gradeList']))
                                        @php $gradeMax = max(array_column($students['gradeList'], 'value')) ?: 1; @endphp
                                        <div class="grade-list">
                                            @foreach ($students['gradeList'] as $grade)
                                                @php $gradePct = (int) round(((int) $grade['value'] / $gradeMax) * 100); @endphp
                                                <div class="grade-row">
                                                    <div class="grade-name" title="{{ $grade['label'] }}">{{ $grade['label'] }}</div>
                                                    <div class="grade-bar"><i style="width: {{ $gradePct }}%"></i></div>
                                                    <div class="grade-count">{{ number_format((int) $grade['value']) }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="gl" style="margin-top:10px">{{ __('No students assigned to classes yet. ') }}<a href="{{ \App\Filament\App\Resources\StudentResource::getUrl('index') }}" style="text-decoration:underline">{{ __('Enrol students') }}</a></div>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endif

                    {{-- Quick actions --}}
                    @if ($quickActions !== [])
                        <section class="card c5" style="--i:1">
                            <h2>{{ __('Quick actions') }}</h2>
                            <div class="q">
                                @foreach ($quickActions as $action)
                                    <a href="{{ $action['url'] }}" style="--c: {{ $action['color'] }}">
                                        <i>{!! $ic($action['icon']) !!}</i>
                                        <span>{{ $action['title'] }}<small>{{ $action['sub'] }}</small></span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Attendance donut --}}
                    @if (! empty($att['visible']))
                        <section class="card c4" style="--i:2">
                            <h2>{{ __('Attendance today') }}<a href="{{ $att['url'] }}">{{ __('Open') }}</a></h2>
                            @if ($att['total'] > 0)
                                <div class="donut">
                                    {!! $att['chart'] !!}
                                    <div class="key" style="flex-direction:column;gap:8px">
                                        <span><span class="dot" style="background:var(--ok)"></span><b>{{ $att['present'] }}</b> {{ __('present') }}</span>
                                        <span><span class="dot" style="background:var(--warn)"></span><b>{{ $att['late'] }}</b> {{ __('late') }}</span>
                                        <span><span class="dot" style="background:var(--bad)"></span><b>{{ $att['absent'] }}</b> {{ __('absent') }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="empty">{{ __('No attendance has been recorded for today yet. Mark it from a lesson on the timetable.') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- Fees --}}
                    @if (! empty($fees['visible']))
                        <section class="card c8" style="--i:3">
                            <h2>
                                {{ __('Fees collected this year') }}
                                <span class="seg" id="tw-fseg">
                                    <button type="button" aria-pressed="true" data-m="c">{{ __('Total') }}</button>
                                    <button type="button" aria-pressed="false" data-m="m">{{ __('Monthly') }}</button>
                                </span>
                                <a href="{{ $fees['statementsUrl'] }}">{{ __('Statements') }}</a>
                            </h2>
                            <div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-end">
                                <div><div class="num" data-countup data-prefix="$">${{ number_format($fees['collected'], 0) }}</div><div class="sub">{{ __('of :amount billed', ['amount' => '$'.number_format($fees['billed'], 0)]) }}</div></div>
                                <div style="flex:1;min-width:200px"><div class="prog"><i style="width: {{ min(100, $fees['percent']) }}%"></i></div><div class="key"><span><b>{{ $fees['percent'] }}%</b> {{ __('collected') }}</span><span><b>${{ number_format($fees['outstanding'], 0) }}</b> {{ __('outstanding') }}</span></div></div>
                            </div>
                            @if ($fees['hasChart'])
                                <div class="fchart" style="margin-top:14px" id="tw-fees-c" data-mode="c">{!! $fees['totalChart'] !!}</div>
                                <div class="fchart" style="margin-top:14px" id="tw-fees-m" data-mode="m" hidden>{!! $fees['monthlyChart'] !!}</div>
                            @else
                                <div class="empty">{{ __('Fees will chart here as soon as two months of payments are recorded.') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- Needs attention --}}
                    @if (! empty($todo['visible']))
                        <section class="card c4" style="--i:4">
                            <h2>{{ __('Needs your attention') }}</h2>
                            <ul class="rows" id="tw-todo">
                                @foreach ($todo['rows'] as $i => $row)
                                    <li data-i="{{ $i }}">
                                        <span class="n">{{ $row['count'] }}</span>
                                        <span>{{ $row['label'] }}<small>{{ $row['area'] }}</small></span>
                                        <a class="go" href="{{ $row['url'] }}">{{ $row['verb'] }}</a>
                                        <button type="button" class="x" aria-label="{{ __('Dismiss: :label', ['label' => $row['label']]) }}">×</button>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($todo['rows'] === [])
                                <div class="empty">{{ __('You’re all caught up.') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- Marks by subject --}}
                    @if (! empty($marks['visible']))
                        <section class="card c4" style="--i:5">
                            <h2>{{ __('Average mark by subject') }}<a href="{{ $marks['url'] }}">{{ __('Analytics') }}</a></h2>
                            @if ($marks['subjects'] !== [])
                                @foreach ($marks['subjects'] as $subject)
                                    <div class="bar">
                                        <span>{{ $subject['name'] }}</span>
                                        <div><i style="width: {{ min(100, $subject['average']) }}%;animation-delay: {{ $loop->index * 80 }}ms;background: {{ $subject['average'] < 60 ? 'var(--bad)' : ($subject['average'] < 70 ? 'var(--warn)' : 'var(--ok)') }}"></i></div>
                                        <b>{{ $subject['average'] }}%</b>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty">{{ __('No marks have been entered for the current term yet.') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- Admissions pipeline --}}
                    @if (! empty($funnel['visible']) && $funnel['applications'] > 0)
                        <section class="card c4" style="--i:6">
                            <h2>{{ __('Admissions pipeline') }}<a href="{{ $funnel['url'] }}">{{ __('Open') }}</a></h2>
                            <div class="fun">
                                @php
                                    $funnelRows = [
                                        [__('Applications'), $funnel['applications'], '#5b57f0'],
                                        [__('Documents verified'), $funnel['verified'], '#7d68f3'],
                                        [__('Enrolled'), $funnel['enrolled'], '#18a272'],
                                    ];
                                @endphp
                                @foreach ($funnelRows as $row)
                                    <div tabindex="0" data-tip="{{ $row[0] }} · {{ round($row[1] / max(1, $funnel['applications']) * 100) }}%" style="width: {{ round($row[1] / max(1, $funnel['applications']) * 100) }}%;min-width:160px;background: {{ $row[2] }}">{{ $row[1] }} · {{ $row[0] }}</div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Today's schedule --}}
                    @if (! empty($schedule['visible']))
                        <section class="card c5" style="--i:7">
                            <h2>{{ __('Today’s schedule') }}<a href="{{ $schedule['url'] }}">{{ __('Full schedule') }}</a></h2>
                            @if ($schedule['rows'] !== [])
                                <ul class="rows">
                                    @foreach ($schedule['rows'] as $row)
                                        <li><span class="t">{{ $row['time'] }}</span><span>{{ $row['title'] }}<small>{{ $row['sub'] }}</small></span></li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="empty">{{ __('Nothing scheduled for today. Add events or timetable lessons to fill this list.') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- This week across the school --}}
                    @if (collect($miniTiles)->contains(fn ($tile) => $tile['on']))
                        <section class="card c7" style="--i:8">
                            <h2>{{ __('This week across the school') }}</h2>
                            <div class="mini">
                                @foreach ($miniTiles as $tile)
                                    @if ($tile['on'])
                                        <div>
                                            <div class="sub">{{ $tile['label'] }}</div>
                                            <div class="num" data-countup>{{ $tile['value'] }}</div>
                                            <span class="{{ $tile['tone'] }}">{{ $tile['line'] }}</span>
                                            {!! $tile['spark'] !!}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Attendance not marked yet --}}
                    @if (! empty($pend['visible']))
                        <section class="card c4" style="--i:9">
                            <h2>{{ __('Attendance not marked yet') }}<a href="{{ \App\Filament\App\Resources\TimetableLessonResource::getUrl('index') }}">{{ __('Mark now') }}</a></h2>
                            @if ($pend['rows'] !== [])
                                @foreach ($pend['rows'] as $row)
                                    <div class="pb">
                                        <b style="width:70px">{{ $row['class'] }}</b>
                                        <small class="sub">{{ trim(($row['course'] ? $row['course'].' · ' : '').($row['teacher'] ?: __('No class teacher'))) }}</small>
                                        <span class="{{ $statusTone[$row['status']] }}">{{ $statusLabel[$row['status']] }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty">{{ __('Every class on today’s timetable has been marked. ') }}</div>
                            @endif
                        </section>
                    @endif

                    {{-- Alerts --}}
                    @if ($alerts['rows'] !== [])
                        <section class="card c4" style="--i:10">
                            <h2>{{ __('Alerts and school health') }}</h2>
                            @foreach ($alerts['rows'] as $row)
                                <div class="pb">
                                    <b style="width:88px">{{ $row['area'] }}</b>
                                    <small class="sub">{{ $row['text'] }}</small>
                                    <span class="{{ $row['tone'] }}" style="white-space:nowrap"><a href="{{ $row['url'] }}">{{ $row['action'] }}</a></span>
                                </div>
                            @endforeach
                        </section>
                    @endif

                    {{-- Recent activity --}}
                    @if (! empty($feed['visible']))
                        <section class="card c4" style="--i:11">
                            <h2>{{ __('Recent activity') }}<a href="{{ $feed['url'] }}">{{ __('Audit log') }}</a></h2>
                            @if ($feed['rows'] !== [])
                                <ul class="rows feed">
                                    @foreach ($feed['rows'] as $row)
                                        <li><i class="dt"></i><span>{{ $row['text'] }}<small>{{ $row['sub'] }}</small></span><time>{{ $row['time'] }}</time></li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="empty">{{ __('Recent activity will appear here as people use the system.') }}</div>
                            @endif
                        </section>
                    @endif
                </div>

                {{-- Demo data --}}
                @php
                    $isWipe = $hasDemoData;
                    $btnLabel = $isWipe ? __('Wipe demo data') : __('Seed demo data');
                    $btnLoading = $isWipe ? 'wipeDemoData' : 'seedDemoData';
                    $btnAction = $isWipe ? 'wipeDemoData' : 'seedDemoData';
                @endphp
                <section class="card seed">
                    <p>
                        <b>{{ __('Demo data.') }}</b>
                        @if ($hasDemoData)
                            {{ __(':students demo students with :reports academic reports, enrollments and marks are loaded across the system.', ['students' => $demoStats['students'], 'reports' => $demoStats['reports']]) }}
                        @else
                            {{ __('Fill the system with sample students, classes, marks and reports so you can test every area.') }}
                        @endif
                    </p>
                    <div x-data="{ seeding: @js($isSeeding), progress: @js($seedProgress), stage: @js($seedStage ?: __('Initializing...')) }"
                         @if ($isSeeding) wire:poll.500ms="pollProgress" @endif
                         style="min-width:260px">
                        <button type="button"
                                wire:click="{{ $btnAction }}"
                                wire:loading.attr="disabled"
                                wire:target="{{ $btnLoading }}"
                                @if ($isWipe) x-on:click="if (!confirm(@js(__('Remove ALL demonstration students, enrollments, marks and reports? Real records are never touched.')))) return false;" @endif
                                {{ $isWipe ? 'style="background:var(--bad)"' : '' }}>
                            <span wire:loading.remove wire:target="{{ $btnLoading }}">{{ $btnLabel }}</span>
                            <span wire:loading wire:target="{{ $btnLoading }}">{{ $isWipe ? __('Removing…') : __('Seeding…') }}</span>
                        </button>
                        <div x-show="seeding || @js($isSeeding)" x-cloak style="margin-top:10px">
                            <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:var(--warn)">
                                <span>{{ $seedStage ?: __('Initializing Academic Structure…') }}</span>
                                <span>{{ $seedProgress }}%</span>
                            </div>
                            <div class="prog"><i style="width: {{ $seedProgress }}%;background:linear-gradient(90deg,var(--warn),var(--ok))"></i></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div id="tw-tip" role="tooltip"></div>
        <div id="tw-toast" role="status" aria-live="polite"></div>
        <div id="tw-cp" hidden>
            <div class="cpbox" role="dialog" aria-label="{{ __('Quick find') }}">
                <input id="tw-cpi" placeholder="{{ __('Jump to a page…') }}" autocomplete="off" aria-label="{{ __('Quick find') }}">
                <ul id="tw-cpl"></ul>
            </div>
        </div>
    </div>

    @script
        <script>
            (() => {
                const root = document.querySelector('.tw-dash');
                if (! root) return;
                const $ = (sel) => root.querySelector(sel);
                const $$ = (sel) => [...root.querySelectorAll(sel)];

                /* Sidebar groups: expand / collapse */
                const nav = $('#tw-nav');
                if (nav) {
                    nav.addEventListener('click', (e) => {
                        const button = e.target.closest('.g>button');
                        if (! button) return;
                        const group = button.parentNode;
                        group.classList.toggle('open');
                        button.setAttribute('aria-expanded', group.classList.contains('open'));
                    });

                    /* Sidebar search filter */
                    const q = $('#tw-q');
                    q?.addEventListener('input', () => {
                        const value = q.value.trim().toLowerCase();
                        $$('.g').forEach((group) => {
                            const groupHit = ! value || group.dataset.t.includes(value);
                            let any = false;
                            group.querySelectorAll('li').forEach((li) => {
                                const hit = groupHit || (li.dataset.t || '').includes(value);
                                li.classList.toggle('hide', ! hit);
                                any = any || hit;
                            });
                            group.classList.toggle('hide', ! any);
                            if (value && any) group.classList.add('open');
                        });
                    });
                }

                /* Off-canvas menu on small screens */
                const menu = $('#tw-menu');
                const setNav = (open) => {
                    document.documentElement.classList.toggle('tw-nav-open', open);
                    menu?.setAttribute('aria-expanded', String(open));
                };
                menu?.addEventListener('click', () => setNav(! document.documentElement.classList.contains('tw-nav-open')));
                $('.tw-veil')?.addEventListener('click', () => setNav(false));

                /* Keyboard shortcuts */
                document.addEventListener('keydown', (e) => {
                    if (e.key === '/' && ! /INPUT|TEXTAREA/.test(document.activeElement?.tagName || '')) {
                        e.preventDefault();
                        $('#tw-q')?.focus();
                    }
                    if (e.key === 'Escape') {
                        setNav(false);
                        const q = $('#tw-q');
                        if (q) { q.value = ''; q.dispatchEvent(new Event('input')); }
                    }
                });

                /* Date + time */
                const dateEl = $('#tw-date');
                if (dateEl) {
                    const locale = root.dataset.lang || 'en';
                    const tick = () => {
                        const d = new Date();
                        dateEl.textContent = d.toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
                            + ' · ' + d.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
                    };
                    tick();
                    setInterval(tick, 30000);
                }

                /* Page-load sequence: cards rise in order, numbers count up */
                $$('.kpis>.card,.grid>.card').forEach((card, i) => card.style.setProperty('--i', i));
                const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;
                if (! reduce) {
                    $$('[data-countup]').forEach((el) => {
                        const m = el.textContent.match(/^(\D*)([\d,]+(?:\.\d+)?)(.*)$/);
                        if (! m) return;
                        const end = parseFloat(m[2].replace(/,/g, ''));
                        const dec = (m[2].split('.')[1] || '').length;
                        const comma = m[2].includes(',');
                        const t0 = performance.now() + 300;
                        const frame = (now) => {
                            const p = Math.min(Math.max((now - t0) / 1100, 0), 1);
                            const v = end * (1 - Math.pow(1 - p, 3));
                            const x = comma ? v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) : v.toFixed(dec);
                            el.textContent = m[1] + x + m[3];
                            if (p < 1) requestAnimationFrame(frame);
                        };
                        el.textContent = m[1] + '0' + m[3];
                        requestAnimationFrame(frame);
                    });
                }

                /* Tooltips for charts (mouse and keyboard) */
                const tip = $('#tw-tip');
                const showTip = (e) => {
                    const target = e.target.closest && e.target.closest('[data-tip]');
                    if (! target || ! root.contains(target)) { tip?.classList.remove('on'); return; }
                    tip.textContent = target.dataset.tip;
                    tip.classList.add('on');
                    let x = e.clientX, y = e.clientY;
                    if (e.type === 'focusin') {
                        const r = target.getBoundingClientRect();
                        x = r.left + r.width / 2;
                        y = r.top;
                    }
                    tip.style.left = Math.max(8, Math.min(x - tip.offsetWidth / 2, innerWidth - tip.offsetWidth - 8)) + 'px';
                    tip.style.top = (y - tip.offsetHeight - 14) + 'px';
                };
                ['mousemove', 'focusin'].forEach((type) => document.addEventListener(type, showTip));
                document.addEventListener('focusout', () => tip?.classList.remove('on'));

                /* Fees: Total / Monthly switch between the two server charts */
                const seg = $('#tw-fseg');
                if (seg) {
                    seg.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => {
                        seg.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
                        const mode = button.dataset.m;
                        ['tw-fees-c', 'tw-fees-m'].forEach((id) => {
                            const el = document.getElementById(id);
                            if (el) el.hidden = el.dataset.mode !== mode;
                        });
                    }));
                }

                /* Toast with optional undo */
                const toastEl = $('#tw-toast');
                let toastTimer;
                const toast = (message, undo) => {
                    toastEl.innerHTML = '<span></span>' + (undo ? '<button type="button"></button>' : '');
                    toastEl.querySelector('span').textContent = message;
                    if (undo) {
                        const button = toastEl.querySelector('button');
                        button.textContent = @js(__('Undo'));
                        button.onclick = () => { undo(); toastEl.classList.remove('on'); };
                    }
                    toastEl.classList.add('on');
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => toastEl.classList.remove('on'), 4500);
                };

                /* Dismiss attention items (until the next full refresh) */
                const todoList = $('#tw-todo');
                if (todoList) {
                    const hidden = new Set();
                    const refresh = () => {
                        todoList.querySelectorAll('li').forEach((li) => {
                            li.style.display = hidden.has(li.dataset.i) ? 'none' : '';
                        });
                        const left = [...todoList.children].filter((li) => li.style.display !== 'none').length;
                        if (left === 0) {
                            const empty = document.createElement('div');
                            empty.className = 'empty';
                            empty.textContent = @js(__('You’re all caught up.'));
                            todoList.after(empty);
                        }
                    };
                    todoList.addEventListener('click', (e) => {
                        const x = e.target.closest('.x');
                        if (! x) return;
                        const li = x.closest('li');
                        li.classList.add('out');
                        setTimeout(() => {
                            hidden.add(li.dataset.i);
                            li.style.display = 'none';
                            refresh();
                        }, reduce ? 0 : 300);
                        toast(@js(__('Hidden until the next refresh')), () => {
                            hidden.delete(li.dataset.i);
                            li.style.display = '';
                            refresh();
                        });
                    });
                }

                /* Quick find (Ctrl/Cmd + K) over the real navigation */
                let items = [];
                try {
                    items = JSON.parse(document.getElementById('tw-nav-json')?.textContent || '[]');
                } catch (e) { items = []; }
                const cp = $('#tw-cp'), ci = $('#tw-cpi'), cl = $('#tw-cpl');
                let sel = 0, current = [];
                const renderList = () => {
                    const value = ci.value.trim().toLowerCase();
                    current = items.filter((item) => ! value || (item.t + ' ' + item.g).toLowerCase().includes(value)).slice(0, 8);
                    sel = Math.min(sel, Math.max(current.length - 1, 0));
                    cl.innerHTML = current.length
                        ? current.map((item, i) => '<li class="' + (i === sel ? 'sel' : '') + '" data-i="' + i + '"><span>' + item.t.replace(/</g, '&lt;') + '</span><small>' + item.g.replace(/</g, '&lt;') + '</small></li>').join('')
                        : '<li><small>' + @js(__('No pages match. Try a shorter word.')) + '</small></li>';
                };
                const openCp = () => { cp.hidden = false; ci.value = ''; sel = 0; renderList(); ci.focus(); };
                const closeCp = () => { cp.hidden = true; };
                $('#tw-cpb')?.addEventListener('click', openCp);
                ci.addEventListener('input', () => { sel = 0; renderList(); });
                ci.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, current.length - 1); renderList(); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); renderList(); }
                    else if (e.key === 'Enter' && current[sel]) { window.location.href = current[sel].h; }
                });
                cl.addEventListener('click', (e) => {
                    const li = e.target.closest('li[data-i]');
                    if (li && current[+li.dataset.i]) window.location.href = current[+li.dataset.i].h;
                });
                cp.addEventListener('click', (e) => { if (e.target === cp) closeCp(); });
                document.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        cp.hidden ? openCp() : closeCp();
                    }
                });
            })();
        </script>
    @endscript
</x-filament-panels::page>
