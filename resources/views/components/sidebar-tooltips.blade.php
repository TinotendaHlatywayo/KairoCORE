<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageBriefs = {
        'Academic Years': 'Configure operating years and set the active calendar year for billing and enrollments.',
        'Classrooms': 'Register physical rooms, lecture halls, and seating capacities for timetables.',
        'Subjects': 'Define institutional curriculum subjects and associate them with departments.',
        'Level': 'Manage grade levels and class streams for student cohorts.',
        'Students': 'Comprehensive directory of enrolled learners, profiles, guardians, and medical records.',
        'ID Card Templates': 'Design and activate official student and staff ID cards with secure QR verification.',
        'Student Directory': 'Search, filter, import, and export student roster records across all streams.',
        'Timetables': 'Schedule weekly lessons with automated teacher and classroom conflict detection.',
        'Assessment Center': 'Manage digital assessments and central question banks.',
        'Fee Structures': 'Define termly tuition fees, boarding levies, and generate student invoices.',
        'Employees': 'Staff directory, payroll assignments, and HR records.'
    };

    const attachTooltips = () => {
        document.querySelectorAll('.fi-sidebar-item-label, .fi-sidebar-item-button span').forEach(el => {
            const text = el.textContent.trim();
            if (pageBriefs[text] && !el.closest('.fi-sidebar-group-label')) {
                const item = el.closest('a') || el.closest('.fi-sidebar-item');
                if (item && !item.hasAttribute('title')) {
                    item.setAttribute('title', pageBriefs[text]);
                }
            }
        });
    };

    attachTooltips();
    document.addEventListener('livewire:navigated', attachTooltips);
});
</script>
