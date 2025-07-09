

<a href="{{ route('lecturers.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('lecturers*') ? 'active' : '' }}">

    <p>Lecturers</p>
</a>

<a href="{{ route('classses.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('classses*') ? 'active' : '' }}">

    <p>Classes</p>
</a>

<a href="{{ route('lecture-administereds.index') }}" class="nav-link text-white font-weight-bold {{ Request::is('lecture-administereds*') ? 'active' : '' }}">

    <p>Lecture Administereds</p>
</a>

<a href="{{ route('logout') }}" class="nav-link text-white font-weight-bold"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

    <p>Logout</p>
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
