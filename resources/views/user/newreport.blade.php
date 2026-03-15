@extends('layouts.app')

@section('title', 'New Report - Sincidentre')

@section('content')

<div class="page-container">

    @php
        $informantRoleMap = [
            'student' => 'Student',
            'faculty' => 'Faculty',
            'employee_staff' => 'Employee/Staff',
        ];
        $accountRole = $informantRoleMap[Auth::user()->registrant_type ?? ''] ?? 'Student';
        $oldAdditionalPersons = old('additional_persons', []);
        $oldWitnessDetails = old('witness_details', []);
    @endphp

    <!-- Page Header -->
    <header class="page-header">
        <h1>LLCC Incident Report Form</h1>
        <p>Please complete all required fields before submitting.</p>
    </header>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Report Form -->
    <section class="form-wrapper animate">
        <form id="reportForm"
              action="{{ route('reports.store') }}"
              method="POST"
              enctype="multipart/form-data">

            @csrf

            <h2>Section 1: Information About the Person/s Involved in the Incident</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="person_full_name">Full Name <span>*</span></label>
                    <input type="text"
                           id="person_full_name"
                           name="person_full_name"
                           value="{{ old('person_full_name') }}"
                           placeholder="Name of person involved"
                           required>
                </div>

                <div class="form-group">
                    <label for="person_college_department">College/Department <span>*</span></label>
                    <input type="text"
                           id="person_college_department"
                           name="person_college_department"
                           value="{{ old('person_college_department') }}"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="person_role">Role <span>*</span></label>
                    <select id="person_role" name="person_role" required>
                        <option value="">-- Select Role --</option>
                        <option value="Student" {{ old('person_role') == 'Student' ? 'selected' : '' }}>Student</option>
                        <option value="Faculty" {{ old('person_role') == 'Faculty' ? 'selected' : '' }}>Faculty</option>
                        <option value="Employee/Staff" {{ old('person_role') == 'Employee/Staff' ? 'selected' : '' }}>Employee/Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="person_contact_number">Contact Number</label>
                    <input type="text"
                           id="person_contact_number"
                           name="person_contact_number"
                           value="{{ old('person_contact_number') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="person_email_address">Email Address</label>
                <input type="email"
                       id="person_email_address"
                       name="person_email_address"
                       value="{{ old('person_email_address') }}">
            </div>

            <div class="form-group">
                <label>Are there multiple persons involved in the incident? <span>*</span></label>
                <div>
                    <label for="person_has_multiple_yes">
                        <input type="radio" id="person_has_multiple_yes" name="person_has_multiple" value="1" {{ old('person_has_multiple') === '1' ? 'checked' : '' }} required>
                        Yes
                    </label>
                    <label for="person_has_multiple_no" style="margin-left: 16px;">
                        <input type="radio" id="person_has_multiple_no" name="person_has_multiple" value="0" {{ old('person_has_multiple') === '0' ? 'checked' : '' }} required>
                        No
                    </label>
                </div>
            </div>

            <div id="additionalPersonsSection" style="display: none;">
                <div id="additionalPersonsContainer"></div>
                <button type="button" class="btn btn-secondary" id="addAdditionalPersonBtn">+ Add Another Person</button>
            </div>

            <h2>Section 2: Information About the Incident</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="incident_date">Date of Incident <span>*</span></label>
                    <input type="date"
                           id="incident_date"
                           name="incident_date"
                           value="{{ old('incident_date') }}"
                           required>
                </div>

                <div class="form-group">
                    <label for="incident_time">Time of Incident <span>*</span></label>
                    <input type="time"
                           id="incident_time"
                           name="incident_time"
                           value="{{ old('incident_time') }}"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location of Incident <span>*</span></label>
                <select id="location" name="location" required>
                    <option value="">-- Select Location --</option>
                    <option value="CAS Building" {{ old('location') == 'CAS Building' ? 'selected' : '' }}>CAS Building</option>
                    <option value="CoT Building" {{ old('location') == 'CoT Building' ? 'selected' : '' }}>CoT Building</option>
                    <option value="Admin Building" {{ old('location') == 'Admin Building' ? 'selected' : '' }}>Admin Building</option>
                    <option value="CoEd Building" {{ old('location') == 'CoEd Building' ? 'selected' : '' }}>CoEd Building</option>
                    <option value="CoHTM Building" {{ old('location') == 'CoHTM Building' ? 'selected' : '' }}>CoHTM Building</option>
                    <option value="LLCC MPB" {{ old('location') == 'LLCC MPB' ? 'selected' : '' }}>LLCC MPB</option>
                    <option value="Gate 1" {{ old('location') == 'Gate 1' ? 'selected' : '' }}>Gate 1</option>
                    <option value="Gate 2" {{ old('location') == 'Gate 2' ? 'selected' : '' }}>Gate 2</option>
                </select>
            </div>

            <div class="form-group">
                <label for="location_details">Please Specify</label>
                <input type="text"
                       id="location_details"
                       name="location_details"
                       value="{{ old('location_details') }}"
                       placeholder="Specific room, office, or area details">
            </div>

            <div class="form-group">
                <label for="category_id">Type of Category of Incident <span>*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select Offense or Concern --</option>
                    @foreach($categoriesByMain as $mainCode => $group)
                        <optgroup label="{{ $mainCode }} - {{ $group['main_name'] }}">
                            @foreach($group['items'] as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->classification }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <small class="form-hint">Select the closest offense or issue classification (Minor, Major, or Grave).</small>
            </div>

            <div class="form-group">
                <label for="description">Description of Complaint or Incident <span>*</span></label>
                <textarea id="description"
                          name="description"
                          rows="7"
                          placeholder="Include: what happened, how it happened, and factors leading to the event"
                          required>{{ old('description') }}</textarea>
                <small class="form-hint">If needed, attach additional sheets below.</small>
            </div>

            <div class="form-group">
                <label for="incident_additional_sheets">Additional Sheets (Optional)</label>
                <input type="file"
                       id="incident_additional_sheets"
                       name="incident_additional_sheets[]"
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                       multiple>
                <small class="form-hint">Attach additional pages for incident details if needed.</small>
            </div>

            <div class="form-group">
                <label>Were there any witnesses? <span>*</span></label>
                <div>
                    <label for="has_witnesses_yes">
                        <input type="radio" id="has_witnesses_yes" name="has_witnesses" value="1" {{ old('has_witnesses') === '1' ? 'checked' : '' }} required>
                        Yes
                    </label>
                    <label for="has_witnesses_no" style="margin-left: 16px;">
                        <input type="radio" id="has_witnesses_no" name="has_witnesses" value="0" {{ old('has_witnesses') === '0' ? 'checked' : '' }} required>
                        No
                    </label>
                </div>
            </div>

            <div id="witnessDetailsSection" style="display: none;">
                <div id="witnessDetailsContainer"></div>
                <button type="button" class="btn btn-secondary" id="addWitnessBtn">+ Add Another Witness</button>
            </div>

            <div class="form-group">
                <label for="evidence">Supporting Evidence <span>*</span></label>
                <input type="file"
                       id="evidence"
                       name="evidence[]"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,.mp3,.wav,.m4a,.aac,.ogg,.flac,.mp4,.mov,.avi,.mkv,.wmv,.webm"
                       required
                       multiple>
                <small class="form-hint">Required. Max 50MB per file. You can upload multiple files at once.</small>
            </div>

            <h2>Section 3: Information About the Informant (the person filing the report)</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="informant_full_name">Full Name <span>*</span></label>
                    <input type="text"
                           id="informant_full_name"
                           name="informant_full_name"
                           value="{{ trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')) }}"
                           readonly>
                </div>

                <div class="form-group">
                    <label for="informant_college_department">College/Department <span>*</span></label>
                    <input type="text"
                           id="informant_college_department"
                           name="informant_college_department"
                           value="{{ Auth::user()->department?->name ?? (Auth::user()->employee_office ?? 'N/A') }}"
                           readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="informant_role">Role <span>*</span></label>
                    <input type="text"
                           id="informant_role"
                           name="informant_role"
                           value="{{ $accountRole }}"
                           readonly>
                </div>

                <div class="form-group">
                    <label for="informant_contact_number">Contact Number <span>*</span></label>
                    <input type="text"
                           id="informant_contact_number"
                           name="informant_contact_number"
                           value="{{ old('informant_contact_number', Auth::user()->phone ?? '') }}"
                           placeholder="Enter your contact number"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="informant_email_address">Email Address <span>*</span></label>
                <input type="email"
                       id="informant_email_address"
                       name="informant_email_address"
                      value="{{ Auth::user()->email ?? '' }}"
                      readonly>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Submit Report
                </button>

                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>
    </section>

