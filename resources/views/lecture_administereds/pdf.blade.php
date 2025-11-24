<h2>Lecture Administereds</h2>

<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Lecturer</th>
            <th>Class</th>
            <th>Date</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
        <tr>
            <td>{{ $item->lecturer->name }}</td>
            <td>{{ $item->classs->name }}</td>
            <td>{{ $item->lecture_date }}</td>
            <td>{{ $item->start_time }}</td>
            <td>{{ $item->end_time }}</td>
            <td>{{ $item->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
