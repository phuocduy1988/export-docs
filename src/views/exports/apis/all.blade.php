@foreach($apis as $name => $api)
    <table>
        <thead>
        <tr>
            <th>
                <strong>{{ $name }}</strong>
            </th>
        </tr>
        </thead>
        <tbody>
        @foreach($api as $value)
            <tr>
                <td>{{ $value['url'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach
