import React, { useEffect, useState } from "react";

const PHRASES = [
  "gathering your courses",
  "checking room availability",
  "matching instructors to slots",
  "balancing labs and tutorials",
  "finalizing conflicts and breaks",
  "building a clean timetable layout",
];

interface WaitingPageProps {
  title?: string;
}

const WaitingPage: React.FC<WaitingPageProps> = ({ title = "Preparing your timetable" }) => {
  const [phase, setPhase] = useState(0);
  const [progress, setProgress] = useState(6);

  useEffect(() => {
    const a = setInterval(() => setPhase((p) => (p + 1) % PHRASES.length), 1400);
    const b = setInterval(() => setProgress((p) => (p < 94 ? p + Math.random() * 5 : p)), 280);
    return () => {
      clearInterval(a);
      clearInterval(b);
    };
  }, []);

  return (
    <div className="fixed inset-0 z-[100] grid place-items-center bg-white/90 backdrop-blur">
      <div className="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-8 shadow-none">
        <div className="mb-5 flex items-center justify-center">
          <div className="h-8 w-8 animate-spin rounded-full border-3 border-blue-600 border-t-transparent" />
        </div>
        <h2 className="text-center text-xl font-semibold text-gray-900">{title}</h2>
        <p className="mt-2 text-center text-sm text-gray-600">{PHRASES[phase]}</p>
        <div className="mt-6">
          <div className="h-2 w-full rounded-full bg-gray-200">
            <div className="h-2 rounded-full bg-blue-600 transition-all" style={{ width: `${progress}%` }} />
          </div>
          <p className="mt-2 text-center text-xs text-gray-500">Almost there…</p>
        </div>
      </div>
    </div>
  );
};

export default WaitingPage;
