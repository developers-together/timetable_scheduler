# Timetable Scheduler: Algorithm & User Manual

> **Note to User**: You can print this document by exporting it to PDF from your editor, or by copying the content into Microsoft Word/Google Docs.

---

## Part 1: The Algorithm Explained

This project solves the **University Course Timetabling Problem**, a classic example of a **Constraint Satisfaction Problem (CSP)**. The goal is to assign convenient times and rooms to classes without violating any hard rules (constraints).

### 1. The Core Concept: CSP
We model the problem as:
*   **Variables ($X$)**: The events to be scheduled (e.g., "CS101 Lecture", "Math202 Lab").
*   **Domains ($D$)**: The possible values for each variable. A "value" is a combination of a **Time Slot** and a **Room**.
*   **Constraints ($C$)**: The rules that define valid assignments (e.g., "Instructor Smith cannot be in two rooms at once").

### 2. Search Strategy: Backtracking with Forward Checking
Instead of trying every random combination (which would take millions of years), we use **Backtracking**:
1.  **Pick a variable** (a class) that hasn't been scheduled yet.
2.  **Pick a valid value** (time/room) for it.
3.  **Check consistency**: Does this move violate any constraints with already scheduled classes?
4.  **Forward Check**: Look ahead! Remove this time/room from the valid options of all related future classes. If any future class runs out of options, we stop immediately (**Pruning**) and backtrack to try a different value.

### 3. Heuristics (Making it Smart)
To speed up the search, we don't pick variables or values randomly. We use heuristics:

#### A. Minimum Remaining Values (MRV) - "Fail First"
*   **Logic**: "Which class is the hardest to schedule right now?"
*   **Action**: We always pick the class with the **fewest** remaining legal time slots.
*   **Why**: If a class only has 1 slot left, we better schedule it *now* before that slot gets taken. If it's impossible, we want to find out immediately, not after scheduling 100 other easy classes.

#### B. Degree Heuristic - "Tie Breaker"
*   **Logic**: "If two classes are equally hard to schedule, which one affects the most *other* classes?"
*   **Action**: Pick the class that shares instructors or students with the most other classes.
*   **Why**: Scheduling the most "connected" class first settles the biggest constraints early.

### 4. Advanced Optimization: AC-3
Before we even start scheduling, we run the **Arc Consistency Algorithm #3 (AC-3)**.
*   **What it does**: It looks at pairs of classes and removes options that will *never* work.
*   **Example**: If Class A must be at the same time as Class B, but Class B can only be on Monday, we remove "Tuesday-Friday" from Class A's options immediately.
*   **Result**: This massive "cleanup" happens before the heavy lifting, making the search space much smaller.

### 5. Parallelization (The "Secret Weapon")
Scheduling is NP-complete, meaning sometimes you just get unlucky and go down a wrong path for a long time.
*   **Solution**: We run **12 solvers** simultaneously in standard background PHP processes.
*   **Randomized Restarts**: Each solver uses a different "Seed". They explore the solution space from different angles.
*   **Race to Finish**: The moment *one* solver finds a solution, it saves it to the database and signals the others to stop. This linearizes the time complexity for finding a solution.

---

## Part 2: How to Open and Run the Project

Follow these steps to set up the project on a new machine (Mac/Linux/Windows).

### Prerequisites
Ensure you have the following installed:
1.  **PHP 8.2+**
2.  **Node.js 20+** & **NPM**
3.  **Composer** (PHP Dependency Manager)
4.  **SQLite** (Usually built-in to PHP)

### Step 1: Installation
Open your terminal in the project folder and run the automated setup script. This installs PHP packages, Node packages, creates the `.env` file, and sets up the database.

```bash
composer run setup
```

*If the command above is not available or fails, run these manually:*
```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
npm install
npm run build
```

### Step 2: Running the Application
To start the development server (which runs both Laravel and Vite):

```bash
composer run dev
```

*Alternative manual start (requires two terminal tabs):*
*   **Terminal 1 (Backend)**: `php artisan serve`
*   **Terminal 2 (Frontend)**: `npm run dev`

### Step 3: Accessing the Project
1.  Open your browser and go to: `http://localhost:8000`
2.  **Generate Page**: Upload your Excel file containing course requirements.
3.  **Waiting Page**: The system will start the 12 parallel workers. Wait for completion (usually < 10 seconds).
4.  **Timetable Page**: View the generated schedule.

### Troubleshooting
*   **"Database not found"**: Ensure `database/database.sqlite` exists. Run `touch database/database.sqlite` and then `php artisan migrate`.
*   **"Timeout"**: If generating takes too long, check your PHP `max_execution_time` in `php.ini`.
