<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Created Courses</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f4f4f4;
            }
            .progress-bar {
                height: 8px;
                border-radius: 4px;
                background-color: #e5e7eb;
            }
            .progress-fill {
                height: 8px;
                border-radius: 4px;
                background-color: #3b82f6;
            }
            .course-item {
                cursor: grab;
                transition: background 0.3s ease;
            }
            .course-item:hover {
                background-color: #d1d5db;
            }
            .dragging {
                opacity: 0.5;
            }
            .pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-top: 20px;
            }
            .pagination a, .pagination span {
                margin: 0 5px;
                padding: 8px 12px;
                border-radius: 5px;
                font-size: 14px;
                text-decoration: none;
                background: #fff;
                color: #333;
                border: 1px solid #ddd;
            }
            .pagination a:hover {
                background: #3b82f6;
                color: #fff;
            }
            .pagination .active {
                background: #3b82f6;
                color: #fff;
                font-weight: bold;
            }
        </style>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
            // Initialize sortable list for created courses
            let sortable = new Sortable(document.getElementById("createdCoursesList"), {
            animation: 150,
                    ghostClass: "dragging",
                    onEnd: function (evt) {
                    console.log("Moved item from index", evt.oldIndex, "to", evt.newIndex);
                    }
            });
            // Handle form submission for editing courses
            let editForm = document.getElementById("editCourseForm");
            if (editForm) {
            editForm.onsubmit = function (event) {
            event.preventDefault(); // Prevent default form submission

            let courseID = document.getElementById("editCourseID").value;
            if (!courseID) {
            console.error("Course ID is missing!");
            return;
            }

            let formAction = `/course/update/${courseID}`;
            this.action = formAction;
            this.submit();
            };
            }
            });
// Function to display course details in the right panel
            function showCourseDetails(courseID, courseName, category, description, studentCount, capacityOffered, progress, image) {
            document.getElementById("selectedCourseName").innerText = courseName;
            document.getElementById("selectedCategory").innerText = category;
            document.getElementById("selectedDescription").innerText = description;
            document.getElementById("studentCount").innerText = studentCount + " students enrolled";
            document.getElementById("capacityOffered").innerText = "Capacity: " + capacityOffered;
            document.getElementById("selectedCourseImage").src = image;
            // Ensure progress is within valid range (0-100)
            let progressValue = parseInt(progress, 10) || 0;
            progressValue = Math.min(Math.max(progressValue, 0), 100);
            // Update progress bar in the right section
            let progressBar = document.getElementById("progressBar");
            progressBar.style.width = progressValue + "%";
            document.getElementById("progressText").innerText = progressValue + "% Progress";
            // Update Manage Course button link dynamically
            document.getElementById("manageCourseLink").href = `/course/${courseID}/chapters`;
            // Hide default message and show course details
            document.getElementById("defaultMessage").classList.add("hidden");
            document.getElementById("courseDetails").classList.remove("hidden");
            // Update progress bar in the left section dynamically
            let leftProgressBar = document.getElementById(`progressBar_${courseID}`);
            if (leftProgressBar) {
            leftProgressBar.style.width = progressValue + "%";
            }
            }