</div>

<script>
    (function () {
        var multipleYesRadio = document.getElementById('person_has_multiple_yes');
        var multipleNoRadio = document.getElementById('person_has_multiple_no');
        var additionalPersonsSection = document.getElementById('additionalPersonsSection');
        var additionalPersonsContainer = document.getElementById('additionalPersonsContainer');
        var addAdditionalPersonBtn = document.getElementById('addAdditionalPersonBtn');

        var yesRadio = document.getElementById('has_witnesses_yes');
        var noRadio = document.getElementById('has_witnesses_no');
        var witnessDetailsSection = document.getElementById('witnessDetailsSection');
        var witnessDetailsContainer = document.getElementById('witnessDetailsContainer');
        var addWitnessBtn = document.getElementById('addWitnessBtn');

        var oldAdditionalPersons = @json($oldAdditionalPersons);
        var oldWitnessDetails = @json($oldWitnessDetails);

        function buildPersonBlock(index, person) {
            var fullName = person && person.full_name ? person.full_name : '';
            var collegeDepartment = person && person.college_department ? person.college_department : '';
            var role = person && person.role ? person.role : '';
            var contactNumber = person && person.contact_number ? person.contact_number : '';
            var emailAddress = person && person.email_address ? person.email_address : '';

            return '' +
                '<div class="form-card" style="margin-bottom: 14px; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Full Name <span>*</span></label>' +
                '      <input type="text" name="additional_persons[' + index + '][full_name]" value="' + fullName + '" required>' +
                '    </div>' +
                '    <div class="form-group">' +
                '      <label>College/Department <span>*</span></label>' +
                '      <input type="text" name="additional_persons[' + index + '][college_department]" value="' + collegeDepartment + '" required>' +
                '    </div>' +
                '  </div>' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Role <span>*</span></label>' +
                '      <select name="additional_persons[' + index + '][role]" required>' +
                '        <option value="">-- Select Role --</option>' +
                '        <option value="Student" ' + (role === 'Student' ? 'selected' : '') + '>Student</option>' +
                '        <option value="Faculty" ' + (role === 'Faculty' ? 'selected' : '') + '>Faculty</option>' +
                '        <option value="Employee/Staff" ' + (role === 'Employee/Staff' ? 'selected' : '') + '>Employee/Staff</option>' +
                '      </select>' +
                '    </div>' +
                '    <div class="form-group">' +
                '      <label>Contact Number</label>' +
                '      <input type="text" name="additional_persons[' + index + '][contact_number]" value="' + contactNumber + '">' +
                '    </div>' +
                '  </div>' +
                '  <div class="form-group">' +
                '    <label>Email Address</label>' +
                '    <input type="email" name="additional_persons[' + index + '][email_address]" value="' + emailAddress + '">' +
                '  </div>' +
                '  <button type="button" class="btn btn-secondary remove-person-btn">Remove</button>' +
                '</div>';
        }

        function buildWitnessBlock(index, witness) {
            var name = witness && witness.name ? witness.name : '';
            var address = witness && witness.address ? witness.address : '';
            var contactNumber = witness && witness.contact_number ? witness.contact_number : '';

            return '' +
                '<div class="form-card" style="margin-bottom: 14px; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Witness Name <span>*</span></label>' +
                '      <input type="text" name="witness_details[' + index + '][name]" value="' + name + '" required>' +
                '    </div>' +
                '    <div class="form-group">' +
                '      <label>Contact Number <span>*</span></label>' +
                '      <input type="text" name="witness_details[' + index + '][contact_number]" value="' + contactNumber + '" required>' +
                '    </div>' +
                '  </div>' +
                '  <div class="form-group">' +
                '    <label>Address <span>*</span></label>' +
                '    <input type="text" name="witness_details[' + index + '][address]" value="' + address + '" required>' +
                '  </div>' +
                '  <button type="button" class="btn btn-secondary remove-witness-btn">Remove</button>' +
                '</div>';
        }

        function syncIndexedNames(containerSelector, baseName, fields) {
            var cards = document.querySelectorAll(containerSelector + ' .form-card');
            cards.forEach(function (card, index) {
                fields.forEach(function (field) {
                    var input = card.querySelector('[name*="[' + field + ']"]');
                    if (input) {
                        input.name = baseName + '[' + index + '][' + field + ']';
                    }
                });
            });
        }

        function addPerson(person) {
            var index = additionalPersonsContainer.querySelectorAll('.form-card').length;
            additionalPersonsContainer.insertAdjacentHTML('beforeend', buildPersonBlock(index, person));
        }

        function addWitness(witness) {
            var index = witnessDetailsContainer.querySelectorAll('.form-card').length;
            witnessDetailsContainer.insertAdjacentHTML('beforeend', buildWitnessBlock(index, witness));
        }

        function toggleAdditionalPersons() {
            var show = multipleYesRadio.checked;
            additionalPersonsSection.style.display = show ? 'block' : 'none';

            if (show && additionalPersonsContainer.querySelectorAll('.form-card').length === 0) {
                if (oldAdditionalPersons.length > 0) {
                    oldAdditionalPersons.forEach(function (person) {
                        addPerson(person);
                    });
                } else {
                    addPerson();
                }
            }

            additionalPersonsContainer.querySelectorAll('input, select').forEach(function (el) {
                if (el.name.includes('[full_name]') || el.name.includes('[college_department]') || el.name.includes('[role]')) {
                    el.required = show;
                } else {
                    el.required = false;
                }
            });
        }

        function toggleWitnessDetails() {
            var show = yesRadio.checked;
            witnessDetailsSection.style.display = show ? 'block' : 'none';

            if (show && witnessDetailsContainer.querySelectorAll('.form-card').length === 0) {
                if (oldWitnessDetails.length > 0) {
                    oldWitnessDetails.forEach(function (witness) {
                        addWitness(witness);
                    });
                } else {
                    addWitness();
                }
            }

            witnessDetailsContainer.querySelectorAll('input').forEach(function (el) {
                el.required = show;
            });
        }

        addAdditionalPersonBtn.addEventListener('click', function () {
            addPerson();
        });

        addWitnessBtn.addEventListener('click', function () {
            addWitness();
        });

        additionalPersonsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-person-btn')) {
                event.target.closest('.form-card').remove();
                syncIndexedNames('#additionalPersonsContainer', 'additional_persons', ['full_name', 'college_department', 'role', 'contact_number', 'email_address']);
            }
        });

        witnessDetailsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-witness-btn')) {
                event.target.closest('.form-card').remove();
                syncIndexedNames('#witnessDetailsContainer', 'witness_details', ['name', 'address', 'contact_number']);
            }
        });

        multipleYesRadio.addEventListener('change', toggleAdditionalPersons);
        multipleNoRadio.addEventListener('change', toggleAdditionalPersons);

        yesRadio.addEventListener('change', toggleWitnessDetails);
        noRadio.addEventListener('change', toggleWitnessDetails);

        toggleAdditionalPersons();
        toggleWitnessDetails();
    })();
</script>

@endsection
