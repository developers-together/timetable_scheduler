# Timetable Scheduler: Intelligent Systems Presentation (5-Minute Version)

> **Course Context**: Intelligent Systems / AI
> **Constraint**: 5 Minutes MAX (approx. 45-60 seconds per slide)
> **Focus**: The "Intelligence" (Heuristics & Search Strategy)

---

## Slide 1: Introduction & Problem Definition (1 Minute)
*   **The Problem**: University Course Scheduling is **NP-Complete**.
*   **The Model**: We define it as a **Constraint Satisfaction Problem (CSP)**.
    *   **Variables ($X$)**: Course Sections (e.g., "AI Lecture", "Math Lab").
    *   **Domains ($D$)**: All possible Room $\times$ TimeSlot pairs ($|D| \approx 1000$).
    *   **Constraints ($C$)**:
        *   $Instructor(x) \neq Instructor(y)$ at Time $t$.
        *   $Room(x) \neq Room(y)$ at Time $t$.

## Slide 2: The Core Algorithm: Backtracking with Propagation (1 Minute)
*   **Why not Brute Force?**: Search space is $|D|^N$. Impossible to search blindly.
*   **Our Solution**: **Backtracking Search** augmented with **Constraint Propagation**.
*   **Technique 1: AC-3 (Preprocessing)**:
    *   We aggressively prune valid domains *before* starting the search.
    *   *Effect*: Removes values that can *never* be part of a solution, shrinking the search tree.
*   **Technique 2: Forward Checking**:
    *   During search, we propagate the implications of every assignment immediately.

## Slide 3: Heuristics - Making the Search "Intelligent" (1 Minute)
*   *How does the agent decide what to do next?*
*   **Variable Ordering (MRV - Minimum Remaining Values)**:
    *   **Strategy**: "Fail First". Pick the class with the fewest legal slots left.
    *   **Why**: Detecting failure early prevents wasting time deep in the tree.
*   **Tie-Breaking (Degree Heuristic)**:
    *   **Strategy**: Pick the variable connected to the most other variables.
    *   **Why**: Solving the "most difficult" nodes first simplifies the rest of the graph.

## Slide 4: Overcoming Local Optima (1 Minute)
*   **The Challenge**: Backtracking can get stuck in "deep" sub-trees that lead nowhere (Thrashing).
*   **The Innovation**: **Parallel Randomized Restarts**.
*   **Implementation**:
    *   Launch **12 Parallel Agents**.
    *   Each agent uses a different **Random Seed** for variable selection ties.
    *   **Result**: Linearly increases the probability of finding a solution in $< 10$ seconds.

## Slide 5: Results & Conclusion (1 Minute)
*   **Performance**:
    *   Solves high-density graphs in **seconds** (vs hours for naive search).
    *   Accommodates 100% of Hard Constraints.
*   **Video/Demo**: *[Quick 15s clip of the "Generate" button working]*
*   **Takeaway**: Combining **Heuristics (MRV)** with **Stochastic Search** turns an unsolvable NP problem into a tractable real-time web app.
