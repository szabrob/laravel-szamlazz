@php
    $value = function (array $field, string $name) use ($submitted): mixed {
        if ($field['type'] === 'checkbox') {
            return $submitted ? request()->boolean($name) : (bool) $field['default'];
        }

        return $submitted ? request()->input($name) : $field['default'];
    };
@endphp
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Számla Agent – {{ $form['title'] }}</title>
    <style>
        :root {
            --bg: #f5f6f8; --panel: #fff; --text: #1d2330; --muted: #677085; --border: #dfe2e8;
            --accent: #2457d6; --accent-soft: #e8eefc; --ok: #13804b; --ok-soft: #e5f5ec;
            --err: #c0322b; --err-soft: #fcebea; --code: #f2f4f7;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #12151b; --panel: #1a1e26; --text: #e5e8ee; --muted: #8e97a8; --border: #2b313d;
                --accent: #6d95ff; --accent-soft: #1f2a45; --ok: #4cc58a; --ok-soft: #15301f;
                --err: #ff7b72; --err-soft: #3a1b1a; --code: #11141a;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 14px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif; }
        a { color: var(--accent); }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; }
        .layout { display: grid; grid-template-columns: 240px minmax(0, 1fr); min-height: 100vh; }
        nav { background: var(--panel); border-right: 1px solid var(--border); padding: 20px 14px; }
        nav h1 { font-size: 16px; margin: 0 6px 18px; }
        nav h1 small { display: block; font-weight: 400; color: var(--muted); font-size: 12px; }
        nav h2 { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 18px 6px 6px; }
        nav a { display: block; padding: 6px 10px; border-radius: 6px; text-decoration: none; color: var(--text); }
        nav a:hover { background: var(--bg); }
        nav a.active { background: var(--accent-soft); color: var(--accent); font-weight: 600; }
        main { padding: 24px 28px 60px; max-width: 1200px; }
        header.page { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 18px; }
        header.page h2 { margin: 0 0 4px; font-size: 22px; }
        header.page p { margin: 0; color: var(--muted); }
        .creds { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; font-size: 12.5px; min-width: 320px; }
        .creds form { display: flex; gap: 6px; margin-top: 6px; }
        .creds .status { color: var(--muted); }
        .creds .status b { color: var(--text); }
        .creds .warn { color: var(--err); font-weight: 600; }
        section.card { background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 16px 18px; margin-bottom: 14px; }
        section.card > h3 { margin: 0 0 12px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px 14px; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field span { font-size: 12px; color: var(--muted); }
        .field .req { color: var(--err); margin-left: 2px; }
        .wide { grid-column: 1 / -1; }
        .check { display: flex; gap: 8px; align-items: center; align-self: end; padding-bottom: 6px; }
        input:not([type=checkbox]), select, textarea {
            width: 100%; padding: 7px 9px; border: 1px solid var(--border); border-radius: 6px;
            background: var(--bg); color: var(--text); font: inherit;
        }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: -1px; }
        .row { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) 32px; gap: 8px; align-items: end; padding: 10px 0; border-top: 1px dashed var(--border); }
        .row:first-of-type { border-top: 0; padding-top: 0; }
        button { font: inherit; cursor: pointer; border-radius: 6px; border: 1px solid var(--border); background: var(--panel); color: var(--text); padding: 7px 12px; }
        button.primary { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; padding: 9px 18px; }
        button.small { padding: 4px 10px; font-size: 12px; }
        button.remove { padding: 6px 0; color: var(--muted); }
        .actions { display: flex; gap: 12px; align-items: center; }
        .actions code { color: var(--muted); }
        .result { border-radius: 10px; padding: 14px 18px; margin: 22px 0 14px; border: 1px solid; }
        .result.ok { background: var(--ok-soft); border-color: var(--ok); }
        .result.err { background: var(--err-soft); border-color: var(--err); }
        .result strong { display: block; font-size: 15px; }
        .result .ok-text { color: var(--ok); }
        .result .err-text { color: var(--err); }
        .result .meta { color: var(--muted); font-size: 12px; margin-top: 4px; }
        pre { background: var(--code); border: 1px solid var(--border); border-radius: 8px; padding: 12px; overflow: auto; max-height: 460px; margin: 0; white-space: pre-wrap; word-break: break-word; }
        details { margin-top: 10px; }
        summary { cursor: pointer; font-weight: 600; margin-bottom: 8px; }
        table.headers { border-collapse: collapse; margin-bottom: 10px; font-size: 12.5px; }
        table.headers td { padding: 3px 12px 3px 0; vertical-align: top; }
        table.headers td:first-child { color: var(--muted); font-family: ui-monospace, Menlo, monospace; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            nav { border-right: 0; border-bottom: 1px solid var(--border); }
            main { padding: 18px 16px 40px; }
            .two { grid-template-columns: 1fr; }
            .creds { min-width: 0; width: 100%; }
        }
    </style>
