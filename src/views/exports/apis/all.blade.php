<table>
    <tr>
        <td></td>
        <td>URL: </td>
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
        <th><strong>Screen ID</strong></th>
        <th><strong>Updated At</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($apis as $index => $api)
        <tr>
            <td></td>
            <td>=HYPERLINK("[{{ $filename }}]'{{ formatSheetName(data_get($api, 'name')) }}'!A1", ROW() - {{ $start_idx }})</td>
            <td>DEV_URL</td>
            <td>/{{ is_array(data_get($api, 'request.url.path')) ? formatPath(implode('/', data_get($api, 'request.url.path'))) : data_get($api, 'request.url.path') }}</td>
            <td>{{ data_get($api, 'request.method') }}</td>
            <td></td>
            <td>{{ dateNow('Y年m月d日') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