// Function to open the edit modal and prefill fields
            // Function to open the edit modal and prefill fields
            function openEditModal() {
            let courseID = document.getElementById("manageCourseLink").href.split('/').slice( - 2, - 1)[0]; // Extract course ID
            let courseName = document.getElementById("selectedCourseName").innerText;
            let description = document.getElementById("selectedDescription").innerText;
            let capacityOffered = document.getElementById("capacityOffered").innerText.replace("Capacity: ", "");
            if (!courseID) {
            console.error("Course ID is missing!");
            return;
            }

            // Set values in the modal
            document.getElementById("editCourseID").value = courseID;
            document.getElementById("editCourseName").value = courseName;
            document.getElementById("editCourseOverview").value = description;
            document.getElementById("editCourseCapacity").value = capacityOffered;
            // Set the form action dynamically
            document.getElementById("editCourseForm").action = `/course/update/${courseID}`;
            // Show the modal
            document.getElementById("editCourseModal").classList.remove("hidden");
            }

            document.addEventListener("DOMContentLoaded", function () {
            let sortableList = document.getElementById("sortableCourses");
            new Sortable(sortableList, {
            animation: 150,
                    ghostClass: "dragging"
            });
            });
            function saveCourseOrder() {
            let sortedItems = document.querySelectorAll("#sortableCourses .course-item");
            let newOrder = Array.from(sortedItems).map((item, index) => ({
            courseID: item.getAttribute("data-course-id"),
                    newPosition: index + 1
            }));
            fetch("{{ route('courses.reorder') }}", {
            method: "POST",
                    headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ order: newOrder })
            })
                    .then(response => response.json())
                    .then(data => {
                    if (data.success) {
                    alert("Order saved successfully!");
                    closeArrangeModal();
                    location.reload(); // Refresh sidebar
                    } else {
                    alert("Failed to save order.");
                    }
                    })
                    .catch(error => console.error("Error:", error));
            }


            document.addEventListener("DOMContentLoaded", function () {
            let arrangeBtn = document.getElementById("arrangeCoursesBtn");
            let saveOrderBtn = document.getElementById("saveOrderBtn");
            // Initialize Sortable.js
            let sortableList = document.getElementById("sortableCourses");
            let sortable = new Sortable(sortableList, {
            animation: 150,
            });
            // Open Modal
            arrangeBtn.addEventListener("click", function () {
            $("#arrangeCoursesModal").modal("show");
            });
            // Save new order
            saveOrderBtn.addEventListener("click", function () {
            let courseOrder = [];
            document.querySelectorAll("#sortableCourses li").forEach((item, index) => {
            courseOrder.push({
            courseID: item.dataset.id,
                    order: index + 1,
            });
            });
            // Send data to backend
            fetch("{{ route('courses.reorder') }}", {
            method: "POST",
                    headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ order: courseOrder }),
            })
                    .then(response => response.json())
                    .then(data => {
                    if (data.success) {
                    alert("Order saved successfully!");
                    location.reload(); // Refresh sidebar
                    } else {
                    alert("Failed to save order.");
                    }
                    })
                    .catch(error => console.error("Error:", error));
            });
            });
            function arrangeCourses() {
            document.getElementById("arrangeCoursesModal").classList.remove("hidden");
            }
            function closeArrangeModal() {
            document.getElementById("arrangeCoursesModal").classList.add("hidden");
            }
