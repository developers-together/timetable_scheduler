import { useState, useEffect } from "react";

export interface TimetableData {
  success: boolean;
  message: string;
  execution_time?: string;
  statistics?: {
    backtracks: number;
    consistency_checks: number;
    forward_checks: number;
  };
  assignments: number;
  data: {
    [courseId: string]: {
      [type: string]: {
        slot: string | string[];
        room_id: string;
        instructor_id: string | null;
        faculty?: string;
        year?: number;
        semester?: number;
        group?: string;
        section?: string;
        course_name?: string;
      };
    };
  };
}

export enum ComputationStatus {
  IDLE = "idle",
  COMPUTING = "computing",
  COMPLETED = "completed",
  ERROR = "error",
}

/**
 * Hook for timetable data management.
 * @param initialData - Optional initial data from Inertia props (from TimetableController::show)
 */
export function useTimetable(initialData?: TimetableData | null) {
  const [timetableData, setTimetableData] = useState<TimetableData | null>(initialData ?? null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [computationStatus, setComputationStatus] = useState<ComputationStatus>(
    initialData ? ComputationStatus.COMPLETED : ComputationStatus.IDLE
  );
  const [lastComputedAt, setLastComputedAt] = useState<Date | null>(null);

  // If initialData is provided (from Inertia), use it and cache it
  useEffect(() => {
    if (initialData) {
      setTimetableData(initialData);
      setComputationStatus(ComputationStatus.COMPLETED);
      // Cache the data
      localStorage.setItem("timetableData", JSON.stringify(initialData));
      const now = new Date();
      localStorage.setItem("timetableLastComputed", now.toISOString());
      setLastComputedAt(now);
    } else {
      // No initial data - try to load from cache
      const cached = localStorage.getItem("timetableData");
      const ts = localStorage.getItem("timetableLastComputed");
      if (cached) {
        try {
          const parsed = JSON.parse(cached);
          setTimetableData(parsed);
          if (ts) setLastComputedAt(new Date(ts));
          setComputationStatus(ComputationStatus.COMPLETED);
        } catch {
          // Cache corrupted, mark as idle (no automatic fetch to non-existent endpoint)
          setComputationStatus(ComputationStatus.IDLE);
        }
      }
    }
  }, [initialData]);

  // Save to localStorage when data changes
  useEffect(() => {
    if (timetableData && computationStatus === ComputationStatus.COMPLETED && !initialData) {
      localStorage.setItem("timetableData", JSON.stringify(timetableData));
      const now = new Date();
      localStorage.setItem("timetableLastComputed", now.toISOString());
      setLastComputedAt(now);
    }
  }, [timetableData, computationStatus, initialData]);

  // Placeholder for manual refresh if needed in the future
  const fetchTimetable = async () => {
    // This function is kept for API compatibility but does nothing
    // since we rely on Inertia props from the backend
    console.warn("fetchTimetable called but data should come from Inertia props");
  };

  const generateTimetable = async (_params?: Record<string, string | number | undefined>) => {
    // This function is kept for API compatibility but does nothing
    // since timetable generation happens via the /cspgenerate endpoint called from waiting page
    console.warn("generateTimetable called but generation happens via /cspgenerate");
  };

  // Optional helper if a page wants local formatting:
  const displayTime = (range: string, fmt: "12" | "24" = "24") => {
    const [a, b] = range.split("-");
    if (fmt === "24") return `${a}-${b}`;
    const to12 = (t: string) => {
      const [H, M] = t.split(":").map(Number);
      const h = ((H + 11) % 12) + 1;
      const ap = H < 12 ? "AM" : "PM";
      return `${h}:${M.toString().padStart(2, "0")} ${ap}`;
    };
    return `${to12(a)} – ${to12(b)}`;
  };

  return {
    timetableData,
    loading,
    error,
    computationStatus,
    lastComputedAt,
    fetchTimetable,
    generateTimetable,
    displayTime,
  };
}
