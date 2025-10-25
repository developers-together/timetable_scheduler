import React, { useEffect, useMemo, useState } from "react";
import { Head } from "@inertiajs/react";
import TimetableCell, { OneCourse, hashString } from "@/components/timetable-cell";
import { useTimetable, ComputationStatus } from "@/hooks/use-timetable";
import WaitingPage from "@/components/waiting-page";

/* ------------------------------------------------------------------ */
/* constants                                                           */
/* ------------------------------------------------------------------ */
const DAYS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday"] as const;
const SLOTS = ["09:00-10:30", "10:45-12:15", "12:30-14:00", "14:15-15:45"] as const;

/* ------------------------------------------------------------------ */
/* helpers                                                             */
/* ------------------------------------------------------------------ */
function to12h(hm: string) {
  const [H, M] = hm.split(":").map(Number);
  const h = ((H + 11) % 12) + 1;
  return `${h}:${String(M).padStart(2, "0")} ${H < 12 ? "AM" : "PM"}`;
}
function fmtRange(range: string, fmt: "12" | "24") {
  const [a, b] = range.split("-");
  return fmt === "12" ? `${to12h(a)} – ${to12h(b)}` : `${a}–${b}`;
}
function ordinal(n: number) {
  const v = n % 100;
  if (v >= 11 && v <= 13) return `${n}th`;
  switch (n % 10) {
    case 1: return `${n}st`;
    case 2: return `${n}nd`;
    case 3: return `${n}rd`;
    default: return `${n}th`;
  }
}

/* rectangular two-side toggle (used for Metrics Show/Hide AND Time 24h/12h) */
function TwoSideToggle({
  value,
  onChange,
  left,
  right,
}: {
  value: string;
  onChange: (v: string) => void;
  left: { label: string; value: string; icon?: React.ReactNode };
  right: { label: string; value: string; icon?: React.ReactNode };
}) {
  const isLeft = value === left.value;
  return (
    <div className="inline-flex overflow-hidden rounded-md border border-gray-200">
      <button
        type="button"
        onClick={() => onChange(left.value)}
        className={`flex items-center gap-1 px-3 py-1.5 text-sm transition ${
          isLeft ? "bg-blue-600 text-white" : "bg-white text-gray-700 hover:bg-gray-50"
        }`}
      >
        {left.icon} {left.label}
      </button>
      <button
        type="button"
        onClick={() => onChange(right.value)}
        className={`flex items-center gap-1 px-3 py-1.5 text-sm transition ${
          !isLeft ? "bg-blue-600 text-white" : "bg-white text-gray-700 hover:bg-gray-50"
        }`}
      >
        {right.icon} {right.label}
      </button>
    </div>
  );
}

/* simple checkbox (kept for any future small toggles if needed) */
function CheckboxToggle({
  checked,
  onChange,
  label,
  disabled,
}: {
  checked: boolean;
  onChange: (next: boolean) => void;
  label?: string;
  disabled?: boolean;
}) {
  return (
    <label className={["inline-flex items-center gap-2", disabled ? "opacity-60" : ""].join(" ")}>
      <input
        type="checkbox"
        className="h-4 w-4 rounded border border-gray-300 text-blue-600 focus:ring-blue-500"
        checked={checked}
        onChange={(e) => onChange(e.target.checked)}
        disabled={disabled}
      />
      {label && <span className="text-sm text-gray-800">{label}</span>}
    </label>
  );
}

