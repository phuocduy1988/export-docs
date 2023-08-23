<table>
    <tbody>
    <tr>
       <td><strong>テーブル名 (論理名):</strong></td>
       <td><strong>{{$title}}</strong></td>
    </tr>
    <tr>
        <td><strong>テーブル名 (物理名):</strong></td>
        <td><strong>{{$title}}</strong></td>
    </tr>
    <tr>
        <td><strong>説明:</strong></td>
        <td><strong></strong></td>
    </tr>
    <tr>
        <td><strong>制約:</strong></td>
        <td><strong></strong></td>
    </tr>
    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th><strong>カラム名 (論理名)</strong></th>
        <th><strong>カラム名 (物理名)</strong></th>
        <th><strong>型</strong></th>
        <th><strong>PK</strong></th>
        <th><strong>NOT NULL</strong></th>
        <th><strong>UNIQUE</strong></th>
        <th><strong>FK</strong></th>
        <th><strong>オートインクリメント</strong></th>
        <th><strong>デフォルト値</strong></th>
        <th><strong>説明</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($databases as $database)
        <tr>
            <td>{{ Str::ucfirst(Str::replace('_', ' ', $database['field'])) }}</td>
            <td>{{$database['field']}}</td>
            <td>{{$database['type']}}</td>
            <td>{{$database['key'] === 'PRI' ? '○' : ''}}</td>
            <td>{{$database['null'] === 'NO' ? '○' : ''}}</td>
            <td>{{in_array($database['key'], ['PRI', 'UNI']) ? '○' : ''}}</td>
            <td>{{Str::contains($database['field'], '_id') ? 'id|' . Str::plural(Str::replace('_id', '', $database['field']))  : ''}}</td>
            <td>{{$database['extra']}}</td>
            <td>{{$database['default']}}</td>
            <td>{{$database['comment']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>
<br>
<br>
<br>

<h5>インデックス</h5>

<br>
<br>
<br>
<br>
<br>
<br>

<h5>複合一意キー</h5>

<br>
<br>
<br>
<br>
<br>
<br>

<h5>外部キー</h5>

<table>
    <thead>
        <tr>
            <th>
                <strong>
                    カラム名 (論理名)
                </strong>
            </th>
            <th>
                <strong>
                    カラム名 (物理名)
                </strong>
            </th>
            <th>
                <strong>
                    参照テーブル
                </strong>
            </th>
            <th>
                <strong>
                    参照キー
                </strong>
            </th>
        </tr>
    </thead>
</table>
