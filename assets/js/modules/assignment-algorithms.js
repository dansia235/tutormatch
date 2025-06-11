/**
 * Assignment Algorithms Module
 * Contains client-side implementations of student-teacher assignment algorithms
 */

/**
 * Greedy Algorithm for student-teacher assignment
 * 
 * @param {Array} students - Array of student objects
 * @param {Array} teachers - Array of teacher objects with capacity property
 * @param {Object} weights - Object mapping student IDs to objects mapping teacher IDs to compatibility weights
 * @param {Object} options - Algorithm options
 * @param {Number} options.preferenceWeight - Weight for student-teacher preferences (0-1)
 * @param {Number} options.capacityWeight - Weight for teacher capacity balance (0-1)
 * @returns {Object} Assignment mapping (student ID to teacher ID)
 */
export function greedyAssignment(students, teachers, weights, options = {}) {
  const preferenceWeight = options.preferenceWeight || 0.7;
  const capacityWeight = options.capacityWeight || 0.3;
  
  // Make a copy of the inputs to avoid modifying the originals
  const studentsCopy = [...students];
  const teachersCopy = [...teachers];
  const weightsCopy = {...weights};
  
  // Initialize assignments
  const assignments = {};
  const teacherLoads = {};
  
  // Initialize teacher loads with zero
  teachersCopy.forEach(teacher => {
    teacherLoads[teacher.id] = 0;
  });
  
  // Sort students by sum of preference weights (descending)
  // Students with stronger preferences are assigned first
  studentsCopy.sort((a, b) => {
    const aSum = Object.values(weightsCopy[a.id] || {}).reduce((sum, w) => sum + w, 0);
    const bSum = Object.values(weightsCopy[b.id] || {}).reduce((sum, w) => sum + w, 0);
    return bSum - aSum;
  });
  
  // Assign each student to the best available teacher
  studentsCopy.forEach(student => {
    const studentId = student.id;
    const studentWeights = weightsCopy[studentId] || {};
    
    // Calculate combined scores for each teacher based on preference and load
    const scores = teachersCopy.map(teacher => {
      const teacherId = teacher.id;
      const preferenceScore = studentWeights[teacherId] || 0;
      
      // Calculate capacity score (higher when teacher has more remaining capacity)
      const currentLoad = teacherLoads[teacherId];
      const maxCapacity = teacher.capacity || 1;
      const capacityScore = Math.max(0, 1 - (currentLoad / maxCapacity));
      
      // Combine scores with weights
      const combinedScore = (preferenceScore * preferenceWeight) + (capacityScore * capacityWeight);
      
      return {
        teacherId,
        score: combinedScore,
        capacityScore,
        preferenceScore,
        currentLoad,
        maxCapacity
      };
    });
    
    // Sort teachers by combined score (descending)
    scores.sort((a, b) => b.score - a.score);
    
    // Assign student to the best teacher that has capacity
    for (const teacherScore of scores) {
      const teacherId = teacherScore.teacherId;
      const teacher = teachersCopy.find(t => t.id === teacherId);
      const maxCapacity = teacher.capacity || Infinity;
      
      if (teacherLoads[teacherId] < maxCapacity) {
        // Assign student to teacher
        assignments[studentId] = teacherId;
        teacherLoads[teacherId]++;
        break;
      }
    }
  });
  
  return assignments;
}

/**
 * Hungarian Algorithm for optimal assignment
 * This is a simplified implementation of the Hungarian algorithm for the assignment problem
 * 
 * @param {Array} students - Array of student objects
 * @param {Array} teachers - Array of teacher objects
 * @param {Object} weights - Object mapping student IDs to objects mapping teacher IDs to compatibility weights
 * @returns {Object} Assignment mapping (student ID to teacher ID)
 */
export function hungarianAssignment(students, teachers, weights) {
  // For simplicity, this implementation assumes:
  // - The number of students is less than or equal to the number of teachers
  // - Each teacher has a capacity of 1
  
  // If we have more students than teachers, we need to add dummy teachers
  // to make the cost matrix square
  const numStudents = students.length;
  const numTeachers = teachers.length;
  
  // Create a cost matrix for the Hungarian algorithm
  // We want to maximize weights, but Hungarian minimizes costs, so we use 1 - weight
  const costMatrix = [];
  
  students.forEach(student => {
    const studentId = student.id;
    const studentWeights = weights[studentId] || {};
    
    const row = teachers.map(teacher => {
      const teacherId = teacher.id;
      const weight = studentWeights[teacherId] || 0;
      return 1 - weight; // Convert to cost (lower is better)
    });
    
    costMatrix.push(row);
  });
  
  // Run the Hungarian algorithm
  const hungarianResult = hungarian(costMatrix);
  
  // Convert result to assignments
  const assignments = {};
  
  hungarianResult.forEach((teacherIndex, studentIndex) => {
    if (teacherIndex < numTeachers) { // Ignore dummy teachers
      const studentId = students[studentIndex].id;
      const teacherId = teachers[teacherIndex].id;
      assignments[studentId] = teacherId;
    }
  });
  
  return assignments;
}

