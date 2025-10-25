import { useState, useEffect } from "react";
import axios from "axios";

export interface TimetableData {
  success: boolean;
  message: string;
  execution_time: string;
  statistics: {
    backtracks: number;
    consistency_checks: number;
    forward_checks: number;
  };
  assignments: number;
  data: {
    [courseId: string]: {
      [type: string]: {
        slot: string;
        room_id: string;
        instructor_id: string | null;
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

export function useTimetable() {
  const [timetableData, setTimetableData] = useState<TimetableData | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [computationStatus, setComputationStatus] = useState<ComputationStatus>(ComputationStatus.IDLE);
  const [lastComputedAt, setLastComputedAt] = useState<Date | null>(null);

  useEffect(() => {
    const cached = localStorage.getItem("timetableData");
    const ts = localStorage.getItem("timetableLastComputed");
    if (cached) {
      try {
        const parsed = JSON.parse(cached);
        setTimetableData(parsed);
        if (ts) setLastComputedAt(new Date(ts));
        setComputationStatus(ComputationStatus.COMPLETED);
      } catch {
        fetchTimetable();
      }
    } else {
      fetchTimetable();
    }
  }, []);

  useEffect(() => {
    if (timetableData && computationStatus === ComputationStatus.COMPLETED) {
      localStorage.setItem("timetableData", JSON.stringify(timetableData));
      const now = new Date();
      localStorage.setItem("timetableLastComputed", now.toISOString());
      setLastComputedAt(now);
    }
  }, [timetableData, computationStatus]);

  const fetchTimetable = async () => {
    setLoading(true);
    setError(null);
    setComputationStatus(ComputationStatus.COMPUTING);

    try {
      const res = await axios.get("/getAssignment");
      const data =
        res.data?.data !== undefined
          ? res.data
          : {
              success: true,
              message: "OK",
              execution_time: "-",
              statistics: { backtracks: 0, consistency_checks: 0, forward_checks: 0 },
              assignments: 0,
              data: res.data || {},
            };
      setTimetableData(data);
      setComputationStatus(ComputationStatus.COMPLETED);
    } catch (e) {
      setError("Failed to load timetable data. Please try again.");
      setComputationStatus(ComputationStatus.ERROR);
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const generateTimetable = async (params?: Record<string, string | number | undefined>) => {
    setLoading(true);
    setError(null);
    setComputationStatus(ComputationStatus.COMPUTING);

    try {
      const res = await axios.get("/generate-timetable", { params });
      setTimetableData(res.data);
      setComputationStatus(ComputationStatus.COMPLETED);
    } catch (e) {
      setError("Failed to generate timetable. Please try again.");
      setComputationStatus(ComputationStatus.ERROR);
      console.error(e);
    } finally {
      setLoading(false);
    }
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
