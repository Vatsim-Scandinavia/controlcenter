@if(config('services.moodle.enabled') && $training->status === \App\Helpers\TrainingStatus::PRE_TRAINING)
    @can('update', $training)
        <div class="card shadow mb-4" id="moodle-enrolment-card">
            <div class="card-header bg-primary py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Moodle enrolment</h6>
                <form action="{{ route('training.moodle.retry', $training) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-light" type="submit"><i class="fas fa-rotate"></i>&nbsp;Retry</button>
                </form>
            </div>
            <div class="card-body">
                @if($moodleUserLink)
                    <p>
                        <strong>Moodle user:</strong>
                        {{ $moodleUserLink->moodle_full_name }}
                        @if($moodleUserLink->moodle_username)
                            ({{ $moodleUserLink->moodle_username }})
                        @endif
                        <span class="badge bg-secondary">{{ $moodleUserLink->match_type }}</span>
                    </p>
                @else
                    <div class="alert alert-warning">No Moodle user is linked to CID {{ $training->user_id }}.</div>
                @endif

                @if($moodleEnrolments->isEmpty())
                    <p class="text-muted">No Moodle courses are currently assigned to this training.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($moodleEnrolments as $enrolment)
                                    <tr>
                                        <td>
                                            {{ $enrolment->course->full_name }}
                                            @if($enrolment->last_error)
                                                <div class="small text-danger">{{ $enrolment->last_error }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColor = match($enrolment->status) {
                                                    'enrolled' => 'success',
                                                    'failed' => 'danger',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }}">{{ ucfirst($enrolment->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <hr>
                <h6>Assign courses manually</h6>
                @if($moodleCourses->isEmpty())
                    <p class="text-muted">Refresh the course catalogue under Moodle integration before assigning courses.</p>
                @else
                    <form action="{{ route('training.moodle.courses.assign', $training) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="moodle-course-ids">Moodle courses</label>
                            <select class="form-select" id="moodle-course-ids" name="course_ids[]" multiple required size="6">
                                @foreach($moodleCourses as $course)
                                    <option value="{{ $course->id }}" @selected($moodleEnrolments->contains('moodle_course_id', $course->id))>
                                        {{ $course->full_name }} ({{ $course->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl/Cmd to select multiple courses. Existing enrolments are left unchanged.</div>
                        </div>
                        <button class="btn btn-primary" type="submit">Assign courses and enrol</button>
                    </form>
                @endif

                @if(! $moodleUserLink || $moodleEnrolments->contains('status', 'failed'))
                    <hr>
                    <h6>Find another Moodle user</h6>
                    <p class="small text-muted">Search Moodle by CID, username, name, or email, then link the selected account to this Control Center student.</p>

                    <div class="input-group mb-3">
                        <input class="form-control" id="moodle-user-query" type="search" minlength="2" maxlength="100" placeholder="Search Moodle users">
                        <button class="btn btn-outline-primary" id="moodle-user-search" type="button">Search</button>
                    </div>
                    <div id="moodle-user-search-error" class="alert alert-danger d-none"></div>
                    <div class="list-group mb-3" id="moodle-user-results"></div>

                    <form action="{{ route('training.moodle.link', $training) }}" method="POST" id="moodle-user-link-form" class="d-none">
                        @csrf
                        <input type="hidden" name="moodle_user_id" id="moodle-user-id">
                        <p>Selected: <strong id="moodle-selected-user"></strong></p>
                        <button class="btn btn-primary" type="submit">Link user and retry enrolment</button>
                    </form>
                @endif
            </div>
        </div>

        @if(! $moodleUserLink || $moodleEnrolments->contains('status', 'failed'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const searchButton = document.getElementById('moodle-user-search');
                    const queryInput = document.getElementById('moodle-user-query');
                    const results = document.getElementById('moodle-user-results');
                    const error = document.getElementById('moodle-user-search-error');
                    const linkForm = document.getElementById('moodle-user-link-form');

                    searchButton.addEventListener('click', async function () {
                        const query = queryInput.value.trim();
                        if (query.length < 2) {
                            return;
                        }

                        searchButton.disabled = true;
                        results.replaceChildren();
                        error.classList.add('d-none');

                        try {
                            const url = new URL(@json(route('training.moodle.users', $training)), window.location.origin);
                            url.searchParams.set('query', query);
                            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
                            const data = await response.json();

                            if (! response.ok) {
                                throw new Error(data.message || 'Moodle user search failed.');
                            }

                            if (data.users.length === 0) {
                                results.textContent = 'No Moodle users found.';
                            }

                            data.users.forEach(function (user) {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'list-group-item list-group-item-action';
                                button.textContent = `${user.fullname}${user.username ? ` (${user.username})` : ''}${user.email ? ` — ${user.email}` : ''}`;
                                button.addEventListener('click', function () {
                                    document.getElementById('moodle-user-id').value = user.id;
                                    document.getElementById('moodle-selected-user').textContent = button.textContent;
                                    linkForm.classList.remove('d-none');
                                });
                                results.appendChild(button);
                            });
                        } catch (searchError) {
                            error.textContent = searchError.message;
                            error.classList.remove('d-none');
                        } finally {
                            searchButton.disabled = false;
                        }
                    });
                });
            </script>
        @endif
    @endcan
@endif