/**
 * Hungarian algorithm implementation
 * Based on: https://en.wikipedia.org/wiki/Hungarian_algorithm
 * 
 * @param {Array<Array<number>>} costMatrix - Matrix of costs to be minimized
 * @returns {Array<number>} - Array where index i contains the column assignment for row i
 */
function hungarian(costMatrix) {
  const n = Math.max(costMatrix.length, Math.max(...costMatrix.map(row => row.length)));
  
  // Create a square matrix, padding with zeros if needed
  const costs = Array(n).fill().map((_, i) => {
    const row = costMatrix[i] || [];
    return Array(n).fill().map((_, j) => row[j] || 0);
  });
  
  // Step 1: Subtract the minimum value in each row from all elements in that row
  for (let i = 0; i < n; i++) {
    const minVal = Math.min(...costs[i]);
    for (let j = 0; j < n; j++) {
      costs[i][j] -= minVal;
    }
  }
  
  // Step 2: Subtract the minimum value in each column from all elements in that column
  for (let j = 0; j < n; j++) {
    const column = costs.map(row => row[j]);
    const minVal = Math.min(...column);
    for (let i = 0; i < n; i++) {
      costs[i][j] -= minVal;
    }
  }
  
  // Initialize results
  const assignments = Array(n).fill(-1);
  
  // Find assignments (simplified approach)
  let totalAssigned = 0;
  
  while (totalAssigned < n) {
    // Find a zero in the cost matrix
    let zeroFound = false;
    
    for (let i = 0; i < n && !zeroFound; i++) {
      if (assignments[i] !== -1) continue;
      
      for (let j = 0; j < n && !zeroFound; j++) {
        if (costs[i][j] === 0 && !assignments.includes(j)) {
          assignments[i] = j;
          totalAssigned++;
          zeroFound = true;
        }
      }
    }
    
    // If no zero was found, adjust the matrix
    if (!zeroFound) {
      // Find the minimum uncovered value
      let minVal = Infinity;
      
      for (let i = 0; i < n; i++) {
        if (assignments[i] !== -1) continue;
        
        for (let j = 0; j < n; j++) {
          if (!assignments.includes(j) && costs[i][j] < minVal) {
            minVal = costs[i][j];
          }
        }
      }
      
      // Subtract from uncovered rows, add to covered columns
      for (let i = 0; i < n; i++) {
        if (assignments[i] === -1) {
          for (let j = 0; j < n; j++) {
            costs[i][j] -= minVal;
          }
        }
      }
      
      for (let j = 0; j < n; j++) {
        if (assignments.includes(j)) {
          for (let i = 0; i < n; i++) {
            costs[i][j] += minVal;
          }
        }
      }
    }
  }
  
  return assignments;
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
export function calculateAssignmentStats(assignments, students, teachers, weights) {
  // Count assigned students
  const assignedCount = Object.keys(assignments).length;
  
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
  
  // Calculate load balance metrics
  const loads = Object.values(teacherLoads);
  const avgLoad = assignedCount / teachers.length;
  const loadDeviation = Math.sqrt(loads.reduce((sum, load) => sum + Math.pow(load - avgLoad, 2), 0) / loads.length);
  
  // Calculate capacity utilization
  let totalCapacity = 0;
  let overCapacityCount = 0;
  
  teachers.forEach(teacher => {
    const capacity = teacher.capacity || 1;
    totalCapacity += capacity;
    
    const load = teacherLoads[teacher.id] || 0;
    if (load > capacity) {
      overCapacityCount++;
    }
  });
  
  const capacityUtilization = totalCapacity > 0 ? assignedCount / totalCapacity : 0;
  
  return {
    totalStudents: students.length,
    assignedCount,
    unassignedCount: students.length - assignedCount,
    assignmentRate: students.length > 0 ? assignedCount / students.length : 0,
    averageScore,
    teacherLoads,
    loadDeviation,
    capacityUtilization,
    overCapacityCount,
    overCapacityTeachers: overCapacityCount
  };
}

/**
 * Run an assignment algorithm
 * 
 * @param {String} algorithm - Algorithm name ('greedy' or 'hungarian')
 * @param {Array} students - Array of student objects
 * @param {Array} teachers - Array of teacher objects
 * @param {Object} weights - Compatibility weights
 * @param {Object} options - Algorithm options
 * @returns {Object} The assignment result and statistics
 */
export function runAssignment(algorithm, students, teachers, weights, options = {}) {
  // Validate inputs
  if (!students || !Array.isArray(students) || students.length === 0) {
    throw new Error('Students must be a non-empty array');
  }
  
  if (!teachers || !Array.isArray(teachers) || teachers.length === 0) {
    throw new Error('Teachers must be a non-empty array');
  }
  
  if (!weights || typeof weights !== 'object') {
    throw new Error('Weights must be an object');
  }
  
  // Run the appropriate algorithm
  let assignments;
  
  switch (algorithm.toLowerCase()) {
    case 'hungarian':
      assignments = hungarianAssignment(students, teachers, weights);
      break;
    case 'greedy':
    default:
      assignments = greedyAssignment(students, teachers, weights, options);
      break;
  }
  
  // Calculate statistics
  const stats = calculateAssignmentStats(assignments, students, teachers, weights);
  
  return {
    assignments,
    stats
  };
}