</head>
<body>
<div class="layout">
    <nav>
        <h1>Számla Agent<small>szabrob/laravel-szamlazz workbench</small></h1>
        @foreach (collect($forms)->groupBy('group', preserveKeys: true) as $group => $items)
            <h2>{{ $group }}</h2>
            @foreach ($items as $key => $item)
                <a href="{{ route('szamlazz.console', $key) }}" @class(['active' => $key === $operation])>{{ $item['title'] }}</a>
            @endforeach
        @endforeach
    </nav>

    <main>
        <header class="page">
            <div>
                <h2>{{ $form['title'] }}</h2>
                <p>{{ $form['description'] }} <code>SzamlaAgent::{{ $form['method'] }}</code></p>
            </div>
            <div class="creds">
                <div class="status">
                    @if ($credentials)
                        Hitelesítés: <b>{{ $credentials }}</b>
                    @else
                        <span class="warn">Nincs hitelesítési adat.</span> Állítsd be a <code>SZAMLAZZ_API_KEY</code>-t a <code>workbench/.env</code>-ben, vagy add meg itt:
                    @endif
                    <br>Végpont: <code>{{ $baseUrl }}</code>
                </div>
                <form method="post" action="{{ route('szamlazz.credentials') }}">
                    @csrf
                    <input type="password" name="api_key" placeholder="Agent kulcs (csak ebben a munkamenetben)" autocomplete="off">
                    <button type="submit" class="small">Mentés</button>
                    @if ($hasSessionKey)
                        <button type="submit" class="small" name="clear" value="1">Törlés</button>
                    @endif
                </form>
            </div>
        </header>

        @if ($outcome)
            <div @class(['result', 'ok' => $outcome['ok'], 'err' => ! $outcome['ok']])>
                @if ($outcome['ok'])
                    <strong class="ok-text">✓ {{ $outcome['message'] ?? 'Sikeres hívás.' }}</strong>
                @else
                    <strong class="err-text">✕ {{ $outcome['error'] }}</strong>
                    @foreach ($outcome['details'] as $label => $detail)
                        <div class="meta">{{ $label }}: {{ $detail }}</div>
                    @endforeach
                @endif
                <div class="meta">
                    {{ $outcome['duration'] }} ms
                    @if (! empty($outcome['pdf']))
                        · <a href="{{ $outcome['pdf'] }}" target="_blank" rel="noopener">PDF megnyitása</a>
                    @endif
                </div>
            </div>

            @if (! empty($outcome['data']))
                <section class="card">
                    <h3>Eredmény DTO <code>toArray()</code></h3>
                    <pre>{{ $outcome['data'] }}</pre>
                    @if (! empty($outcome['xml']))
                        <details>
                            <summary>Teljes számla XML (<code>$xml</code>)</summary>
                            <pre>{{ $outcome['xml'] }}</pre>
                        </details>
                    @endif
                </section>
            @endif

            @foreach ($outcome['exchanges'] as $exchange)
                <section class="card">
                    <h3>HTTP forgalom <code>{{ $exchange['status'] }} · {{ $exchange['url'] }}</code></h3>
                    <div class="two">
                        <div>
                            <details open>
                                <summary>Kérés XML</summary>
                                <pre>{{ $exchange['request'] }}</pre>
                            </details>
                        </div>
                        <div>
                            <details open>
                                <summary>Válasz</summary>
                                @if ($exchange['headers'])
                                    <table class="headers">
                                        @foreach ($exchange['headers'] as $header => $headerValue)
                                            <tr><td>{{ $header }}</td><td>{{ $headerValue }}</td></tr>
                                        @endforeach
                                    </table>
                                @endif
                                <pre>{{ $exchange['response'] !== '' ? $exchange['response'] : '(üres válasz)' }}</pre>
                            </details>
                        </div>
                    </div>
                </section>
            @endforeach
        @endif

        <form method="post" action="{{ route('szamlazz.run', $operation) }}">
            @csrf

            @foreach ($form['sections'] as $title => $fields)
                <section class="card">
                    <h3>{{ $title }}</h3>
                    <div class="grid">
                        @foreach ($fields as $field)
                            @include('szamlazz-console.field', ['field' => $field, 'name' => $field['name'], 'value' => $value($field, $field['name'])])
                        @endforeach
                    </div>
                </section>
            @endforeach

            @foreach ($form['repeaters'] ?? [] as $repeaterName => $repeater)
                @php
                    $submittedRows = $submitted ? array_values(array_filter((array) request()->input($repeaterName, []), 'is_array')) : [];
                    $rowCount = $submitted ? count($submittedRows) : $repeater['rows'];
                @endphp
                <section class="card repeater" data-max="{{ $repeater['max'] ?? '' }}">
                    <h3>
                        {{ $repeater['title'] }}
                        <button type="button" class="small" data-add>+ Sor</button>
                    </h3>
                    <div data-rows>
                        @for ($i = 0; $i < $rowCount; $i++)
                            <div class="row">
                                @foreach ($repeater['fields'] as $field)
                                    @php
                                        $name = "{$repeaterName}.{$i}.{$field['name']}";
                                        $rowValue = $submitted ? ($submittedRows[$i][$field['name']] ?? null) : $field['default'];
                                    @endphp
                                    @include('szamlazz-console.field', ['field' => $field, 'name' => $name, 'value' => $rowValue])
                                @endforeach
                                <button type="button" class="remove" data-remove title="Sor törlése">✕</button>
                            </div>
                        @endfor
                    </div>
                    <template>
                        <div class="row">
                            @foreach ($repeater['fields'] as $field)
                                @include('szamlazz-console.field', ['field' => $field, 'name' => "{$repeaterName}.__i__.{$field['name']}", 'value' => $field['default']])
                            @endforeach
                            <button type="button" class="remove" data-remove title="Sor törlése">✕</button>
                        </div>
                    </template>
                </section>
            @endforeach

            <div class="actions">
                <button type="submit" class="primary">Küldés a Számla Agentnek</button>
                <code>POST {{ $baseUrl }}</code>
            </div>
        </form>
    </main>
</div>

<script>
    document.querySelectorAll('.repeater').forEach((repeater) => {
        const rows = repeater.querySelector('[data-rows]');
        const template = repeater.querySelector('template');
        const max = parseInt(repeater.dataset.max || '0', 10);
        let next = rows.children.length;

        repeater.querySelector('[data-add]').addEventListener('click', () => {
            if (max && rows.children.length >= max) {
                return;
            }
            const html = template.innerHTML.replaceAll('__i__', String(next++));
            rows.insertAdjacentHTML('beforeend', html);
        });

        rows.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove]')) {
                event.target.closest('.row').remove();
            }
        });
    });
</script>
</body>
</html>
