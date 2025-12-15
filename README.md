# Timetable Scheduler

A sophisticated **Constraint Satisfaction Problem (CSP)** based university timetable scheduling system built with Laravel. This application automatically generates conflict-free timetables for courses, considering multiple constraints such as room availability, instructor schedules, and student group conflicts.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Installation](#installation)
- [Data Import](#data-import)
- [Usage](#usage)
- [CSP Algorithm Details](#csp-algorithm-details)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

---

## Overview

This timetable scheduler solves the university course scheduling problem using a CSP approach. Given a set of courses, instructors, rooms, and time slots, the system finds a valid assignment that satisfies all hard constraints while optimizing for soft preferences.

### The Scheduling Problem

The scheduler must assign each course session (lecture, lab, tutorial) to:
- A **room** with appropriate capacity and type
- A **time slot** (day + time period)
- An **instructor** (for lectures)

While ensuring:
- No room is double-booked
- No instructor teaches two classes simultaneously
- Students in the same group don't have overlapping classes
- Room types match course requirements (e.g., labs need computer rooms)

---

## Features

### Core Features
- ✅ **Automatic timetable generation** using CSP backtracking with forward checking
- ✅ **Parallel solving** - Multiple worker processes try different search paths simultaneously
- ✅ **Constraint propagation** using AC-3 arc consistency algorithm
- ✅ **Smart variable ordering** using MRV (Minimum Remaining Values) heuristic
- ✅ **Half-slot support** - Tutorials can use half time slots for efficient room utilization
- ✅ **Quality evaluation** - Scores generated timetables based on multiple criteria

### Constraint Handling
- **Hard Constraints** (must be satisfied):
  - Room conflicts (one class per room per time slot)
  - Instructor conflicts (one instructor per time slot)
  - Student group conflicts (no overlapping classes for same faculty/year/group)
  
- **Soft Constraints** (optimized for):
  - Preferred time slots (10:45 AM - 2:00 PM)
  - Balanced instructor workload
  - Efficient room utilization
  - Half-slot pairing efficiency

### Data Management
- ✅ Excel-based data import for courses, rooms, instructors, and time slots
- ✅ Automatic table truncation before import for clean data loads
- ✅ Unique constraint enforcement to prevent duplicate course components

---

## Architecture

The system is built using a modular provider-based architecture:

```
app/Providers/CSP/
├── CspSchedulerProvider.php      # Main orchestrator
├── VariableManagerProvider.php   # Creates variables, domains, and neighbors
├── ConstraintSolverProvider.php  # CSP solving with backtracking + AC-3
├── ParallelSolverProvider.php    # Multi-process parallel solving
├── EvaluatorProvider.php         # Schedule quality scoring
└── DatabaseSaverProvider.php     # Saves results with validation
```

### Component Roles

#### 1. CspSchedulerProvider
The main entry point that orchestrates the entire scheduling process:
- Validates inputs
- Chooses between sequential or parallel solving
- Saves results to database

#### 2. VariableManagerProvider
Transforms the scheduling problem into CSP form:
- **Variables**: Each course session (lecture/lab/tutorial for each group/section)
- **Domains**: Possible room + time slot combinations for each variable
- **Neighbors**: Constraint graph edges between conflicting variables

Key optimizations:
- Rotating room distribution to balance room usage
- Pre-computed neighbor relationships based on potential conflicts
- Instructor workload balancing during variable creation

#### 3. ConstraintSolverProvider
Implements the CSP solving algorithm:
- **AC-3**: Reduces domains by enforcing arc consistency
- **Backtracking with Forward Checking**: Assigns values and prunes future domains
- **MRV + Degree Heuristics**: Smart variable/value ordering

#### 4. ParallelSolverProvider
Launches multiple worker processes with different random seeds:
- Uses file-based cache for inter-process communication
- First solution found wins
- Automatic cleanup of worker processes

#### 5. EvaluatorProvider
Scores generated timetables based on:
- Time preference adherence
- Instructor workload balance
- Room utilization efficiency
- Half-slot pairing efficiency

#### 6. DatabaseSaverProvider
Saves the solution with comprehensive validation:
- Pre-save conflict detection
- Transaction-based atomic saves
- Post-save validation queries

---

## Installation

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+
- MariaDB/MySQL

### Setup

```bash
# Clone the repository
git clone <repository-url>
cd timetable_scheduler

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Configure database in .env
# DB_CONNECTION=mariadb
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=timetable_scheduler
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
composer run dev
```

---

## Data Import

### Required Excel Files
Place the following files in `storage/app/`:

1. **courses.xlsx** - Course definitions with components
2. **rooms.xlsx** - Room details (code, type, capacity)
3. **instructors.xlsx** - Instructor list with roles
4. **slots.xlsx** - Time slots (day, start, end times)
5. **input.xlsx** - Required courses for the current term

### Import Order

1. **Base Data**: Visit `/dbload` to import courses, rooms, instructors, and time slots
2. **Input Data**: Visit `/dbinput` to import required courses for scheduling

The import functions automatically truncate tables before importing to ensure clean data.

---

## Usage

### Generate Timetable

1. **Via Web**: Navigate to `/cspgenerate` to trigger timetable generation
2. **Response**: Returns JSON with success status, assignment details, and statistics

### View Generated Timetable

- **JSON Format**: `/timetablejson` - Returns structured JSON organized by Faculty → Year → Day → Time
- **Web View**: `/timetable` - Renders the timetable in an interactive UI

### Example Response Structure

```json
{
  "CSIT": {
    "1": {
      "Mon": {
        "10:45": [
          {
            "course_code": "CS101",
            "course_name": "Introduction to Programming",
            "type": "Lecture",
            "room_id": "A-101",
            "instructor_id": "Dr. Smith",
            "group": 1
          }
        ]
      }
    }
  }
}
```

---

## CSP Algorithm Details

### 1. Variable Creation
Each required course generates multiple variables based on:
- **Lectures**: One per student group (capacity / 90)
- **Labs/Tutorials**: One per section (capacity / 30)

### 2. Domain Generation
Each variable's domain consists of valid (room, time_slot, slot_type) combinations:
- Room type must match course component
- Lectures and Labs use "full" slots
- Tutorials can use "first_half" or "second_half" slots

**Optimization**: Each variable gets only 10 rooms with 40% overlap using circular distribution to reduce memory and improve solving speed.

### 3. Constraint Graph (Neighbors)
Variables are connected if they can potentially conflict:
- Same instructor
- Overlapping room sets
- Same student group (Faculty + Year + Group)

### 4. AC-3 Arc Consistency
Before backtracking, reduces domains by removing values that have no support:
```
For each arc (Xi, Xj):
  For each value in Xi's domain:
    If no value in Xj's domain satisfies the constraint:
      Remove this value from Xi's domain
```

### 5. Backtracking with Forward Checking
```
function backtrack(assignment):
  if assignment is complete: return assignment
  
  var = selectUnassignedVariable()  // MRV heuristic
  
  for value in var.domain:
    if isConsistent(var, value, assignment):
      assignment[var] = value
      if forwardCheck(var, value):  // Prune neighbor domains
        result = backtrack(assignment)
        if result: return result
      unassign(var)
  
  return null
```

### 6. Consistency Rules
Two assignments conflict if they share the same time slot AND:
- Same room (both "full" or same half)
- Same instructor
- Same student group (Faculty + Year + Group)

---

## Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `courses` | Course definitions (code, name) |
| `course_components` | Course components (Lecture/Lab/Tutorial) with unique constraint on (course_id, type) |
| `instructors` | Instructor list |
| `instructor_roles` | Roles (prof, ta, lab_ta) |
| `course_instructor` | Many-to-many relationship |
| `rooms` | Room details (code, type, capacity) |
| `time_slots` | Time periods (day, start, end) |
| `required_courses` | Courses to schedule (capacity, level, term, faculty) |
| `schedules` | Generated timetable entries |

### Key Constraints
- `course_components`: Unique on (course_id, type) - prevents duplicate component types
- `schedules`: Unique on (room_id, time_slot_id, slot) - prevents room double-booking
- `schedules`: Unique on (instructor_id, time_slot_id, slot) - prevents instructor conflicts

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dbload` | Import base data (courses, rooms, instructors, time slots) |
| GET | `/dbinput` | Import required courses |
| GET | `/cspgenerate` | Generate new timetable |
| GET | `/timetable` | View timetable (Web UI) |
| GET | `/timetablejson` | Get timetable as JSON |

---

## Configuration

### CSP Solver Settings

In `ConstraintSolverProvider.php`:
```php
private int $maxBacktrackCalls = 1000000;    // Max backtrack iterations
private int $maxConstraintChecks = 20000000; // Max constraint checks
```

### Parallel Solver Settings

In `ParallelSolverProvider.php`:
```php
private int $numWorkers = 8;        // Number of parallel processes
private int $timeoutSeconds = 120;  // Max solving time
```

### Domain Size Control

In `VariableManagerProvider.php`:
```php
private const ROOMS_PER_VARIABLE = 10;       // Rooms per variable domain
private const ROOM_OVERLAP_PERCENTAGE = 40;  // Overlap between consecutive variables
```

---

## Troubleshooting

### "No solution found"
- Check if the problem is over-constrained
- Verify enough rooms exist for each course type
- Ensure time slots are sufficient for all courses
- Check for duplicate course components (fixed by unique constraint)

### "Max backtrack calls exceeded"
- Reduce problem size or increase `maxBacktrackCalls`
- Enable parallel solving for faster search
- Check constraint graph density in logs

### "Communication link failure" during parallel solving
- The system now uses file cache instead of database cache
- Ensure `storage/framework/cache/data` is writable

### Import fails with duplicate key error
- The unique constraint prevents duplicate course components
- Check your Excel data for duplicate component rows

---

## License

[Add your license here]

---

## Contributing

[Add contribution guidelines here]
