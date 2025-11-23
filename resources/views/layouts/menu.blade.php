
@can('lecturer.manage')
<a href="{{ route('lecturers.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('lecturers*') ? 'active' : '' }}">
    <p>Lecturers</p>
</a>
@endcan

    <a href="{{ route('units.index') }}" class="nav-link text-white font-weight-bold  {{ Request::is('units*') ? 'active' : '' }}">
        <p>Units</p>
    </a>

@can('class.manage')
<a href="{{ route('classses.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('classses*') ? 'active' : '' }}">
    <p>Classes</p>
</a>
@endcan

@can('lecture.manage')
<a href="{{ route('lecture-administereds.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('lecture-administereds*') ? 'active' : '' }}">
    <p>Lecture Administereds</p>
</a>
@endcan

@can('user.manage')
<a href="{{ route('users.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('users*') ? 'active' : '' }}">
    <p>Users</p>
</a>
@endcan

@can('role.manage')
<a href="{{ route('roles.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('roles*') ? 'active' : '' }}">
    <p>User Roles</p>
</a>
@endcan

<a href="{{ route('logout') }}" class="nav-link text-white font-weight-bold"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

    <p>Logout</p>
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

