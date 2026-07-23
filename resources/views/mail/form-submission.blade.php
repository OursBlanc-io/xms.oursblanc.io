<h2>New submission: {{ $form->name }}</h2>

<table>
    @foreach ($data as $key => $value)
        <tr>
            <td><strong>{{ $key }}</strong></td>
            <td>{{ is_array($value) ? implode(', ', $value) : $value }}</td>
        </tr>
    @endforeach
</table>
