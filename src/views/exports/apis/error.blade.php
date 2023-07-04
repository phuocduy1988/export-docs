<table>
    <thead>
        <tr>
            <th>File</th>
            <th>Sheet</th>
            <th>Status</th>
            <th>Type</th>
            <th>Response</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $api)
            <tr>
                <td>{{ $refFile }}</td>
                <td>=HYPERLINK("[../{{ $refFile }}]'{{ formatSheetName($api['name']) }}'!A1", "{{ formatSheetName($api['name']) }}")</td>
                <td>{{ $api['code'] ?? '' }}</td>
                <td>{{ $api['text'] ?? '' }}</td>
                <td colspan="2">{{ json_encode($api['data'], JSON_UNESCAPED_UNICODE)  }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
