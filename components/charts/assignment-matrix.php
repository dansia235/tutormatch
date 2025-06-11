<?php
/**
 * Assignment matrix component
 * 
 * @param array  $students    List of students
 * @param array  $teachers    List of teachers
 * @param array  $assignments Current assignments (student_id => teacher_id)
 * @param array  $weights     Compatibility weights (student_id => [teacher_id => weight])
 * @param bool   $editable    Whether the matrix is editable
 * @param string $updateUrl   URL for updating assignments via AJAX
 * @param string $id          Matrix ID
 * @param string $class       Additional CSS classes
 * @param array  $attributes  Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'assignment-matrix-' . uniqid();
$editable = $editable ?? false;
$updateUrl = $updateUrl ?? '';
$class = $class ?? '';
$attributes = $attributes ?? [];
$students = $students ?? [];
$teachers = $teachers ?? [];
$assignments = $assignments ?? [];
$weights = $weights ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Helper function to get cell color based on weight
function getWeightColor($weight) {
    $weight = floatval($weight);
    if ($weight >= 0.8) return 'bg-success-100';
    if ($weight >= 0.6) return 'bg-success-50';
    if ($weight >= 0.4) return 'bg-warning-50';
    if ($weight >= 0.2) return 'bg-danger-50';
    return 'bg-danger-100';
}

// Prepare data for assignment matrix controller
$studentsJson = json_encode($students);
$teachersJson = json_encode($teachers);
$assignmentsJson = json_encode($assignments);
$weightsJson = json_encode($weights);
?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="assignment-matrix overflow-x-auto <?php echo htmlspecialchars($class); ?>"
    data-controller="assignment-matrix"
    data-assignment-matrix-students-value='<?php echo $studentsJson; ?>'
    data-assignment-matrix-teachers-value='<?php echo $teachersJson; ?>'
    data-assignment-matrix-assignments-value='<?php echo $assignmentsJson; ?>'
    data-assignment-matrix-weights-value='<?php echo $weightsJson; ?>'
    data-assignment-matrix-editable-value="<?php echo $editable ? 'true' : 'false'; ?>"
    data-assignment-matrix-update-url-value="<?php echo htmlspecialchars($updateUrl); ?>"
    <?php echo $attributesStr; ?>
>
    <table class="min-w-full border border-gray-200 assignment-matrix-table">
        <thead>
            <tr>
                <th class="w-40 bg-gray-50 border border-gray-200 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-10">
                    Étudiants / Tuteurs
                </th>
                <?php foreach ($teachers as $teacher): ?>
                    <th class="border border-gray-200 px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <?php echo htmlspecialchars($teacher['name'] ?? $teacher['id']); ?>
                        <?php if (isset($teacher['capacity'])): ?>
                            <div class="text-xxs font-normal normal-case mt-1">
                                Capacité: <?php echo htmlspecialchars($teacher['capacity']); ?>
                            </div>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td class="border border-gray-200 px-4 py-2 text-sm text-gray-900 bg-gray-50 font-medium sticky left-0 z-10">
                        <?php echo htmlspecialchars($student['name'] ?? $student['id']); ?>
                    </td>
                    <?php foreach ($teachers as $teacher): ?>
                        <?php
                            $studentId = $student['id'];
                            $teacherId = $teacher['id'];
                            $isAssigned = isset($assignments[$studentId]) && $assignments[$studentId] === $teacherId;
                            $weight = isset($weights[$studentId][$teacherId]) ? $weights[$studentId][$teacherId] : 0;
                            $cellClass = getWeightColor($weight);
                        ?>
                        <td 
                            class="border border-gray-200 px-2 py-2 text-center <?php echo $cellClass; ?> <?php echo $isAssigned ? 'assigned-cell' : ''; ?> <?php echo $editable ? 'cursor-pointer hover:bg-gray-100' : ''; ?>"
                            data-student-id="<?php echo htmlspecialchars($studentId); ?>"
                            data-teacher-id="<?php echo htmlspecialchars($teacherId); ?>"
                            data-weight="<?php echo htmlspecialchars($weight); ?>"
                            <?php echo $editable ? 'data-action="click->assignment-matrix#toggleAssignment"' : ''; ?>
                        >
                            <?php if ($isAssigned): ?>
                                <div class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="text-xs"><?php echo number_format($weight * 100, 0); ?>%</div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="w-40 bg-gray-50 border border-gray-200 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-10">
                    Affectations
                </th>
                <?php foreach ($teachers as $teacher): ?>
                    <?php
                        $teacherId = $teacher['id'];
                        $assignedCount = array_count_values($assignments)[$teacherId] ?? 0;
                        $capacity = $teacher['capacity'] ?? 0;
                        $capacityClass = '';
                        if ($capacity > 0) {
                            if ($assignedCount > $capacity) {
                                $capacityClass = 'text-danger-500 font-bold';
                            } elseif ($assignedCount === $capacity) {
                                $capacityClass = 'text-success-500 font-bold';
                            }
                        }
                    ?>
                    <th class="border border-gray-200 px-4 py-2 text-center text-xs font-medium <?php echo $capacityClass; ?>">
                        <?php echo $assignedCount; ?><?php echo $capacity ? ' / ' . $capacity : ''; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </tfoot>
    </table>
</div>