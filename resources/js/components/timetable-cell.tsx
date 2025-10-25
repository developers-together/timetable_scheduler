import React from "react";

/* export for page to reuse in modal theme */
export function hashString(s: string) {
  let h = 0;
  for (let i = 0; i < s.length; i++) {
    h = (h << 5) - h + s.charCodeAt(i);
    h |= 0;
  }
  return Math.abs(h);
}

/** Shared shape coming from filtered rows on the page */
export type OneCourse = {
  courseId: string;
  courseName?: string;

  type: string; // Lecture | Lab | Tutorial
  roomId: string;
  instructorId: string | null;

  // optional metadata (if backend provides)
  faculty?: string | null;
  year?: number | null;
  semester?: number | string | null;
  group?: string | number | null;
  section?: string | number | null;
  creditHours?: number | null;
};

// 25 color sets: base bg, hover bg, border ring, text (light themes)
const COLOR_SETS = [
  ["bg-rose-100", "hover:bg-rose-200", "ring-rose-400", "text-rose-900", "border-rose-300"],
  ["bg-orange-100", "hover:bg-orange-200", "ring-orange-400", "text-orange-900", "border-orange-300"],
  ["bg-amber-100", "hover:bg-amber-200", "ring-amber-400", "text-amber-900", "border-amber-300"],
  ["bg-yellow-100", "hover:bg-yellow-200", "ring-yellow-400", "text-yellow-900", "border-yellow-300"],
  ["bg-lime-100", "hover:bg-lime-200", "ring-lime-400", "text-lime-900", "border-lime-300"],
  ["bg-green-100", "hover:bg-green-200", "ring-green-400", "text-green-900", "border-green-300"],
  ["bg-emerald-100", "hover:bg-emerald-200", "ring-emerald-400", "text-emerald-900", "border-emerald-300"],
  ["bg-teal-100", "hover:bg-teal-200", "ring-teal-400", "text-teal-900", "border-teal-300"],
  ["bg-cyan-100", "hover:bg-cyan-200", "ring-cyan-400", "text-cyan-900", "border-cyan-300"],
  ["bg-sky-100", "hover:bg-sky-200", "ring-sky-400", "text-sky-900", "border-sky-300"],
  ["bg-blue-100", "hover:bg-blue-200", "ring-blue-400", "text-blue-900", "border-blue-300"],
  ["bg-indigo-100", "hover:bg-indigo-200", "ring-indigo-400", "text-indigo-900", "border-indigo-300"],
  ["bg-violet-100", "hover:bg-violet-200", "ring-violet-400", "text-violet-900", "border-violet-300"],
  ["bg-purple-100", "hover:bg-purple-200", "ring-purple-400", "text-purple-900", "border-purple-300"],
  ["bg-fuchsia-100", "hover:bg-fuchsia-200", "ring-fuchsia-400", "text-fuchsia-900", "border-fuchsia-300"],
  ["bg-pink-100", "hover:bg-pink-200", "ring-pink-400", "text-pink-900", "border-pink-300"],
  ["bg-slate-100", "hover:bg-slate-200", "ring-slate-400", "text-slate-900", "border-slate-300"],
  ["bg-zinc-100", "hover:bg-zinc-200", "ring-zinc-400", "text-zinc-900", "border-zinc-300"],
  ["bg-neutral-100", "hover:bg-neutral-200", "ring-neutral-400", "text-neutral-900", "border-neutral-300"],
  ["bg-stone-100", "hover:bg-stone-200", "ring-stone-400", "text-stone-900", "border-stone-300"],
  ["bg-red-100", "hover:bg-red-200", "ring-red-400", "text-red-900", "border-red-300"],
  ["bg-rose-50", "hover:bg-rose-100", "ring-rose-300", "text-rose-900", "border-rose-300"],
  ["bg-blue-50", "hover:bg-blue-100", "ring-blue-300", "text-blue-900", "border-blue-300"],
  ["bg-green-50", "hover:bg-green-100", "ring-green-300", "text-green-900", "border-green-300"],
  ["bg-amber-50", "hover:bg-amber-100", "ring-amber-300", "text-amber-900", "border-amber-300"],
] as const;

export default function TimetableCell({
  courses,
  onSelect,
}: {
  courses: OneCourse[];
  onSelect?: (c: OneCourse) => void;
}) {
  if (!courses || courses.length === 0) {
    return (
      <div className="grid h-[72px] place-items-center rounded-xl border border-dashed border-gray-300 text-center text-xs text-gray-500">
        Empty
      </div>
    );
  }

  return (
    <div className="space-y-1.5">
      {courses.map((c, idx) => {
        const [base, hover, ring, text, border] =
          COLOR_SETS[hashString(c.courseId) % COLOR_SETS.length];

        // pattern per type: lecture blank, lab stripes, tutorial dots
        let bgImage = "none";
        let bgSize: string | undefined;
        const kind = (c.type || "").toLowerCase();
        if (kind === "lab") {
          bgImage =
            "repeating-linear-gradient(45deg, rgba(0,0,0,0.08) 0, rgba(0,0,0,0.08) 4px, transparent 4px, transparent 10px)";
        } else if (kind === "tutorial") {
          bgImage = "radial-gradient(rgba(0,0,0,0.1) 1px, transparent 1px)";
          bgSize = "10px 10px";
        }

        return (
          <button
            key={idx}
            type="button"
            onClick={() => onSelect?.(c)}
            className={[
              "block w-full rounded-xl border px-2 py-1.5 text-left text-[11px] ring-1 transition",
              base, hover, ring, text, border,
              "active:scale-[0.99]",
            ].join(" ")}
            style={{ backgroundImage: bgImage, backgroundSize: bgSize }}
          >
            {/* Title (course name or code) */}
            <div className="truncate font-semibold leading-tight">
              {c.courseName ?? c.courseId}
            </div>

            {/* Room only (compact) */}
            <div className="mt-0.5 flex items-center gap-1 leading-tight opacity-85">
              <svg
                className="h-3 w-3"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                aria-hidden
              >
                <path d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                <circle cx="12" cy="11" r="2" />
              </svg>
              <span className="truncate">{c.roomId}</span>
            </div>
          </button>
        );
      })}
    </div>
  );
}
