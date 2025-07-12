<?php
// Function to simulate user info
function get_user_info() {
    return [
        'name' => 'Eliza Malik',
        'storage_used' => '218 GB'
    ];
}

// Function to simulate folder types data
function get_folder_types_data() {
    return [
        ['name' => 'Image', 'items' => '240 Items'],
        ['name' => 'Video', 'items' => '240 Items'],
        ['name' => 'Documents', 'items' => '240 Items'],
        ['name' => 'Audio', 'items' => '240 Items'],
        ['name' => 'Movies', 'items' => '240 Items'],
        ['name' => 'Assignment', 'items' => '240 Items'],
        ['name' => 'UI-Kit', 'items' => '240 Items'],
        ['name' => 'Design', 'items' => '240 Items'],
    ];
}

// Function to simulate storage details data
function get_storage_details_data() {
    return [
        'used' => 75,
        'total' => 100,
        'categories' => [
            ['name' => 'Documents', 'size' => 6.674, 'percentage' => 60, 'icon' => 'fas fa-file-alt', 'color' => '#007bff'],
            ['name' => 'Videos', 'size' => 1.834, 'percentage' => 30, 'icon' => 'fas fa-video', 'color' => '#28a745'],
            ['name' => 'Images', 'size' => 0.511, 'percentage' => 10, 'icon' => 'fas fa-image', 'color' => '#fd7e14'],
        ]
    ];
}

// Function to simulate activity chart data
function get_activity_chart_data() {
    return [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'], // Example months
        'data' => [65, 59, 80, 81, 56, 55, 40] // Example activity data
    ];
}

// Function to simulate uploading on drive data
function get_uploading_on_drive_data() {
    return [
        ['filename' => 'Onboarding.zip', 'time' => 33, 'progress' => 80],
        ['filename' => 'Web Design.zip', 'time' => 45, 'progress' => 60],
        ['filename' => 'Transfer.zip', 'time' => 25, 'progress' => 90],
        ['filename' => 'Website.zip', 'time' => 85, 'progress' => 40],
        ['filename' => 'Design.pptx', 'time' => 70, 'progress' => 50],
        ['filename' => 'Landing Page.zip', 'time' => 65, 'progress' => 70],
        ['filename' => 'Design.pptx', 'time' => 73, 'progress' => 30],
    ];
}
?>