// Function to close the edit modal
            function closeEditModal() {
            document.getElementById("editCourseModal").classList.add("hidden");
            }
        </script>
    </head>
    <body>
        @include('navigation')

        <div class="max-w-6xl mx-auto mt-8 p-4">
            <div class="grid grid-cols-3 gap-6">
                <!-- Left Sidebar: Created Courses -->
                <div class="col-span-1 bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Created Courses</h2>
                    <!-- Arrange Courses Button -->
                    <button id="arrangeCoursesBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1.5 px-3 text-sm mb-4 rounded-md shadow transition duration-300 ease-in-out" onclick="arrangeCourses()">
                        Arrange Courses
                    </button>

                    <div id="createdCoursesList" class="space-y-4">
                        @foreach ($courses as $course)
                        @php
                        $courseImage = $course->image ? Storage::url($course->image) : asset('images/course-placeholder.jpg');
                        @endphp
                        <div class="course-item bg-gray-100 p-4 rounded-lg flex items-center space-x-4"
                             onclick="showCourseDetails('{{ $course->courseID }}', '{{ $course->courseName }}', '{{ ucfirst($course->category) }}', '{{ $course->description }}', '{{ $course->studentCount }}', '{{ $course->capacityOffered }}', '{{ $course->progress }}', '{{ $courseImage }}')">
                            <img src="{{ $courseImage }}" alt="Course Image" class="w-12 h-12 rounded-full">
                            <div>
                                <p class="text-lg font-semibold">{{ $course->courseName }}</p>
                                <p class="text-sm text-gray-600">Category : {{ ucfirst($course->category) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $courses->links() }}
                    </div>
                </div>

                <!-- Right Section: Course Details -->
                <div id="courseDetails" class="col-span-2 bg-white p-6 rounded-lg shadow hidden">
                    <img id="selectedCourseImage" src="{{ asset('images/selected-course.jpg') }}" class="w-full h-60 object-cover rounded-lg" alt="Course Image">
                    <h2 class="text-2xl font-bold mt-4" id="selectedCourseName"></h2>
                    <p class="text-gray-500 text-sm mt-1" id="selectedCategory"></p>

                    <h3 class="text-lg font-bold mt-4">Course Overview</h3>
                    <p class="text-gray-600 mt-0.5" id="selectedDescription"></p>

                    <p class="text-gray-600 mt-2" id="studentCount"></p>
                    <p class="text-gray-600 mt-1" id="capacityOffered"></p>

                    <div class="progress-bar mt-4">
                        <div class="progress-fill" id="progressBar" style="width: 0%;"></div>
                    </div>
                    <p class="text-xs text-gray-600 mt-1" id="progressText">0% Progress</p>


                    <div class="mt-6">
                        <a id="manageCourseLink" href="#" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                            Manage Course
                        </a>
                    </div>
                    <div class="mt-4">
                        <button onclick="openEditModal()" 
                                class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                            Edit Course
                        </button>
                    </div>


                </div>

                <!-- Default Message (Visible Initially) -->
                <div id="defaultMessage" class="col-span-2 bg-white p-6 rounded-lg shadow flex flex-col justify-center items-center h-64">
                    <img src="{{ asset('photo/selectIcon.png') }}" alt="Select Course Icon" class="w-24 h-24 mb-4">
                    <h2 class="text-2xl font-semibold text-gray-500">Select a Course to View Details</h2>
                </div>
            </div>
        </div>

        <!-- Arrange Courses Modal -->
        <div id="arrangeCoursesModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
                <h2 class="text-lg font-bold mb-4">Arrange Courses</h2>
                <ul id="sortableCourses" class="space-y-2">
                    @foreach ($createdCourses as $course)
                    <li class="course-item bg-gray-100 p-3 rounded flex items-center justify-between"
                        data-course-id="{{ $course->courseID }}">
                        <span>{{ $course->courseName }}</span>
                        <span class="cursor-move">☰</span>
                    </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex justify-end space-x-2">
                    <button onclick="closeArrangeModal()" class="px-4 py-2 bg-gray-400 text-white rounded">Cancel</button>
                    <button onclick="saveCourseOrder()" class="px-4 py-2 bg-blue-600 text-white rounded">Save Order</button>
                </div>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <div id="editCourseModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <h2 class="text-xl font-bold mb-4">Edit Course</h2>
                <form id="editCourseForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" id="editCourseID" name="courseID">

                    <label class="block text-sm font-medium">Course Name</label>
                    <input type="text" name="courseName" id="editCourseName" class="block w-full border p-2 rounded mb-2">

                    <label class="block text-sm font-medium">Overview</label>
                    <textarea name="courseOverview" id="editCourseOverview" class="block w-full border p-2 rounded mb-2"></textarea>

                    <label class="block text-sm font-medium">Capacity</label>
                    <input type="number" name="capacityOffered" id="editCourseCapacity" class="block w-full border p-2 rounded mb-2">

                    <label class="block text-sm font-medium">Upload New Image (Optional)</label>
                    <input type="file" name="courseImage" id="editCourseImage" class="block w-full border p-2 rounded mb-2">

                    <div class="flex justify-end">
                        <button type="button" onclick="closeEditModal()" class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Save Changes</button>
                    </div>
                </form>

            </div>
        </div>
    </body>
</html>
