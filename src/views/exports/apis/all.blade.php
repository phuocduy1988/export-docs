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
                <td>=HYPERLINK("[{{ $filename }}]'{{ formatSheetName($api['name']) }}'!A1", ROW() - {{ $start_idx }})</td>
                <td>DEV_URL</td>
                <td>{{ formatPath($api['path']) }}</td>
                <td>{{ $api['method'] }}</td>
                <td></td>
                <td>{{ $api['updated_at'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