/* ------------------------------------------------------------------ */
/* page                                                                */
/* ------------------------------------------------------------------ */
export default function TimetablePage() {
  const {
    timetableData,
    loading,
    computationStatus,
    lastComputedAt,
  } = useTimetable();

  const [timeFmt, setTimeFmt] = useState<"12" | "24">(
    (localStorage.getItem("tt_timeFormat") as "12" | "24") ?? "24",
  );
  useEffect(() => {
    localStorage.setItem("tt_timeFormat", timeFmt);
  }, [timeFmt]);

  // metrics toggle (two-side Show/Hide)
  const [metricsEnabled, setMetricsEnabled] = useState(
    (localStorage.getItem("tt_metricsEnabled") ?? "on") === "on",
  );
  useEffect(() => {
    localStorage.setItem("tt_metricsEnabled", metricsEnabled ? "on" : "off");
  }, [metricsEnabled]);

  // UI modal state
  const [openSettings, setOpenSettings] = useState(false);
  const [openMetrics, setOpenMetrics] = useState(false);

  // filters
  type Filters = {
    faculty?: string;
    instructor?: string;
    room?: string;
    year?: string;         // label for title (e.g. "1st year")
    yearRaw?: number;      // numeric if provided
    semester?: string;     // "1st" | "2nd"
    semesterRaw?: number;  // numeric if provided
    group?: string;
    section?: string;      // numeric-only string in UI
  };
  const [filters, setFilters] = useState<Filters>({});
  const [applied, setApplied] = useState<Filters>({});
  const [skip, setSkip] = useState(false);
  const [shake, setShake] = useState(false);

  // data -> option lists (derive numeric-only sections)
  const rawData = timetableData?.data ?? {};
  const allItems = useMemo(() => {
    type Detail = { slot: string | string[]; room_id: string; instructor_id: string | null } & Record<string, any>;
    const instructors = new Set<string>();
    const rooms = new Set<string>();
    const faculties = new Set<string>();
    const years = new Set<number>();
    const semesters = new Set<number>();
    const groups = new Set<string>();
    const sectionsNum = new Set<number>(); // numeric-only sections

    for (const [, types] of Object.entries<any>(rawData)) {
      for (const [, details] of Object.entries<Detail>(types || {})) {
        if (!details) continue;
        if (details.instructor_id) instructors.add(String(details.instructor_id));
        if (details.room_id) rooms.add(String(details.room_id));
        if (details.faculty != null) faculties.add(String(details.faculty));
        if (typeof details.year === "number") years.add(details.year);
        if (typeof details.semester === "number") semesters.add(details.semester);
        if (details.group != null) groups.add(String(details.group));
        if (details.section != null) {
          const n = Number(details.section);
          if (!Number.isNaN(n) && Number.isFinite(n)) sectionsNum.add(n);
        }
      }
    }
    const sections = Array.from(sectionsNum).sort((a, b) => a - b);
    return {
      instructors: Array.from(instructors).sort(),
      rooms: Array.from(rooms).sort(),
      faculties: Array.from(faculties).sort(),
      years: Array.from(years).sort((a, b) => a - b),
      semesters: Array.from(semesters).sort((a, b) => a - b),
      groups: Array.from(groups).sort(),
      sections,
    };
  }, [rawData]);

  // Instructor selection skips all mandatory checks (as requested)
  const instructorChosen = Boolean(applied.instructor);
  const mustValidate = !skip && !instructorChosen;

  // detect if anything is applied; used to keep default blank
  const hasAnyApplied =
    Boolean(applied.faculty) ||
    Boolean(applied.instructor) ||
    Boolean(applied.room) ||
    typeof applied.yearRaw === "number" ||
    typeof applied.semesterRaw === "number" ||
    Boolean(applied.group) ||
    Boolean(applied.section);

  // filtered dataset — blank by default unless skip OR something applied
  const filteredData = useMemo(() => {
    if (!skip && !hasAnyApplied) return {}; // default blank state

    const out: typeof rawData = {};
    const f = applied;

    const passes = (_courseId: string, _type: string, d: any) => {
      if (f.faculty && d.faculty != null && String(d.faculty) !== f.faculty) return false;
      if (f.instructor && String(d.instructor_id) !== f.instructor) return false;
      if (f.room && String(d.room_id) !== f.room) return false;
      if (f.yearRaw != null && d.year != null && Number(d.year) !== Number(f.yearRaw)) return false;
      if (f.semesterRaw != null && d.semester != null && Number(d.semester) !== Number(f.semesterRaw)) return false;
      if (f.group != null && d.group != null && String(d.group) !== f.group) return false;
      if (f.section != null && d.section != null && String(d.section) !== f.section) return false;
      return true;
    };

    for (const [cid, types] of Object.entries<any>(rawData)) {
      for (const [t, details] of Object.entries<any>(types || {})) {
        if (!details) continue;
        if (passes(cid, t, details)) {
          (out as any)[cid] ??= {};
          (out as any)[cid][t] = details;
        }
      }
    }
    return out;
  }, [rawData, applied, skip, hasAnyApplied]);

  // courses for a cell
  const coursesFor = (day: string, slot: string): OneCourse[] => {
    const start = slot.split("-")[0];
    const list: OneCourse[] = [];
    for (const [courseId, types] of Object.entries<any>(filteredData)) {
      for (const [type, details] of Object.entries<any>(types || {})) {
        const slots: string[] = Array.isArray(details?.slot) ? details.slot : [details?.slot].filter(Boolean);
        for (const s of slots) {
          const [d, st] = String(s).split("-");
          if (d === day && st === start) {
            list.push({
              courseId,
              courseName: courseId,
              type,
              roomId: details?.room_id,
              instructorId: details?.instructor_id ?? null,
              faculty: details?.faculty ?? null,
              year: details?.year ?? null,
              semester: details?.semester ?? null,
              group: details?.group ?? null,
              section: details?.section ?? null,
              creditHours: details?.credit_hours ?? null,
            });
          }
        }
      }
    }
    return list;
  };

  // hover counts
  const dayCounts = useMemo(() => {
    const res: Record<string, number> = {};
    for (const d of DAYS) {
      let n = 0;
      for (const s of SLOTS) n += coursesFor(d, s).length;
      res[d] = n;
    }
    return res;
  }, [filteredData]);

  // actions
  const onRegenerate = () => {
    const missing: string[] = [];
    if (mustValidate) {
      const hasFac = allItems.faculties.length > 0;
      if (hasFac && !applied.faculty) missing.push("Faculty");
      if (!applied.year && allItems.years.length > 0) missing.push("Year");
      if (!applied.semester && allItems.semesters.length > 0) missing.push("Semester");
      if (!applied.group && allItems.groups.length > 0) missing.push("Group");
      const hasSections = (allItems.sections?.length ?? 0) > 0;
      if (!applied.section && hasSections) missing.push("Section");
    }
    if (missing.length > 0) {
      setShake(true);
      setTimeout(() => setShake(false), 450);
      return;
    }
    setSkip(false);
  };

  const onReset = () => {
    setFilters({});
    setApplied({});
    setSkip(false);
  };

  const onSkip = () => {
    setSkip(true);
    setApplied({}); // show everything
  };

  // popups / modals
  const [activeCourse, setActiveCourse] = useState<OneCourse | null>(null);

  // printable CSS
  const printCSS = `
    @media print {
      @page { size: A4 landscape; margin: 10mm; }
      body { background: #fff !important; }
      body * { visibility: hidden !important; }
      #tt-print, #tt-print * { visibility: visible !important; }
      #tt-print { position: absolute; inset: 0; margin: 0 !important; padding: 0 !important; }
      .no-print { display: none !important; }
    }
    @keyframes shake {
      0%,100%{transform:translateX(0)}
      20%{transform:translateX(-4px)}
      40%{transform:translateX(4px)}
      60%{transform:translateX(-3px)}
      80%{transform:translateX(3px)}
    }
  `;

  if (loading || computationStatus === ComputationStatus.COMPUTING) {
    return (
      <>
        <Head title="Timetable" />
        <WaitingPage title="Loading timetable…" />
      </>
    );
  }

  /* ---------------------------- UI pieces ---------------------------- */

  const Label = ({ children }: { children: React.ReactNode }) => (
    <div className="mb-1 text-[11px] font-medium text-gray-600">{children}</div>
  );

  const selectClass = (val?: string, disabled?: boolean) =>
    [
      "w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm",
      !val ? "text-gray-400" : "text-gray-800",
      disabled ? "opacity-60" : "",
    ].join(" ");

  /* Group 1: Faculty + Instructor + Room — blue accents */
  const GroupOne = (
    <div className="rounded-xl border border-blue-200 bg-blue-50/60 p-3">
      <div className="mb-2 text-[12px] font-semibold text-blue-900">
        1 • Faculty, Instructor & Room
      </div>

      <div className="mb-3">
        <Label>Faculty</Label>
        <select
          value={filters.faculty ?? ""}
          onChange={(e) => setFilters((s) => ({ ...s, faculty: e.target.value || undefined }))}
          className={selectClass(filters.faculty, allItems.faculties.length === 0)}
          disabled={allItems.faculties.length === 0}
        >
          <option value="">
            {allItems.faculties.length === 0 ? "No faculties in data" : "Select faculty…"}
          </option>
          {allItems.faculties.map((f) => (
            <option key={f} value={f}>{f}</option>
          ))}
        </select>
      </div>

      <div className="mb-3">
        <Label>Instructor</Label>
        <select
          value={filters.instructor ?? ""}
          onChange={(e) => setFilters((s) => ({ ...s, instructor: e.target.value || undefined }))}
          className={selectClass(filters.instructor)}
        >
          <option value="">Select instructor…</option>
          {allItems.instructors.map((i) => (
            <option key={i} value={i}>{i}</option>
          ))}
        </select>
        {filters.instructor && (
          <p className="mt-1 text-[11px] text-blue-800/80">
            Selecting an instructor skips other mandatory filters.
          </p>
        )}
      </div>

      <div>
        <Label>Room</Label>
        <select
          value={filters.room ?? ""}
          onChange={(e) => setFilters((s) => ({ ...s, room: e.target.value || undefined }))}
          className={selectClass(filters.room)}
        >
          <option value="">Select room…</option>
          {allItems.rooms.map((r) => (
            <option key={r} value={r}>{r}</option>
          ))}
        </select>
      </div>
    </div>
  );

  /* Group 2: Year + Semester — amber accents */
  const GroupTwo = (
    <div className="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
      <div className="mb-2 text-[12px] font-semibold text-amber-900">2 • Year &amp; Semester</div>
      <div className="mb-3">
        <Label>Year</Label>
        <select
          value={filters.yearRaw ?? ""}
          onChange={(e) => {
            const yr = e.target.value ? Number(e.target.value) : undefined;
            setFilters((s) => ({ ...s, yearRaw: yr, year: yr ? `${ordinal(yr)} year` : undefined }));
          }}
          disabled={Boolean(filters.instructor)}
          className={selectClass(filters.yearRaw != null ? String(filters.yearRaw) : "", Boolean(filters.instructor))}
        >
          <option value="">Select year…</option>
          {(() => {
            const base = [1, 2, 3, 4];
            const extra = allItems.years.filter((y) => y > 4 && !base.includes(y));
            return [...base, ...extra].map((y) => (
              <option key={y} value={y}>{`${ordinal(y)} year`}</option>
            ));
          })()}
        </select>
      </div>
      <div>
        <Label>Semester</Label>
        <select
          value={filters.semesterRaw ?? ""}
          onChange={(e) => {
            const raw = e.target.value ? Number(e.target.value) : undefined;
            setFilters((s) => ({ ...s, semesterRaw: raw, semester: raw ? (raw % 2 === 1 ? "1st" : "2nd") : undefined }));
          }}
          disabled={Boolean(filters.instructor)}
          className={selectClass(filters.semesterRaw != null ? String(filters.semesterRaw) : "", Boolean(filters.instructor))}
        >
          <option value="">Select semester…</option>
          {(allItems.semesters.length ? allItems.semesters : [1, 2]).map((s) => (
            <option key={s} value={s}>{s % 2 === 1 ? "1st" : "2nd"}</option>
          ))}
        </select>
      </div>
    </div>
  );

  /* Group 3: Group + Section — emerald accents (section numeric-only) */
  const GroupThree = (
    <div className="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
      <div className="mb-2 text-[12px] font-semibold text-emerald-900">3 • Group &amp; Section</div>
      <div className="mb-3">
        <Label>Group</Label>
        <select
          value={filters.group ?? ""}
          onChange={(e) => setFilters((s) => ({ ...s, group: e.target.value || undefined }))}
          disabled={Boolean(filters.instructor)}
          className={selectClass(filters.group, Boolean(filters.instructor))}
        >
          <option value="">Select group…</option>
          {(allItems.groups.length ? allItems.groups : ["1", "2", "3"]).map((g) => (
            <option key={g} value={g}>{g}</option>
          ))}
        </select>
      </div>
      <div>
        <Label>Section (numbers)</Label>
        <select
          value={filters.section ?? ""}
          onChange={(e) => setFilters((s) => ({ ...s, section: e.target.value || undefined }))}
          disabled={Boolean(filters.instructor)}
          className={selectClass(filters.section, Boolean(filters.instructor))}
        >
          <option value="">Select section…</option>
          {(allItems.sections.length ? allItems.sections : [1, 2, 3]).map((n) => (
            <option key={n} value={String(n)}>{n}</option>
          ))}
        </select>
      </div>
    </div>
  );

  // buttons row: Skip left, Reset + Regenerate right
  const Actions = (
    <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
      <button
        type="button"
        onClick={onSkip}
        className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]"
      >
        Skip &amp; show anyway
      </button>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={onReset}
          className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]"
        >
          Reset
        </button>
        <button
          type="button"
          onClick={() => {
            setApplied({ ...filters });
            onRegenerate();
          }}
          className="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-semibold text-white transition active:scale-[0.98] hover:bg-blue-700"
        >
          Regenerate
        </button>
      </div>
    </div>
  );

  // selection chips
  const selectionChips = useMemo(() => {
    const parts: Array<{ k: string; v: string }> = [];
    if (applied.faculty) parts.push({ k: "Faculty", v: String(applied.faculty) });
    if (applied.instructor) parts.push({ k: "Instructor", v: String(applied.instructor) });
    if (applied.room) parts.push({ k: "Room", v: String(applied.room) });
    if (applied.year) parts.push({ k: "Year", v: String(applied.year) });
    if (applied.semester) parts.push({ k: "Semester", v: `${applied.semester}` });
    if (applied.group) parts.push({ k: "Group", v: String(applied.group) });
    if (applied.section) parts.push({ k: "Section", v: String(applied.section) });
    return parts;
  }, [applied]);

  /* ------------------------------ render ----------------------------- */

  return (
    <>
      <Head title="Timetable" />
      <style>{printCSS}</style>

      {/* top bar */}
      <div className="no-print sticky top-0 z-20 border-b border-gray-200 bg-white/85 backdrop-blur">
        <div className="mx-auto flex max-w-[1400px] items-center justify-between px-2 py-3">
          <div>
            <h1 className="text-lg font-semibold text-gray-900">Academic Timetable</h1>
            {lastComputedAt && (
              <p className="mt-0.5 text-[11px] text-gray-500">
                Last generated {lastComputedAt.toLocaleString()}
              </p>
            )}
          </div>

          <div className="flex items-center gap-2">
            {/* Metrics button (enabled only if toggle on) */}
            <button
              type="button"
              onClick={() => metricsEnabled && setOpenMetrics(true)}
              disabled={!metricsEnabled}
              className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98] disabled:opacity-60"
              title="Performance metrics"
            >
              <span className="inline-flex items-center gap-1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19h4V9H4v10Zm6 0h4V5h-4v14Zm6 0h4V13h-4v6Z"/></svg>
                Metrics
              </span>
            </button>

            {/* Settings = Bolt icon */}
            <button
              type="button"
              onClick={() => setOpenSettings(true)}
              className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]"
              title="Settings"
            >
              <span className="inline-flex items-center gap-1">
                {/* Bolt icon */}
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                  <circle cx="12" cy="12" r="4"/>
                </svg>
                Settings
              </span>
            </button>

            {/* Print / Export timetable only */}
            <button
              onClick={() => window.print()}
              className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]"
            >
              Print / Export
            </button>
          </div>
        </div>
      </div>

      {/* Filters row – 3 compact colorful groups in one rectangle */}
      <div className="no-print mx-auto mt-4 max-w-[1400px] px-2">
        <div className={["rounded-2xl border bg-white p-3 transition", shake ? "animate-[shake_0.45s_ease]" : "", "border-gray-200"].join(" ")}>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
            {GroupOne}
            {GroupTwo}
            {GroupThree}
          </div>
          {Actions}
        </div>
      </div>

      {/* selection chips */}
      {selectionChips.length > 0 && (
        <div className="no-print mx-auto mt-3 max-w-[1400px] px-2">
          <div className="flex flex-wrap gap-2">
            {selectionChips.map(({ k, v }) => (
              <span key={`${k}-${v}`} className="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] text-gray-700">
                <span className="mr-1 font-medium text-gray-800">{k}:</span> {v}
              </span>
            ))}
          </div>
        </div>
      )}

      {/* grid (blank by default until filters applied or skip) */}
      <div id="tt-print" className="mx-auto mt-4 max-w-[1400px] px-1 pb-12">
        <div className="overflow-x-auto rounded-xl border border-gray-200">
          <table className="min-w-full table-fixed bg-white text-[13px]">
            <colgroup>
              <col style={{ width: "9rem" }} />
              {DAYS.map((d) => (
                <col key={d} style={{ width: "9.75rem" }} />
              ))}
            </colgroup>
            <thead>
              <tr>
                <th className="sticky left-0 z-10 border-b border-gray-200 bg-gray-100 px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-gray-700 whitespace-nowrap">
                  Time / Day
                </th>
                {DAYS.map((d) => (
                  <th key={d} className="group relative border-b border-l border-gray-200 bg-gray-100 px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-gray-700">
                    {d}
                    <span className="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[10px] text-gray-600 group-hover:inline-block">
                      {dayCounts[d]}
                    </span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {SLOTS.map((slot, i) => (
                <React.Fragment key={slot}>
                  <tr className={i % 2 === 0 ? "bg-white" : "bg-gray-50/40"}>
                    <td className="sticky left-0 z-10 border-r border-gray-200 bg-gray-100 px-3 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                      {fmtRange(slot, timeFmt)}
                    </td>
                    {DAYS.map((day) => (
                      <td key={`${day}-${slot}`} className="border-r border-gray-200 px-1.5 py-2 align-top">
                        <TimetableCell
                          courses={(() => {
                            if (!skip && !hasAnyApplied) return [];
                            return coursesFor(day, slot);
                          })()}
                          onSelect={(c) => setActiveCourse(c)}
                        />
                      </td>
                    ))}
                  </tr>
                  {i < SLOTS.length - 1 && (
                    <tr aria-hidden>
                      <td className="bg-white py-1 whitespace-nowrap" />
                      {DAYS.map((d) => (
                        <td key={`${d}-gap-${i}`} className="border-r border-gray-200 bg-white px-1.5 py-1">
                          <div className="h-2" />
                        </td>
                      ))}
                    </tr>
                  )}
                </React.Fragment>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* SETTINGS MODAL */}
      {openSettings && (
        <Modal onClose={() => setOpenSettings(false)} title="Settings">
          <div className="space-y-4">
            {/* Performance toggle (Show/Hide) */}
            <div className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="mb-2 text-sm font-semibold text-gray-800">Performance</div>
              <TwoSideToggle
                value={metricsEnabled ? "show" : "hide"}
                onChange={(v) => setMetricsEnabled(v === "show")}
                left={{
                  value: "show",
                  label: "Show",
                  icon: (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-[14px] w-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  ),
                }}
                right={{
                  value: "hide",
                  label: "Hide",
                  icon: (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-[14px] w-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-3.17 4.17M1 1l22 22" />
                    </svg>
                  ),
                }}
              />
            </div>

            {/* Time format (24h / 12h) uses the same two-side toggle style */}
            <div className="rounded-xl border border-gray-200 bg-white p-4">
              <div className="mb-2 text-sm font-semibold text-gray-800">Time format</div>
              <TwoSideToggle
                value={timeFmt}
                onChange={(v) => setTimeFmt(v === "24" ? "24" : "12")}
                left={{
                  value: "24",
                  label: "24-hour",
                  icon: (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-[14px] w-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" />
                    </svg>
                  ),
                }}
                right={{
                  value: "12",
                  label: "12-hour",
                  icon: (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-[14px] w-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <circle cx="12" cy="12" r="9" /><path d="M12 7v5l2 2" />
                    </svg>
                  ),
                }}
              />
            </div>
          </div>
        </Modal>
      )}

      {/* METRICS MODAL (only when clicking button) */}
      {openMetrics && (
        <Modal onClose={() => setOpenMetrics(false)} title="Performance Metrics">
          <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
            <Metric label="Execution Time" value={timetableData?.execution_time ?? "-"} dot="bg-blue-500" />
            <Metric label="Assignments" value={timetableData?.assignments ?? "-"} dot="bg-emerald-500" />
            <Metric label="Backtracks" value={timetableData?.statistics?.backtracks ?? "-"} dot="bg-purple-500" />
            <Metric label="Consistency Checks" value={timetableData?.statistics?.consistency_checks ?? "-"} dot="bg-amber-500" />
          </div>
        </Modal>
      )}

      {/* COURSE MODAL */}
      {activeCourse && (
        <CourseModal course={activeCourse} onClose={() => setActiveCourse(null)} />
      )}
    </>
  );
}

/* ---------------------------- small components ---------------------------- */

function Metric({ label, value, dot }: { label: string; value: React.ReactNode; dot: string }) {
  return (
    <div className="rounded-xl border border-gray-200 bg-gray-50 p-3">
      <div className="mb-1.5 flex items-center">
        <span className={`mr-2 inline-block h-2 w-2 rounded-full ${dot}`} />
        <span className="text-xs font-semibold text-gray-700">{label}</span>
      </div>
      <div className="text-lg font-bold text-gray-900">{value}</div>
    </div>
  );
}

function Modal({ title, children, onClose }: { title: string; children: React.ReactNode; onClose: () => void }) {
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/20 p-4">
      <div className="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white">
        <div className="flex items-center justify-between rounded-t-2xl border-b border-gray-200 px-5 py-4">
          <div className="text-base font-semibold text-gray-900">{title}</div>
          <button
            onClick={onClose}
            className="rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700 hover:bg-gray-50 active:scale-[0.98]"
            aria-label="Close"
            title="Close"
          >
            ✕
          </button>
        </div>
        <div className="px-5 py-4">{children}</div>
      </div>
    </div>
  );
}

function CourseModal({ course, onClose }: { course: OneCourse; onClose: () => void }) {
  // theme based on course id, same as cell
  const idx = hashString(course.courseId) % 25;
  const THEMES = [
    ["bg-rose-100","text-rose-900","border-rose-300"],
    ["bg-orange-100","text-orange-900","border-orange-300"],
    ["bg-amber-100","text-amber-900","border-amber-300"],
    ["bg-yellow-100","text-yellow-900","border-yellow-300"],
    ["bg-lime-100","text-lime-900","border-lime-300"],
    ["bg-green-100","text-green-900","border-green-300"],
    ["bg-emerald-100","text-emerald-900","border-emerald-300"],
    ["bg-teal-100","text-teal-900","border-teal-300"],
    ["bg-cyan-100","text-cyan-900","border-cyan-300"],
    ["bg-sky-100","text-sky-900","border-sky-300"],
    ["bg-blue-100","text-blue-900","border-blue-300"],
    ["bg-indigo-100","text-indigo-900","border-indigo-300"],
    ["bg-violet-100","text-violet-900","border-violet-300"],
    ["bg-purple-100","text-purple-900","border-purple-300"],
    ["bg-fuchsia-100","text-fuchsia-900","border-fuchsia-300"],
    ["bg-pink-100","text-pink-900","border-pink-300"],
    ["bg-slate-100","text-slate-900","border-slate-300"],
    ["bg-zinc-100","text-zinc-900","border-zinc-300"],
    ["bg-neutral-100","text-neutral-900","border-neutral-300"],
    ["bg-stone-100","text-stone-900","border-stone-300"],
    ["bg-red-100","text-red-900","border-red-300"],
    ["bg-rose-50","text-rose-900","border-rose-300"],
    ["bg-blue-50","text-blue-900","border-blue-300"],
    ["bg-green-50","text-green-900","border-green-300"],
    ["bg-amber-50","text-amber-900","border-amber-300"],
  ] as const;
  const [bg, txt, border] = THEMES[idx];

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/20 p-4">
      <div className={`w-full max-w-lg rounded-2xl border ${border} bg-white`}>
        <div className={`flex items-center justify-between rounded-t-2xl border-b ${border} ${bg} px-5 py-4`}>
          <div>
            <div className={`text-base font-semibold ${txt}`}>{course.courseName ?? course.courseId}</div>
            <div className={`mt-0.5 text-xs ${txt} opacity-80`}>{course.courseId}</div>
          </div>
          <button
            onClick={onClose}
            className="rounded-md border border-white/70 bg-white/70 px-2 py-1 text-sm text-gray-800 hover:bg-white active:scale-[0.98]"
            aria-label="Close"
            title="Close"
          >
            ✕
          </button>
        </div>
        <div className="px-5 py-4">
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <Info label="Type" value={course.type || "—"} />
            <Info label="Room" value={course.roomId || "—"} />
            <Info label="Instructor" value={course.instructorId || "—"} />
            <Info label="Credit Hours" value={course.creditHours ?? "—"} />
            <Info label="Faculty" value={course.faculty ?? "—"} />
            <Info label="Year" value={course.year ?? "—"} />
            <Info label="Semester" value={course.semester ?? "—"} />
            <Info label="Group" value={course.group ?? "—"} />
            <Info label="Section" value={course.section ?? "—"} />
          </div>
        </div>
      </div>
    </div>
  );
}

function Info({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="inline-flex w-full items-start justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
      <div className="text-[11px] font-medium text-gray-600">{label}</div>
      <div className="ml-3 text-sm text-gray-900">{value}</div>
    </div>
  );
}
