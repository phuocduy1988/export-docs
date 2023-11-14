<table>
    <tr>
        <td></td>
        <td>ID</td>
        <td colspan="3">=CONCATENATE(REPT("0", 3 - LEN(INDEX('{{ $refName }}'!B:B, {{$refIdx}}))), INDEX('{{ $refName }}'!B:B, {{$refIdx}}))</td>
    </tr>
    <tr>
        <td></td>
        <td>Name</td>
        <td colspan="3">{{ $name }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Path</td>
        <td colspan="3">{{ $path }}</td>
    </tr>
    <tr>
        <td></td>
        <td>ScreenID</td>
        <td colspan="3">=@INDEX('{{ $refName }}'!F:F, {{ $refIdx }})</td>
    </tr>
</table>
<br />
<table>
    <tr>
        <td></td>
        <td colspan="4">URI</td>
    </tr>
    <tr>
        <td></td>
        <td>{{ $method }}</td>
        <td colspan="3">{{ $path }}</td>
    </tr>
</table>
<table>
    <tr>
        <td></td>
        <td colspan="4">HEADER</td>
    </tr>
    <tr>
        <td></td>
        <td>パラメータ名</td>
        <td>TYPE</td>
        <td>概要</td>
        <td>必須項目</td>
    </tr>
    @foreach($headers as $header)
        <tr>
            <td></td>
            <td>{{ $header['key'] }}</td>
            <td>{{ $header['type'] }}</td>
            <td></td>
            <td style="text-align: center">◯</td>
        </tr>
    @endforeach
</table>
<table>
    <tr>
        <td></td>
        <td colspan="4">INPUT</td>
    </tr>
    <tr>
        <td></td>
        <td>パラメータ名</td>
        <td>TYPE</td>
        <td>概要</td>
        <td>必須項目</td>
    </tr>
    @foreach($inputs as $input)
        <tr>
            <td></td>
            <td>{{ $input['key'] }}</td>
            <td>{{ $input['type'] }}</td>
            <td></td>
            <td style="text-align: center">{{ $input['required'] ? '◯' : '' }}</td>
        </tr>
    @endforeach
</table>
<table>
    <tr>
        <td></td>
        <td colspan="4">OUTPUT</td>
    </tr>
    <tr>
        <td></td>
        <td>パラメータ名</td>
        <td>TYPE</td>
        <td colspan="2">概要</td>
    </tr>
    @foreach($outputs as $output)
        <tr>
            <td></td>
            <td>{{ data_get($output, 'key') }}</td>
            <td>{{ data_get($output, 'type') }}</td>
            <td colspan="2"></td>
        </tr>
    @endforeach
</table>
<table>
    <tr>
        <td></td>
        <td colspan="4">RESPONSE</td>
    </tr>
    @if($successResponses)
        <tr>
            <td></td>
            <td>{{ data_get($successResponses, 'text') == 'O_K' ? 'SUCCESS' : data_get($successResponses, 'text') }}</td>
            <td>{{ data_get($successResponses, 'code') }}</td>
            <td colspan="2">{{ json_encode(data_get($successResponses, 'data'), JSON_UNESCAPED_UNICODE)  }}</td>
        </tr>
    @endif
    @if($errorResponses)
        <tr>
            <td></td>
            <td>{{ \Illuminate\Support\Str::upper(data_get($errorResponses, 'text')) }}</td>
            <td>{{ data_get($errorResponses, 'code') }}</td>
            <td colspan="2">{{ json_encode(data_get($errorResponses, 'data'), JSON_UNESCAPED_UNICODE)  }}</td>
        </tr>
    @endif
</table>
