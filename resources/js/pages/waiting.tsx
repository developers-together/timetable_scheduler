import React, { useEffect, useState } from "react";
import { Head, router } from "@inertiajs/react";
import axios from "axios";

const PHRASES = [
  "Parsing input and validating fields…",
  "Checking rooms, instructors, and slots…",
  "Building constraints and searching solution space…",
  "Balancing conflicts and backtracks…",
  "Finalizing your timetable…",
];

export default function WaitingPage() {
  const [phase, setPhase] = useState(0);
  const [progress, setProgress] = useState(8);

  useEffect(() => {
    const t1 = setInterval(() => setPhase((p) => (p + 1) % PHRASES.length), 1400);
    const t2 = setInterval(() => setProgress((p) => (p < 92 ? p + Math.random() * 6 : p)), 260);

    (async () => {
      try {
        await axios.get("/generate-timetable"); // backend computation
      } catch (e) {
        console.error(e);
      } finally {
        router.visit("/timetable");
      }
    })();

    return () => { clearInterval(t1); clearInterval(t2); };
  }, []);

  return (
    <>
      <Head title="Preparing timetable" />
      <div className="fixed inset-0 grid place-items-center bg-white">
        <div className="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-8">
          <div className="mb-5 flex items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
          </div>
          <h2 className="text-center text-xl font-semibold text-gray-900">Preparing your timetable</h2>
          <p className="mt-1 text-center text-sm text-gray-600">{PHRASES[phase]}</p>
          <div className="mt-6">
            <div className="h-2 w-full rounded-full bg-gray-200">
              <div className="h-2 rounded-full bg-blue-600 transition-all" style={{ width: `${progress}%` }} />
            </div>
            <p className="mt-2 text-center text-xs text-gray-500">This won’t take long.</p>
          </div>
        </div>
      </div>
    </>
  );
}
