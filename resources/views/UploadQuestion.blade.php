<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upload Questions</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f8f9fa;
                margin: 0;
                padding: 0;
            }

            h2 {
                color: #1a73e8;
                text-align: center;
                margin-top: 2rem;
                font-size: 2rem;
                font-weight: 600;
            }

            .container {
                max-width: 1200px;
                margin: 2rem auto;
                padding: 0 1rem;
            }

            .card {
                background-color: #ffffff;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 1.5rem;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            }

            .upload-box {
                background-color: #f9f9f9;
                border: 2px dashed #1a73e8;
                border-radius: 12px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .upload-box:hover {
                background-color: #e9f2ff;
            }

            .btn {
                background-color: #1a73e8;
                color: #fff;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.875rem;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }

            .btn:hover {
                background-color: #1557b0;
            }

            .btn-success {
                background-color: #28a745;
            }

            .btn-success:hover {
                background-color: #218838;
            }

            .btn-danger {
                background-color: #dc3545;
            }

            .btn-danger:hover {
                background-color: #c82333;
            }

            .btn-warning {
                background-color: #ffc107;
                color: #000;
            }

            .btn-warning:hover {
                background-color: #e0a800;
            }

            .category-card {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 1rem;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .category-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .selected-course-card {
                background-color: #e9f2ff;
                border: 1px solid #1a73e8;
                border-radius: 12px;
                padding: 1.5rem;
            }

            .text-muted {
                color: #6c757d;
                font-size: 0.875rem;
            }

            .list-unstyled {
                padding-left: 0;
                list-style: none;
            }

            .list-unstyled li {
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                color: #333;
            }

            .row {
                display: flex;
                flex-wrap: wrap;
                margin: -0.75rem;
            }

            .col-md-8, .col-md-4 {
                padding: 0.75rem;
            }

            .col-md-8 {
                flex: 0 0 66.666667%;
                max-width: 66.666667%;
            }

            .col-md-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }

            .w-100 {
                width: 100%;
            }

            .mt-2 {
                margin-top: 0.5rem;
            }

            .mt-3 {
                margin-top: 1rem;
            }

            .mt-4 {
                margin-top: 1.5rem;
            }

            .mb-3 {
                margin-bottom: 1rem;
            }

            .fw-bold {
                font-weight: 600;
            }

            .fas {
                margin-right: 0.5rem;
            }
        </style>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                let fileInput = document.getElementById("questionFile");
                let uploadBox = document.querySelector(".upload-box");
                let processBtn = document.querySelector(".btn-success.mt-3.w-100");

                // Trigger file input when clicking the upload box
                uploadBox.addEventListener("click", function () {
                    fileInput.click();
                });

                // Update file name display
                fileInput.addEventListener("change", function () {
                    if (fileInput.files.length > 0) {
                        uploadBox.innerHTML = `<p>${fileInput.files[0].name}</p>`;
                    }
                });

                // Handle drag & drop
                uploadBox.addEventListener("dragover", function (e) {
                    e.preventDefault();
                    uploadBox.style.backgroundColor = "#e9f2ff";
                });

                uploadBox.addEventListener("dragleave", function (e) {
                    uploadBox.style.backgroundColor = "#f9f9f9";
                });

                uploadBox.addEventListener("drop", function (e) {
                    e.preventDefault();
                    uploadBox.style.backgroundColor = "#f9f9f9";

                    if (e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        uploadBox.innerHTML = `<p>${e.dataTransfer.files[0].name}</p>`;
                    }
                });

                // Process uploaded file
                processBtn.addEventListener("click", function () {
                    if (!fileInput.files.length) {
                        alert("Please select a file first.");
                        return;
                    }

                    let formData = new FormData();
                    formData.append("file", fileInput.files[0]);

                    fetch('/process-questions', {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                        body: formData
                    })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => {
                                        throw new Error(err.error || 'Network response was not ok');
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.error) {
                                    alert(data.error);
                                    return;
                                }

                                if (!data.categorized_questions) {
                                    alert("No questions were categorized.");
                                    return;
                                }

                                let easyList = "";
                                let mediumList = "";
                                let hardList = "";

                                data.categorized_questions.forEach(q => {
                                    if (q.difficulty === 'Easy') {
                                        easyList += `<li>${q.question}</li>`;
                                    } else if (q.difficulty === 'Normal') {
                                        mediumList += `<li>${q.question}</li>`;
                                    } else {
                                        hardList += `<li>${q.question}</li>`;
                                    }
                                });

                                document.getElementById("easyQuestions").innerHTML = easyList;
                                document.getElementById("moderateQuestions").innerHTML = mediumList;
                                document.getElementById("hardQuestions").innerHTML = hardList;
                            })
                            .catch(error => {
                                console.error("Error:", error);
                                alert(error.message || "An error occurred while processing the file.");
                            });
                });
            });
        </script>
    </head>
    <body>
        <header>
            @include('navigation')
        </header>
        <h2>Upload Question</h2>

        <div class="container">
            <div class="row">
                <!-- Upload Section -->
                <div class="col-md-8">
                    <div class="card">
                        <h4 class="mb-3">Upload Questions for AI Leveling</h4>
                        <div class="upload-box">
                            <input type="file" id="questionFile" class="d-none">
                            <label for="questionFile" class="btn">
                                <i class="fas fa-upload"></i> Choose File
                            </label>
                            <p class="text-muted mt-2">Drag & Drop your question file here</p>
                        </div>
                        <button class="btn btn-success mt-3 w-100">Process with AI</button>
                    </div>

                    <!-- AI Categorized Questions -->
                    <div class="card mt-4">
                        <h4 class="mb-3">AI Categorized Questions</h4>
                        <div class="row">
                            <!-- Easy -->
                            <div class="col-md-4">
                                <div class="category-card">
                                    <h6 class="fw-bold"><i class="fas fa-check-circle"></i> Easy</h6>
                                    <ul id="easyQuestions" class="list-unstyled">
                                        <!-- Easy questions will be populated here -->
                                    </ul>
                                </div>
                            </div>
                            <!-- Moderate -->
                            <div class="col-md-4">
                                <div class="category-card">
                                    <h6 class="fw-bold"><i class="fas fa-check-circle"></i> Moderate</h6>
                                    <ul id="moderateQuestions" class="list-unstyled">
                                        <!-- Moderate questions will be populated here -->
                                    </ul>
                                </div>
                            </div>
                            <!-- Difficult -->
                            <div class="col-md-4">
                                <div class="category-card">
                                    <h6 class="fw-bold"><i class="fas fa-check-circle"></i> Difficult</h6>
                                    <ul id="hardQuestions" class="list-unstyled">
                                        <!-- Difficult questions will be populated here -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-warning mt-3 w-100">Modify Question</button>
                    </div>
                </div>

                <!-- Selected Course -->
                <div class="col-md-4">
                    <div class="card selected-course-card">
                        <h4 class="mb-3">Selected Course</h4>
                        <p><strong>Course:</strong></p>
                        <p><strong>Chapter:</strong></p>
                        <p><strong>Part:</strong>
                            <select name="partID" class="w-full border rounded-lg p-2 mt-1" required>
                            </select>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>