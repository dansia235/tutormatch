/**
 * Assignment Matrix module
 * Responsible for the interactive assignment matrix visualization
 */

/**
 * Generate compatibility color class based on weight value
 * 
 * @param {Number} weight - Compatibility weight (0-1)
 * @returns {String} Tailwind CSS color class
 */
export function getCompatibilityColorClass(weight) {
    if (weight >= 0.8) return 'bg-success-100';
    if (weight >= 0.6) return 'bg-success-50';
    if (weight >= 0.4) return 'bg-warning-50';
    if (weight >= 0.2) return 'bg-danger-50';
    return 'bg-danger-100';
}

/**
 * Create an assignment matrix visualization
 * 
 * @param {HTMLElement} container - Container element
 * @param {Array} students - Array of student objects
 * @param {Array} teachers - Array of teacher objects
 * @param {Object} assignments - Current assignments (student_id => teacher_id)
 * @param {Object} weights - Compatibility weights (student_id => {teacher_id => weight})
 * @param {Object} options - Additional options
 * @returns {Object} Matrix object with update methods
 */
export function createAssignmentMatrix(container, students, teachers, assignments = {}, weights = {}, options = {}) {
    const editable = options.editable || false;
    
    // Create the table element
    const table = document.createElement('table');
    table.className = 'min-w-full border border-gray-200 assignment-matrix-table';
    
    // Create header row with teacher names
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    // Add corner cell
    const cornerCell = document.createElement('th');
    cornerCell.className = 'w-40 bg-gray-50 border border-gray-200 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-10';
    cornerCell.textContent = 'Étudiants / Tuteurs';
    headerRow.appendChild(cornerCell);
    
    // Add teacher headers
    teachers.forEach(teacher => {
        const th = document.createElement('th');
        th.className = 'border border-gray-200 px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider';
        
        const teacherName = document.createElement('div');
        teacherName.textContent = teacher.name || teacher.id;
        th.appendChild(teacherName);
        
        if (teacher.capacity) {
            const capacityDiv = document.createElement('div');
            capacityDiv.className = 'text-xxs font-normal normal-case mt-1';
            capacityDiv.textContent = `Capacité: ${teacher.capacity}`;
            th.appendChild(capacityDiv);
        }
        
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Create table body with cells
    const tbody = document.createElement('tbody');
    
    students.forEach(student => {
        const row = document.createElement('tr');
        
        // Add student name cell
        const nameCell = document.createElement('td');
        nameCell.className = 'border border-gray-200 px-4 py-2 text-sm text-gray-900 bg-gray-50 font-medium sticky left-0 z-10';
        nameCell.textContent = student.name || student.id;
        row.appendChild(nameCell);
        
        // Add compatibility cells for each teacher
        teachers.forEach(teacher => {
            const studentId = student.id;
            const teacherId = teacher.id;
            const isAssigned = assignments[studentId] === teacherId;
            const weight = weights[studentId]?.[teacherId] || 0;
            const colorClass = getCompatibilityColorClass(weight);
            
            const cell = document.createElement('td');
            cell.className = `border border-gray-200 px-2 py-2 text-center ${colorClass} ${isAssigned ? 'assigned-cell' : ''} ${editable ? 'cursor-pointer hover:bg-gray-100' : ''}`;
            cell.dataset.studentId = studentId;
            cell.dataset.teacherId = teacherId;
            cell.dataset.weight = weight;
            
            if (isAssigned) {
                const checkmarkDiv = document.createElement('div');
                checkmarkDiv.className = 'flex items-center justify-center';
                checkmarkDiv.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                `;
                cell.appendChild(checkmarkDiv);
            }
            
            const weightDiv = document.createElement('div');
            weightDiv.className = 'text-xs';
            weightDiv.textContent = `${Math.round(weight * 100)}%`;
            cell.appendChild(weightDiv);
            
            // Add click handler for editable cells
            if (editable) {
                cell.addEventListener('click', () => {
                    toggleAssignment(studentId, teacherId);
                });
            }
            
            row.appendChild(cell);
        });
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    
    // Create footer with teacher assignment counts
    const tfoot = document.createElement('tfoot');
    const footerRow = document.createElement('tr');
    
    // Add corner cell
    const footerCorner = document.createElement('th');
    footerCorner.className = 'w-40 bg-gray-50 border border-gray-200 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 z-10';
    footerCorner.textContent = 'Affectations';
    footerRow.appendChild(footerCorner);
    
    // Count assignments per teacher
    const assignmentCounts = {};
    Object.values(assignments).forEach(teacherId => {
        assignmentCounts[teacherId] = (assignmentCounts[teacherId] || 0) + 1;
    });
    
    // Add assignment count cells
    teachers.forEach(teacher => {
        const teacherId = teacher.id;
        const assignedCount = assignmentCounts[teacherId] || 0;
        const capacity = teacher.capacity || 0;
        
        let capacityClass = '';
        if (capacity > 0) {
            if (assignedCount > capacity) {
                capacityClass = 'text-danger-500 font-bold';
            } else if (assignedCount === capacity) {
                capacityClass = 'text-success-500 font-bold';
            }
        }
        
        const cell = document.createElement('th');
        cell.className = `border border-gray-200 px-4 py-2 text-center text-xs font-medium ${capacityClass}`;
        cell.textContent = capacity ? `${assignedCount} / ${capacity}` : assignedCount;
        footerRow.appendChild(cell);
    });
    
    tfoot.appendChild(footerRow);
    table.appendChild(tfoot);
    
    // Add table to container
    container.innerHTML = '';
    container.appendChild(table);
    
    // Function to toggle assignment
    function toggleAssignment(studentId, teacherId) {
        // Get the cell
        const cell = table.querySelector(`td[data-student-id="${studentId}"][data-teacher-id="${teacherId}"]`);
        if (!cell) return;
        
        // Check if already assigned
        const isAssigned = assignments[studentId] === teacherId;
        
        if (isAssigned) {
            // Remove assignment
            delete assignments[studentId];
            cell.classList.remove('assigned-cell');
            cell.querySelector('.flex')?.remove();
        } else {
            // First, clear any existing assignment for this student
            const oldTeacherId = assignments[studentId];
            if (oldTeacherId) {
                const oldCell = table.querySelector(`td[data-student-id="${studentId}"][data-teacher-id="${oldTeacherId}"]`);
                if (oldCell) {
                    oldCell.classList.remove('assigned-cell');
                    oldCell.querySelector('.flex')?.remove();
                }
            }
            
            // Add assignment
            assignments[studentId] = teacherId;
            cell.classList.add('assigned-cell');
            
            // Add checkmark
            const checkmarkDiv = document.createElement('div');
            checkmarkDiv.className = 'flex items-center justify-center';
            checkmarkDiv.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            `;
            cell.insertBefore(checkmarkDiv, cell.firstChild);
        }
        
        // Update footer counts
        updateCapacityIndicators();
        
        // Trigger change event
        const event = new CustomEvent('assignment-change', {
            bubbles: true,
            detail: { assignments, studentId, teacherId, action: isAssigned ? 'remove' : 'add' }
        });
        table.dispatchEvent(event);
    }
    
    // Function to update capacity indicators
    function updateCapacityIndicators() {
        // Count assignments per teacher
        const assignmentCounts = {};
        Object.values(assignments).forEach(teacherId => {
            assignmentCounts[teacherId] = (assignmentCounts[teacherId] || 0) + 1;
        });
        
        // Update footer cells
        const footerCells = tfoot.querySelectorAll('th:not(:first-child)');
        footerCells.forEach((cell, index) => {
            const teacher = teachers[index];
            if (teacher) {
                const teacherId = teacher.id;
                const assignedCount = assignmentCounts[teacherId] || 0;
                const capacity = teacher.capacity || 0;
                
                // Update count
                cell.textContent = capacity ? `${assignedCount} / ${capacity}` : assignedCount;
                
                // Update styling
                cell.className = 'border border-gray-200 px-4 py-2 text-center text-xs font-medium';
                if (capacity > 0) {
                    if (assignedCount > capacity) {
                        cell.classList.add('text-danger-500', 'font-bold');
                    } else if (assignedCount === capacity) {
                        cell.classList.add('text-success-500', 'font-bold');
                    }
                }
            }
        });
    }
    
    // Return an API for updating the matrix
    return {
        getAssignments() {
            return {...assignments};
        },
        setAssignments(newAssignments) {
            // Reset all assignments
            const cells = table.querySelectorAll('td.assigned-cell');
            cells.forEach(cell => {
                cell.classList.remove('assigned-cell');
                cell.querySelector('.flex')?.remove();
            });
            
            // Apply new assignments
            Object.entries(newAssignments).forEach(([studentId, teacherId]) => {
                const cell = table.querySelector(`td[data-student-id="${studentId}"][data-teacher-id="${teacherId}"]`);
                if (cell) {
                    cell.classList.add('assigned-cell');
                    
                    // Add checkmark
                    const checkmarkDiv = document.createElement('div');
                    checkmarkDiv.className = 'flex items-center justify-center';
                    checkmarkDiv.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    `;
                    cell.insertBefore(checkmarkDiv, cell.firstChild);
                }
            });
            
            // Update assignments object
            Object.assign(assignments, newAssignments);
            
            // Update capacity indicators
            updateCapacityIndicators();
            
            // Trigger change event
            const event = new CustomEvent('assignments-updated', {
                bubbles: true,
                detail: { assignments }
            });
            table.dispatchEvent(event);
        },
        updateWeights(newWeights) {
            // Update cell background colors based on new weights
            Object.entries(newWeights).forEach(([studentId, teacherWeights]) => {
                Object.entries(teacherWeights).forEach(([teacherId, weight]) => {
                    const cell = table.querySelector(`td[data-student-id="${studentId}"][data-teacher-id="${teacherId}"]`);
                    if (cell) {
                        // Remove all background color classes
                        cell.classList.remove('bg-success-100', 'bg-success-50', 'bg-warning-50', 'bg-danger-50', 'bg-danger-100');
                        
                        // Add new color class
                        cell.classList.add(getCompatibilityColorClass(weight));
                        
                        // Update weight text
                        cell.dataset.weight = weight;
                        const weightDiv = cell.querySelector('.text-xs');
                        if (weightDiv) {
                            weightDiv.textContent = `${Math.round(weight * 100)}%`;
                        }
                    }
                });
            });
            
            // Update weights object
            Object.assign(weights, newWeights);
        }
    };
}

/**
 * Calculate assignment statistics
 * 
 * @param {Object} assignments - Assignment mapping (student ID to teacher ID)
 * @param {Array} students - Array of student objects
 * @param {Array} teachers - Array of teacher objects with capacity property
 * @param {Object} weights - Object mapping student IDs to objects mapping teacher IDs to compatibility weights
 * @returns {Object} Statistics about the assignment
 */
export function calculateMatrixStats(assignments, students, teachers, weights) {
    // Count assigned students
    const assignedCount = Object.keys(assignments).length;
    const unassignedCount = students.length - assignedCount;
    const assignmentRate = students.length > 0 ? assignedCount / students.length : 0;
    
    // Calculate average compatibility score
    let totalScore = 0;
    Object.entries(assignments).forEach(([studentId, teacherId]) => {
        totalScore += (weights[studentId]?.[teacherId] || 0);
    });
    const averageScore = assignedCount > 0 ? totalScore / assignedCount : 0;
    
    // Calculate teacher loads
    const teacherLoads = {};
    teachers.forEach(teacher => {
        teacherLoads[teacher.id] = 0;
    });
    
    Object.values(assignments).forEach(teacherId => {
        teacherLoads[teacherId] = (teacherLoads[teacherId] || 0) + 1;
    });
    
    // Calculate capacity metrics
    const overCapacityTeachers = teachers.filter(teacher => {
        const capacity = teacher.capacity || 1;
        const load = teacherLoads[teacher.id] || 0;
        return load > capacity;
    }).length;
    
    // Calculate balance score
    const nonEmptyTeachers = teachers.filter(teacher => (teacherLoads[teacher.id] || 0) > 0).length;
    const perfectBalance = nonEmptyTeachers > 0 ? assignedCount / nonEmptyTeachers : 0;
    let balanceScore = 0;
    
    if (nonEmptyTeachers > 0) {
        const squaredDifferences = teachers.reduce((sum, teacher) => {
            const load = teacherLoads[teacher.id] || 0;
            if (load === 0) return sum;
            return sum + Math.pow(load - perfectBalance, 2);
        }, 0);
        
        const standardDeviation = Math.sqrt(squaredDifferences / nonEmptyTeachers);
        balanceScore = 1 - Math.min(1, standardDeviation / perfectBalance);
    }
    
    return {
        assignedCount,
        unassignedCount,
        assignmentRate,
        averageScore,
        teacherLoads,
        overCapacityTeachers,
        balanceScore: Math.max(0, balanceScore)
    };
}