<table>
    <tr>
        <td></td>
        <td>API LIST</td>
    </tr>
</table>
<br />
<br />
<table>
    <thead>
    <tr>
        <th></th>
        <th><strong>NO</strong></th>
        <th><strong>URL</strong></th>
        <th><strong>API Path</strong></th>
        <th><strong>METHOD</strong></th>
        <th><strong>Updated At</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($apis as $index => $api)
        @php
            $url = data_get($api, 'request.url.host')[0] ?? '';
			$uri = is_array(data_get($api, 'request.url.path')) ? formatPath(implode('/', data_get($api, 'request.url.path'))) : data_get($api, 'request.url.path');
			if(!$uri && Str::contains($url, 'graphql')) {
                $uri = data_get($api,'request.body.graphql.query');
                preg_match('/^(.*?)\{/', $uri, $matches);
				$uri = count($matches) ? rtrim($matches[1]) : '';
				$uri = 'GraphQL: '. $uri;
			}
        @endphp
        <tr>
            <td></td>
            <td>=HYPERLINK("#{{ formatSheetName(data_get($api, 'name')) }}!A1", ROW() - {{ $start_idx }})</td>
            <td>{{ $url }}</td>
            <td>{{ $uri }}</td>
            <td>{{ data_get($api, 'request.method') }}</td>
            <td>{{ dateNow('Y年m月d日') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
