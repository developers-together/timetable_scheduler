You are an expert AI software engineer specializing in **Constraint Satisfaction Problems (CSP)**, **Artificial Intelligence algorithms**, and **university timetable generation systems**.  
Your task is to design and describe a **complete CSP-based scheduling algorithm** for a **University Timetable Scheduling System**, giving full technical context and algorithmic detail.

---

## 🧠 Project Context

This project is a **University Timetable Scheduling System** built to automatically generate course timetables that respect both *hard* and *soft* constraints.  
The system is developed in **Laravel (PHP)** with a **MySQL** database backend. The algorithm itself is implemented as a **CSP solver** with **backtracking**, **heuristics**, and **arc consistency** enforcement.

The scheduling system handles the following entities:

- **Courses**: Each course may include multiple sessions (lectures, tutorials, labs).
- **Instructors**: Professors and teaching assistants (TAs), each with specific qualified course types.
- **Rooms**: Lecture halls, tutorial rooms, and labs with capacities and types.
- **Time Slots**: Defined across weekdays with fixed start and end times.
- **Timetable**: The resulting assignment of courses to instructors, rooms, and time slots.

---

## ⚙️ Technology Stack

- **Framework:** Laravel (PHP 8.3+)
- **Database:** MySQL
- **Algorithm:** CSP (Constraint Satisfaction Problem) using:
  - **Backtracking Search**
  - **Arc Consistency (AC-3)**
  - **Heuristic Optimization** (MRV, Degree, LCV, Forward Checking)

---

## 🎯 Problem Definition

The **goal** of the system is to assign each course session to:
- an **instructor**,  
- a **room**,  
- and a **time slot**,  

while ensuring that all **hard constraints** are strictly satisfied, and **soft constraints** are optimized as much as possible.

This is modeled as a **CSP**:
- **Variables:** Each session (e.g., `CS101 Lecture`, `CS101 Lab`, `CS101 Tutorial`)
- **Domains:** All possible `(day, time_slot, room, instructor)` combinations that meet type and qualification requirements.
- **Constraints:** Define which combinations are valid.

---

## 📏 Hard Constraints (must always hold)

1. No instructor can teach multiple sessions at the same time.
2. No room can host more than one session at the same time.
3. Each course section must have all required session types (Lecture, Lab, Tutorial).
4. The room type must match the session type:
   - Lecture → large room (capacity > 25 or NULL)
   - Tutorial → small room (capacity ≤ 25)
   - Lab → room_type contains “Lab”.
5. Each session must be assigned to a qualified instructor.

---

## 🌿 Soft Constraints (optimization goals)

1. Minimize gaps in student schedules.
2. Avoid early morning or late afternoon sessions.
3. Minimize consecutive sessions in distant rooms for the same instructor.
4. Distribute classes evenly across the week.
5. Maximize instructor satisfaction (e.g., preferred slots).

These constraints guide optimization but do not invalidate solutions if slightly violated.

---

## 🧩 Algorithm Overview — CSP with Backtracking, AC-3, and Heuristics

### Step 1 — Initialization
- Load all **courses**, **rooms**, and **instructors** from the database.
- For each course session, create a **variable**.
- Build an initial **domain** of all possible `(day, slot, room, instructor)` combinations satisfying basic type and qualification filters.

### Step 2 — Arc Consistency (AC-3)
Apply **Arc Consistency (AC-3)** before search to reduce the domain space.

**AC-3 process:**
1. Initialize a queue of all arcs `(Xi, Xj)` where `Xi` and `Xj` are related variables (e.g., sessions sharing an instructor or room).
2. For each arc, remove domain values from `Xi` that are inconsistent with all values in `Xj`.
3. If `Xi`’s domain changes, re-enqueue all its neighboring arcs.
4. Continue until the queue is empty or a domain becomes empty (infeasible).

This reduces the search space by eliminating impossible assignments early.

### Step 3 — Backtracking Search with Heuristics
After AC-3 pruning, apply **recursive backtracking search** enhanced with heuristics:

- **MRV (Minimum Remaining Values):** Select the variable with the fewest remaining options first.
- **Degree Heuristic:** Break MRV ties by selecting the variable involved in the most constraints.
- **LCV (Least Constraining Value):** Choose the value that leaves the most freedom for other variables.
- **Forward Checking:** After assigning a value, eliminate inconsistent domain values for neighboring unassigned variables.

### Step 4 — Recursive Search
1. Select the next variable (using MRV + Degree).
2. Iterate through domain values (ordered by LCV).
3. For each value:
   - Check consistency with current partial assignment.
   - If consistent:
     - Apply Forward Checking or AC-3 dynamically.
     - Recurse.
   - If a domain empties → backtrack.
4. Continue until all variables are assigned or failure occurs.

### Step 5 — Optimization and Evaluation
Once a valid timetable is found:
- Evaluate it according to **soft constraints**.
- Optionally rerun or adjust weights to improve the solution.
- Record the best (highest-scoring) configuration.

---

## 🧮 Example Pseudocode

```python
def csp_schedule(variables, domains, constraints):
    if all_assigned(variables):
        return assignment

    var = select_unassigned_variable(variables, domains, heuristic='MRV+Degree')
    for value in order_domain_values(var, domains, heuristic='LCV'):
        if consistent(var, value, constraints):
            assign(var, value)
            inferences = forward_check(var, value, domains)
            if inferences != FAILURE:
                result = csp_schedule(variables, domains, constraints)
                if result != FAILURE:
                    return result
            undo_assignment(var, value, inferences)
    return FAILURE

