<table>
    <thead>
    <tr>
        <th>
            <strong>METHOD</strong>
        </th>
        <th>
            <strong>API</strong>
        </th>
        <th>
            <strong>CONTROLLER</strong>
        </th>
        <th>
            <strong>HEADER</strong>
        </th>
        <th>
            <strong>REQUEST</strong>
        </th>
        <th>
            <strong>RESPONSE</strong>
        </th>
        <th>
            <strong>Modified</strong>
        </th>
        <th>
            <strong>Modified By</strong>
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($apis as $api)
        <tr>
            <td>
                {{ strtoupper($api['method']) }}
            </td>
            <td>
                <p>{{ $api['url'] }}</p>
                @if($api['content-type'])
                    <p>( {{$api['content-type']}} )</p>
                @endif
            </td>
            <td>
                {{ $api['operationId'] }}
            </td>
            <td>
                @foreach($api['headers'] as $header)
                    <p>{{ $header['name'] }}</p>
                @endforeach
            </td>
            <td>
                @foreach($api['params'] as $param)
                    <p>{{ $param['name'] }}</p>
                @endforeach
            </td>
            <td>
                {!! $api['response'] !!}
            </td>
            <td></td>
            <td></td>
        </tr>
    @endforeach
    </tbody>
</table>
