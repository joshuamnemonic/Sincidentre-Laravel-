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

        $oldCategoryId = old('category_id');
        $oldMainCategoryCode = old('main_category_code');

        if (!$oldMainCategoryCode && $oldCategoryId) {
            foreach ($categoriesByMain as $mainCode => $group) {
                foreach ($group['items'] as $category) {
                    if ((string) $category->id === (string) $oldCategoryId) {
                        $oldMainCategoryCode = $mainCode;
                        break 2;
                    }
                }
            }
        }

        $categoryDataForJs = $categoriesByMain->map(function ($group) {
            return collect($group['items'])->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'classification' => $category->classification,
                ];
            })->values();
        })->toArray();

        $departmentOptionsForJs = collect($departments ?? [])->values()->all();
    @endphp

    <header class="page-header">
        <h1>LLCC Incident Report Form</h1>
        <p>Please complete all required fields before submitting.</p>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="form-wrapper animate">
        <form id="reportForm" action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <h2>Incident Type Routing (Required First Step)</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="main_category_code">Main Category Group (A-G) <span>*</span></label>
                    <select id="main_category_code" name="main_category_code" required>
                        <option value="">-- Select Main Category Group --</option>
                        @foreach($categoriesByMain as $mainCode => $group)
                            <option value="{{ $mainCode }}" {{ $oldMainCategoryCode == $mainCode ? 'selected' : '' }}>
                                {{ $mainCode }} - {{ $group['main_name'] }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-hint">Pick the category letter group first.</small>
                </div>

                <div class="form-group">
                    <label for="category_id">Specific Category <span>*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Specific Category --</option>
                    </select>
                    <small class="form-hint">Then choose the specific incident type.</small>
                </div>
            </div>

            <h2>Section 1: Information About the Person/s Involved in the Incident</h2>

            <div class="form-group">
                <label>Person Involvement <span>*</span></label>
                <div class="radio-inline-group">
                    <label class="radio-option"><input type="radio" name="person_involvement" value="known" {{ old('person_involvement') === 'known' ? 'checked' : '' }} required> Yes, known identity</label>
                    <label class="radio-option"><input type="radio" name="person_involvement" value="unknown" {{ old('person_involvement') === 'unknown' ? 'checked' : '' }} required> Yes, unknown identity</label>
                    <label class="radio-option"><input type="radio" name="person_involvement" value="none" {{ old('person_involvement') === 'none' ? 'checked' : '' }} required> No person involved</label>
                    <label class="radio-option"><input type="radio" name="person_involvement" value="unsure" {{ old('person_involvement') === 'unsure' ? 'checked' : '' }} required> Not sure yet</label>
                </div>
            </div>

            <div id="knownPersonSection" style="display:none;">

                <div class="form-row">
                    <div class="form-group">
                        <label for="person_full_name">Full Name <span>*</span></label>
                        <input type="text" id="person_full_name" name="person_full_name" value="{{ old('person_full_name') }}" placeholder="Name of person involved">
                    </div>

                    <div class="form-group">
                        <label for="person_college_department">College/Department <span>*</span></label>
                        <select id="person_college_department" name="person_college_department">
                            <option value="">-- Select College/Department --</option>
                            @foreach(($departments ?? []) as $departmentName)
                                <option value="{{ $departmentName }}" {{ old('person_college_department') === $departmentName ? 'selected' : '' }}>
                                    {{ $departmentName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="person_role">Role <span>*</span></label>
                        <select id="person_role" name="person_role">
                            <option value="">-- Select Role --</option>
                            <option value="Student" {{ old('person_role') == 'Student' ? 'selected' : '' }}>Student</option>
                            <option value="Faculty" {{ old('person_role') == 'Faculty' ? 'selected' : '' }}>Faculty</option>
                            <option value="Employee/Staff" {{ old('person_role') == 'Employee/Staff' ? 'selected' : '' }}>Employee/Staff</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="person_contact_number">Contact Number</label>
                        <input type="text" id="person_contact_number" name="person_contact_number" value="{{ old('person_contact_number') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="person_email_address">Email Address</label>
                    <input type="email" id="person_email_address" name="person_email_address" value="{{ old('person_email_address') }}">
                </div>

                <div class="form-group">
                    <label>Are there multiple persons involved? <span>*</span></label>
                    <div class="radio-inline-group">
                        <label class="radio-option" for="person_has_multiple_yes"><input type="radio" id="person_has_multiple_yes" name="person_has_multiple" value="1" {{ old('person_has_multiple') === '1' ? 'checked' : '' }}> Yes</label>
                        <label class="radio-option" for="person_has_multiple_no"><input type="radio" id="person_has_multiple_no" name="person_has_multiple" value="0" {{ old('person_has_multiple') === '0' ? 'checked' : '' }}> No</label>
                    </div>
                </div>

                <div id="additionalPersonsSection" style="display:none;">
                    <div id="additionalPersonsContainer"></div>
                    <button type="button" class="btn btn-secondary" id="addAdditionalPersonBtn">+ Add Another Person</button>
                </div>
            </div>

            <div id="unknownPersonSection" style="display:none;">
                <h3>Unknown Person Details</h3>
                <div class="form-group">
                    <label for="unknown_person_details">Unknown Identity Notes <span>*</span></label>
                    <textarea id="unknown_person_details" name="unknown_person_details" rows="5" placeholder="Describe appearance, role, location, time seen, and other useful identifiers">{{ old('unknown_person_details') }}</textarea>
                    <small class="form-hint">Include any clues: age range, clothing, role, nickname, companions, and last known location.</small>
                </div>
            </div>

            <div id="technicalFacilitySection" style="display:none;">
                <h3>Technical/Facility Impact Details</h3>
                <div class="form-group">
                    <label for="technical_facility_details">Technical/Facility Specific Details <span>*</span></label>
                    <textarea id="technical_facility_details" name="technical_facility_details" rows="5" placeholder="Describe affected area/asset, severity, operational impact, and recurrence">{{ old('technical_facility_details') }}</textarea>
                    <small class="form-hint">Required for Technical/Facility categories.</small>
                </div>
            </div>

            <h2>Section 2: Information About the Incident</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="incident_date">Date of Incident <span>*</span></label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}" required>
                </div>

                <div class="form-group">
                    <label for="incident_time">Time of Incident <span>*</span></label>
                    <input type="time" id="incident_time" name="incident_time" value="{{ old('incident_time') }}" required>
                </div>
            </div>

            <div class="form-row">
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
                    <input type="text" id="location_details" name="location_details" value="{{ old('location_details') }}" placeholder="Specific room, office, or area details">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description of Complaint or Incident <span>*</span></label>
                <textarea id="description" name="description" rows="7" placeholder="Include: what happened, how it happened, and factors leading to the event" required>{{ old('description') }}</textarea>
                <small class="form-hint">Give as much detail as you can to help the review process.</small>
            </div>

            <div class="form-group">
                <label>Were there any witnesses? <span>*</span></label>
                <div class="radio-inline-group">
                    <label class="radio-option" for="has_witnesses_yes"><input type="radio" id="has_witnesses_yes" name="has_witnesses" value="1" {{ old('has_witnesses') === '1' ? 'checked' : '' }} required> Yes</label>
                    <label class="radio-option" for="has_witnesses_no"><input type="radio" id="has_witnesses_no" name="has_witnesses" value="0" {{ old('has_witnesses') === '0' ? 'checked' : '' }} required> No</label>
                </div>
            </div>

            <div id="witnessDetailsSection" style="display:none;">
                <div id="witnessDetailsContainer"></div>
                <button type="button" class="btn btn-secondary" id="addWitnessBtn">+ Add Another Witness</button>
            </div>

            <div class="form-group">
                <label for="evidence">Supporting Evidence <span>*</span></label>
                <input
                    type="file"
                    id="evidence"
                    name="evidence[]"
                    accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,.mp3,.wav,.m4a,.aac,.ogg,.flac,.mp4,.mov,.avi,.mkv,.wmv,.webm"
                    required
                    multiple
                >
                <small class="form-hint">Required. Max 50MB per file. You can upload multiple files at once.</small>
            </div>

            <h2>Section 3: Information About the Informant (the person filing the report)</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="informant_full_name">Full Name <span>*</span></label>
                    <input type="text" id="informant_full_name" name="informant_full_name" value="{{ trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')) }}" readonly>
                </div>

                <div class="form-group">
                    <label for="informant_college_department">College/Department <span>*</span></label>
                    <input type="text" id="informant_college_department" name="informant_college_department" value="{{ Auth::user()->department?->name ?? (Auth::user()->employee_office ?? 'N/A') }}" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="informant_role">Role <span>*</span></label>
                    <input type="text" id="informant_role" name="informant_role" value="{{ $accountRole }}" readonly>
                </div>

                <div class="form-group">
                    <label for="informant_contact_number">Contact Number <span>*</span></label>
                    <input type="text" id="informant_contact_number" name="informant_contact_number" value="{{ old('informant_contact_number', Auth::user()->phone ?? '') }}" placeholder="Enter your contact number" required>
                </div>
            </div>

            <div class="form-group">
                <label for="informant_email_address">Email Address <span>*</span></label>
                <input type="email" id="informant_email_address" name="informant_email_address" value="{{ Auth::user()->email ?? '' }}" readonly>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Report</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
</div>

<script>
    (function () {
        var categoryData = @json($categoryDataForJs);
        var departmentOptions = @json($departmentOptionsForJs);

        var oldMainCategoryCode = @json($oldMainCategoryCode);
        var oldCategoryId = @json($oldCategoryId);
        var oldAdditionalPersons = @json($oldAdditionalPersons);
        var oldWitnessDetails = @json($oldWitnessDetails);

        var mainCategorySelect = document.getElementById('main_category_code');
        var categorySelect = document.getElementById('category_id');

        var involvementRadios = document.querySelectorAll('input[name="person_involvement"]');
        var knownPersonSection = document.getElementById('knownPersonSection');
        var unknownPersonSection = document.getElementById('unknownPersonSection');
        var technicalFacilitySection = document.getElementById('technicalFacilitySection');

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

        var knownPersonRequiredIds = ['person_full_name', 'person_college_department', 'person_role'];
        var unknownPersonDetails = document.getElementById('unknown_person_details');
        var technicalFacilityDetails = document.getElementById('technical_facility_details');

        function buildDepartmentOptionsHtml(selectedValue) {
            var options = '<option value="">-- Select College/Department --</option>';
            departmentOptions.forEach(function (departmentName) {
                var selected = String(selectedValue || '') === String(departmentName) ? ' selected' : '';
                options += '<option value="' + departmentName + '"' + selected + '>' + departmentName + '</option>';
            });
            return options;
        }

        function getSelectedInvolvement() {
            var selected = document.querySelector('input[name="person_involvement"]:checked');
            return selected ? selected.value : '';
        }

        function isTechnicalFacilityMainCategory(mainCode) {
            if (!mainCode || !categoryData[mainCode] || !categoryData[mainCode].length) {
                return false;
            }

            var firstCategory = categoryData[mainCode][0];
            var name = (firstCategory && firstCategory.name ? firstCategory.name : '').toLowerCase();

            return name.indexOf('technical') !== -1 || name.indexOf('facility') !== -1;
        }

        function populateSpecificCategories(mainCode, selectedCategoryId) {
            categorySelect.innerHTML = '<option value="">-- Select Specific Category --</option>';

            if (!mainCode || !categoryData[mainCode]) {
                return;
            }

            categoryData[mainCode].forEach(function (item) {
                var option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name + ' (' + item.classification + ')';
                if (selectedCategoryId && String(selectedCategoryId) === String(item.id)) {
                    option.selected = true;
                }
                categorySelect.appendChild(option);
            });
        }

        function setKnownSectionRequirements(enabled) {
            knownPersonRequiredIds.forEach(function (id) {
                var input = document.getElementById(id);
                if (input) {
                    input.required = enabled;
                }
            });

            if (multipleYesRadio) multipleYesRadio.required = enabled;
            if (multipleNoRadio) multipleNoRadio.required = enabled;

            if (!enabled) {
                if (multipleYesRadio) multipleYesRadio.checked = false;
                if (multipleNoRadio) multipleNoRadio.checked = false;
                additionalPersonsSection.style.display = 'none';
                additionalPersonsContainer.innerHTML = '';
            }
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

        function buildPersonBlock(index, person) {
            var fullName = person && person.full_name ? person.full_name : '';
            var collegeDepartment = person && person.college_department ? person.college_department : '';
            var role = person && person.role ? person.role : '';
            var contactNumber = person && person.contact_number ? person.contact_number : '';
            var emailAddress = person && person.email_address ? person.email_address : '';

            return '' +
                '<div class="form-card" style="margin-bottom:14px;padding:12px;border:1px solid #ddd;border-radius:8px;">' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Full Name <span>*</span></label>' +
                '      <input type="text" name="additional_persons[' + index + '][full_name]" value="' + fullName + '">' +
                '    </div>' +
                '    <div class="form-group">' +
                '      <label>College/Department <span>*</span></label>' +
                '      <select name="additional_persons[' + index + '][college_department]">' +
                         buildDepartmentOptionsHtml(collegeDepartment) +
                '      </select>' +
                '    </div>' +
                '  </div>' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Role <span>*</span></label>' +
                '      <select name="additional_persons[' + index + '][role]">' +
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
                '<div class="form-card" style="margin-bottom:14px;padding:12px;border:1px solid #ddd;border-radius:8px;">' +
                '  <div class="form-row">' +
                '    <div class="form-group">' +
                '      <label>Witness Name <span>*</span></label>' +
                '      <input type="text" name="witness_details[' + index + '][name]" value="' + name + '">' +
                '    </div>' +
                '    <div class="form-group">' +
                '      <label>Contact Number <span>*</span></label>' +
                '      <input type="text" name="witness_details[' + index + '][contact_number]" value="' + contactNumber + '">' +
                '    </div>' +
                '  </div>' +
                '  <div class="form-group">' +
                '    <label>Address <span>*</span></label>' +
                '    <input type="text" name="witness_details[' + index + '][address]" value="' + address + '">' +
                '  </div>' +
                '  <button type="button" class="btn btn-secondary remove-witness-btn">Remove</button>' +
                '</div>';
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
            var show = multipleYesRadio && multipleYesRadio.checked;
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
            var show = yesRadio && yesRadio.checked;
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

        function togglePersonSections() {
            var involvement = getSelectedInvolvement();
            var showKnown = involvement === 'known';
            var showUnknown = involvement === 'unknown';

            knownPersonSection.style.display = showKnown ? 'block' : 'none';
            unknownPersonSection.style.display = showUnknown ? 'block' : 'none';

            setKnownSectionRequirements(showKnown);

            if (unknownPersonDetails) {
                unknownPersonDetails.required = showUnknown;
                if (!showUnknown) {
                    unknownPersonDetails.value = '';
                }
            }
        }

        function toggleTechnicalFacilitySection() {
            var mainCode = mainCategorySelect.value;
            var show = isTechnicalFacilityMainCategory(mainCode);
            technicalFacilitySection.style.display = show ? 'block' : 'none';

            if (technicalFacilityDetails) {
                technicalFacilityDetails.required = show;
                if (!show) {
                    technicalFacilityDetails.value = '';
                }
            }
        }

        if (mainCategorySelect) {
            mainCategorySelect.addEventListener('change', function () {
                populateSpecificCategories(mainCategorySelect.value, null);
                toggleTechnicalFacilitySection();
            });
        }

        if (addAdditionalPersonBtn) {
            addAdditionalPersonBtn.addEventListener('click', function () {
                addPerson();
                toggleAdditionalPersons();
            });
        }

        if (addWitnessBtn) {
            addWitnessBtn.addEventListener('click', function () {
                addWitness();
                toggleWitnessDetails();
            });
        }

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

        if (multipleYesRadio) multipleYesRadio.addEventListener('change', toggleAdditionalPersons);
        if (multipleNoRadio) multipleNoRadio.addEventListener('change', toggleAdditionalPersons);

        if (yesRadio) yesRadio.addEventListener('change', toggleWitnessDetails);
        if (noRadio) noRadio.addEventListener('change', toggleWitnessDetails);

        involvementRadios.forEach(function (radio) {
            radio.addEventListener('change', togglePersonSections);
        });

        populateSpecificCategories(oldMainCategoryCode || mainCategorySelect.value, oldCategoryId);
        togglePersonSections();
        toggleTechnicalFacilitySection();
        toggleAdditionalPersons();
        toggleWitnessDetails();
    })();
</script>

@push('styles')
    <style>
        .radio-inline-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            align-items: center;
        }

        .radio-option {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin: 0;
        }

        .radio-option input[type="radio"] {
            width: auto;
            min-width: 0;
            margin: 0;
        }
    </style>
@endpush
@endsection
