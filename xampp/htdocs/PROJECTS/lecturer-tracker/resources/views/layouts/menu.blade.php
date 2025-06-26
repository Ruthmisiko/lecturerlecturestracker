

    <a href="{{ route('lecturers.index') }}" class="nav-link {{ Request::is('lecturers*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-home"></i>
        <p>Lecturers</p>
    </a>
        <a href="{{ route('classses.index') }}" class="nav-link {{ Request::is('classses*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-home"></i>
            <p>Classses</p>
        </a>
        <a href="{{ route('lecture-administereds.index') }}" class="nav-link {{ Request::is('lecture-administereds*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-home"></i>
            <p>Lecture Administereds</p>
        </a>
<a href="{{ route('logout') }}" class="nav-link"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
    <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
    <p>Logout</p>
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
