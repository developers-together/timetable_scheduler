import React, { useEffect, useMemo, useRef, useState } from "react";
import { Head } from "@inertiajs/react";
import TimetableCell from "@/components/timetable-cell";
import WaitingPage from "@/components/waiting-page";
import { useTimetable, ComputationStatus } from "@/hooks/use-timetable";

// —— Days across, time vertically (no 16:00-17:30) ——
const DAYS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday"] as const;
const SLOTS = ["09:00-10:30", "10:45-12:15", "12:30-14:00", "14:15-15:45"] as const;

// ——————————— Utilities (no duplicates) ———————————
const to12h = (hhmm: string) => {
  const [H, M] = hhmm.split(":").map(Number);
  const h = ((H + 11) % 12) + 1;
  return `${h}:${String(M).padStart(2, "0")} ${H < 12 ? "AM" : "PM"}`;
};
const formatRange = (r: string, mode: "12" | "24") => {
  const [a, b] = r.split("-");
  return mode === "12" ? `${to12h(a)} – ${to12h(b)}` : `${a}–${b}`;
};
function isOdd(n: number) {
  return (n & 1) === 1;
}
function unique<T>(arr: T[]) {
  return Array.from(new Set(arr));
}

// ——————————— Types used only in this file ———————————
type OneCourse = {
  courseId: string;
  type: string; // "Lecture" | "Lab" | "Tutorial" | others
  roomId: string;
  instructorId: string | null;
  courseName?: string | null;
};

type VisibleFilters = {
  instructor: boolean;
  grade: boolean;
  semester: boolean;
  group: boolean;
  section: boolean;
  faculty: boolean; // this one will auto-hide if only one faculty is present
};

type FilterState = {
  instructor: string; // instructor_id
  grade: string; // "1" | "2" | "3" | "4" | ...
  semesterHalf: "" | "1" | "2"; // "1" => odd(1st), "2" => even(2nd)
  group: string; // group number/name
  section: string; // section id/name
  faculty: string; // faculty name/code
};

export default function TimetableTestPage() {
  const {
    timetableData,
    loading,
    computationStatus,
    lastComputedAt,
    fetchTimetable, // not used but kept available
  } = useTimetable();

  // —— Settings read/write (kept on timetable page as requested) ——
  const [timeFormat, setTimeFormat] = useState<"12" | "24">(
    (localStorage.getItem("tt_timeFormat") as "12" | "24") ?? "24",
  );
  const [showMetrics, setShowMetrics] = useState<boolean>(
    (localStorage.getItem("tt_showMetrics") ?? "on") === "on",
  );

  // —— Visibility toggles (hard-coded filters; easy to comment out) ——
  // To remove a filter completely, set its default to false or comment its block below.
  const [showFilter, setShowFilter] = useState<VisibleFilters>({
    instructor: true,
    grade: true,
    semester: true,
    group: true,
    section: true,
    faculty: true, // will be auto-hidden if not present in data
  });

  // —— Controlled filter values with placeholders ——
  const [filters, setFilters] = useState<FilterState>({
    instructor: "",
    grade: "",
    semesterHalf: "",
    group: "",
    section: "",
    faculty: "",
  });

  // —— Require selection before showing; allow skip ——
  const [mustChoose, setMustChoose] = useState(true);
  const [applied, setApplied] = useState<FilterState>(filters);
  const [appliedShowFilter, setAppliedShowFilter] = useState<VisibleFilters>(showFilter);

  // —— Modals ——
  const [openMetrics, setOpenMetrics] = useState(false);
  const [openSettings, setOpenSettings] = useState(false);

  // —— Cell details modal state ——
  const [cellDetail, setCellDetail] = useState<{
    open: boolean;
    themeKey?: string;
    courseId?: string;
    courseName?: string | null;
    instructorId?: string | null;
    roomId?: string;
    type?: string;
    credits?: number | null;
  }>({ open: false });

  // —— Derive lists dynamically from backend JSON when available ——
  const meta = useMemo(() => (timetableData as any)?.meta ?? {}, [timetableData]);
  const allInstructors = useMemo(() => {
    const out: string[] = [];
    const data = timetableData?.data ?? {};
    for (const types of Object.values<any>(data)) {
      for (const d of Object.values<any>(types)) {
        const slots = Array.isArray(d?.slot) ? d.slot : [d?.slot].filter(Boolean);
        if (!slots.length) continue;
        if (d?.instructor_id) out.push(d.instructor_id);
      }
    }
    return unique(out).sort();
  }, [timetableData]);

  // Try to read groups/sections/faculties/semesters from meta if present
  const { groups, sections, faculties, semestersFound } = useMemo(() => {
    const groupsSet = new Set<string>();
    const sectionsSet = new Set<string>();
    const facultySet = new Set<string>();
    const semesterNums: number[] = [];

    // meta by courseId shape is flexible; we check common keys
    for (const [cid, m] of Object.entries<any>(meta)) {
      if (!m || typeof m !== "object") continue;
      if (m.group) groupsSet.add(String(m.group));
      if (Array.isArray(m.groups)) m.groups.forEach((g: any) => groupsSet.add(String(g)));
      if (m.section) sectionsSet.add(String(m.section));
      if (Array.isArray(m.sections)) m.sections.forEach((s: any) => sectionsSet.add(String(s)));
      if (m.faculty) facultySet.add(String(m.faculty));
      if (typeof m.semester === "number") semesterNums.push(m.semester);
    }

    return {
      groups: Array.from(groupsSet).sort((a, b) => Number(a) - Number(b)),
      sections: Array.from(sectionsSet).sort((a, b) => Number(a) - Number(b)),
      faculties: Array.from(facultySet).sort(),
      semestersFound: unique(semesterNums).sort((a, b) => a - b),
    };
  }, [meta]);

  // Grade options: if semesters present, compute max year; else default to 1..4
  const gradeOptions = useMemo(() => {
    if (semestersFound.length) {
      const maxYear = Math.max(...semestersFound.map((n) => Math.ceil(n / 2)));
      return Array.from({ length: maxYear }, (_, i) => String(i + 1));
    }
    // default UI still shows options (hard-coded list)
    return ["1", "2", "3", "4"];
  }, [semestersFound]);

  // Auto-hide faculty filter if only one or none present
  const effectiveShowFaculty = showFilter.faculty && faculties.length > 1;

  // —— Helper: get course name/credits/faculty/semester ——
  const getCourseName = (courseId: string) => (meta?.[courseId]?.course_name ?? courseId) as string;
  const getCredits = (courseId: string) => (meta?.[courseId]?.credits ?? null) as number | null;
  const getSemesterNum = (courseId: string) => (meta?.[courseId]?.semester ?? null) as number | null;
  const getFaculty = (courseId: string) => (meta?.[courseId]?.faculty ?? null) as string | null;
  const getGroupOf = (courseId: string) => (meta?.[courseId]?.group ?? null) as string | null;
  const getSectionOf = (courseId: string) => (meta?.[courseId]?.section ?? null) as string | null;

  // —— Compute “title” chips for what’s selected ——
  const selectionTitle = useMemo(() => {
    const chips: string[] = [];
    if (effectiveShowFaculty && applied.faculty) chips.push(`Faculty: ${applied.faculty}`);
    if (applied.grade) chips.push(`Year: ${applied.grade}`);
    if (applied.semesterHalf) chips.push(`Semester: ${applied.semesterHalf === "1" ? "1st" : "2nd"}`);
    if (applied.group) chips.push(`Group: ${applied.group}`);
    if (applied.section) chips.push(`Section: ${applied.section}`);
    if (applied.instructor) chips.push(`Instructor: ${applied.instructor}`);
    return chips.join(" · ");
  }, [applied, effectiveShowFaculty]);

  // —— Derived rows list: "slot" rows + a single connected "gap" row between them ——
  const rows = useMemo(() => {
    const out: Array<{ kind: "slot" | "gap"; label: string; id: string }> = [];
    SLOTS.forEach((slot, i) => {
      out.push({ kind: "slot", label: slot, id: `slot-${slot}` });
      if (i < SLOTS.length - 1) out.push({ kind: "gap", label: "15-minute break", id: `gap-${i}` });
    });
    return out;
  }, []);

  // —— Filtering logic ——
  const passesFilters = (courseId: string, instructorId: string | null) => {
    const f = applied;
    const vis = appliedShowFilter;

    if (vis.instructor && f.instructor && instructorId && instructorId !== f.instructor) return false;
    if (vis.instructor && f.instructor && !instructorId) return false;

    if (vis.faculty && effectiveShowFaculty && f.faculty) {
      const fac = getFaculty(courseId);
      if (!fac || fac !== f.faculty) return false;
    }

    if (vis.grade && f.grade) {
      const sem = getSemesterNum(courseId);
      if (typeof sem !== "number") return false; // no data -> exclude
      const year = Math.ceil(sem / 2);
      if (String(year) !== f.grade) return false;
    }

    if (vis.semester && f.semesterHalf) {
      const sem = getSemesterNum(courseId);
      if (typeof sem !== "number") return false;
      if (f.semesterHalf === "1" && !isOdd(sem)) return false;
      if (f.semesterHalf === "2" && isOdd(sem)) return false;
    }

    if (vis.group && f.group) {
      const g = getGroupOf(courseId);
      if (!g || String(g) !== String(f.group)) return false;
    }

    if (vis.section && f.section) {
      const s = getSectionOf(courseId);
      if (!s || String(s) !== String(f.section)) return false;
    }

    return true;
  };

  const coursesForSlot = (day: string, timeSlot: string): OneCourse[] => {
    const start = timeSlot.split("-")[0];
    const out: OneCourse[] = [];
    const data = timetableData?.data ?? {};
    for (const [courseId, types] of Object.entries<any>(data)) {
      for (const [type, details] of Object.entries<any>(types)) {
        const slots = Array.isArray(details?.slot) ? details.slot : [details?.slot].filter(Boolean);
        for (const s of slots) {
          const [slotDay, slotTime] = String(s).split("-");
          if (slotDay === day && slotTime === start) {
            if (!mustChoose || (mustChoose && isSelectionSatisfied())) {
              if (passesFilters(courseId, details?.instructor_id ?? null)) {
                out.push({
                  courseId,
                  type,
                  roomId: details?.room_id,
                  instructorId: details?.instructor_id ?? null,
                  courseName: getCourseName(courseId),
                });
              }
            }
          }
        }
      }
    }
    return out;
  };

  // —— required-selection check ——
  function isSelectionSatisfied() {
    const vis = showFilter;
    // faculty only if it’s effectively shown
    const facultyRequired = vis.faculty && effectiveShowFaculty;
    const checks: Array<[boolean, string]> = [
      [!vis.instructor || !!filters.instructor, "instructor"],
      [!vis.grade || !!filters.grade, "grade"],
      [!vis.semester || !!filters.semesterHalf, "semester"],
      [!vis.group || !!filters.group, "group"],
      [!vis.section || !!filters.section, "section"],
      [!facultyRequired || !!filters.faculty, "faculty"],
    ];
    return checks.every(([ok]) => ok);
  }

  function applyFilters() {
    if (mustChoose && !isSelectionSatisfied()) {
      // simple shake hint
      const el = document.getElementById("filter-panel");
      if (el) {
        el.animate(
          [{ transform: "translateX(0)" }, { transform: "translateX(-6px)" }, { transform: "translateX(6px)" }, { transform: "translateX(0)" }],
          { duration: 260, easing: "ease-in-out" },
        );
      }
      return;
    }
    setApplied({ ...filters });
    setAppliedShowFilter({ ...showFilter });
  }

  function skipAndShow() {
    setMustChoose(false);
    setApplied({ ...filters });
    setAppliedShowFilter({ ...showFilter });
  }

  // —— persist settings changes ——
  useEffect(() => {
    localStorage.setItem("tt_timeFormat", timeFormat);
  }, [timeFormat]);
  useEffect(() => {
    localStorage.setItem("tt_showMetrics", showMetrics ? "on" : "off");
  }, [showMetrics]);

  // —— loading overlay ——
  if (loading || computationStatus === ComputationStatus.COMPUTING) {
    return (
      <>
        <Head title="Timetable" />
        <WaitingPage title="Building your timetable" />
      </>
    );
  }

  // ——————————— Render ———————————
  return (
    <>
      <Head title="Timetable" />
      <div className="min-h-screen bg-white pb-12">
        {/* Top bar */}
        <div className="sticky top-0 z-30 border-b border-gray-200 bg-white/80 backdrop-blur">
          <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <div>
              <h1 className="text-lg font-semibold text-gray-900">Academic Timetable</h1>
              {lastComputedAt && (
                <p className="mt-0.5 text-xs text-gray-500">
                  Last generated {new Date(lastComputedAt).toLocaleString()}
                </p>
              )}
            </div>
            <div className="flex items-center gap-2">
              {/* Metrics icon */}
              <button
                onClick={() => setOpenMetrics(true)}
                className="rounded-lg border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 transition"
                title="Performance metrics"
                aria-label="Performance metrics"
              >
                {/* bar chart icon */}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M4 19h4V9H4v10Zm6 0h4V5h-4v14Zm6 0h4V13h-4v6Z" />
                </svg>
              </button>
              {/* Settings icon */}
              <button
                onClick={() => setOpenSettings(true)}
                className="rounded-lg border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 transition"
                title="Settings"
                aria-label="Settings"
              >
                {/* gear icon */}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 5 15a1.65 1.65 0 0 0-1.51-1H3.4a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 5 8.49a1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 7.44 3.8l.06.06A1.65 1.65 0 0 0 9.32 4.2 1.65 1.65 0 0 0 10.83 3.2H11a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06A2 2 0 1 1 20.53 7.2l-.06.06c-.52.52-.68 1.3-.33 1.82.3.47.82.77 1.39.82H22a2 2 0 1 1 0 4h-.09c-.57.05-1.09.35-1.39.82Z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Filter panel */}
        <div id="filter-panel" className="mx-auto mt-6 max-w-7xl px-4">
          <div className="rounded-xl border border-gray-200 bg-white">
            {/* filter switches */}
            <div className="grid grid-cols-2 gap-3 border-b border-gray-200 p-4 md:grid-cols-6">
              {/* Toggle to show/hide each filter — comment any block to permanently remove a filter */}
              <Toggle label="Instructor" checked={showFilter.instructor} onChange={(v) => setShowFilter((s) => ({ ...s, instructor: v }))} />
              <Toggle label="Grade (Year)" checked={showFilter.grade} onChange={(v) => setShowFilter((s) => ({ ...s, grade: v }))} />
              <Toggle label="Semester" checked={showFilter.semester} onChange={(v) => setShowFilter((s) => ({ ...s, semester: v }))} />
              <Toggle label="Group" checked={showFilter.group} onChange={(v) => setShowFilter((s) => ({ ...s, group: v }))} />
              <Toggle label="Section" checked={showFilter.section} onChange={(v) => setShowFilter((s) => ({ ...s, section: v }))} />
              <Toggle label="Faculty" checked={showFilter.faculty} onChange={(v) => setShowFilter((s) => ({ ...s, faculty: v }))} />
            </div>

            {/* actual selectors */}
            <div className="grid grid-cols-1 gap-4 p-4 md:grid-cols-6">
              {/* Instructor */}
              {showFilter.instructor && (
                <Selector
                  label="Instructor"
                  value={filters.instructor}
                  onChange={(v) => setFilters((s) => ({ ...s, instructor: v }))}
                  placeholder="Select instructor"
                  options={allInstructors.map((i) => ({ value: i, label: i }))}
                />
              )}

              {/* Grade (Year) — always show options; filter only works if backend provides semester numbers */}
              {showFilter.grade && (
                <Selector
                  label="Grade (Year)"
                  value={filters.grade}
                  onChange={(v) => setFilters((s) => ({ ...s, grade: v }))}
                  placeholder="Select year"
                  options={gradeOptions.map((y) => ({ value: y, label: `${y} year` }))}
                />
              )}

              {/* Semester half (odd/even) */}
              {showFilter.semester && (
                <Selector
                  label="Semester"
                  value={filters.semesterHalf}
                  onChange={(v) => setFilters((s) => ({ ...s, semesterHalf: v as "1" | "2" | "" }))}
                  placeholder="Select semester"
                  options={[
                    { value: "1", label: "1st (odd)" },
                    { value: "2", label: "2nd (even)" },
                  ]}
                />
              )}

              {/* Group — list comes from backend if present */}
              {showFilter.group && (
                <Selector
                  label="Group"
                  value={filters.group}
                  onChange={(v) => setFilters((s) => ({ ...s, group: v }))}
                  placeholder="Select group"
                  options={(groups.length ? groups : []).map((g) => ({ value: g, label: g }))}
                />
              )}

              {/* Section — list comes from backend if present */}
              {showFilter.section && (
                <Selector
                  label="Section"
                  value={filters.section}
                  onChange={(v) => setFilters((s) => ({ ...s, section: v }))}
                  placeholder="Select section"
                  options={(sections.length ? sections : []).map((sec) => ({ value: sec, label: sec }))}
                />
              )}

              {/* Faculty — appears only when >1 distinct faculty present */}
              {effectiveShowFaculty && (
                <Selector
                  label="Faculty"
                  value={filters.faculty}
                  onChange={(v) => setFilters((s) => ({ ...s, faculty: v }))}
                  placeholder="Select faculty"
                  options={faculties.map((f) => ({ value: f, label: f }))}
                />
              )}
            </div>

            {/* Actions */}
            <div className="flex flex-col items-end gap-2 border-t border-gray-200 p-4 md:flex-row md:justify-between">
              {mustChoose && (
                <p className="text-[13px] text-gray-500">
                  Please choose values for the enabled filters, or{" "}
                  <button className="underline hover:text-gray-700" onClick={skipAndShow}>
                    skip & show anyway
                  </button>
                  .
                </p>
              )}
              <div className="flex gap-2">
                <button
                  onClick={() => {
                    setFilters({ instructor: "", grade: "", semesterHalf: "", group: "", section: "", faculty: "" });
                  }}
                  className="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                >
                  Reset
                </button>
                <button
                  onClick={applyFilters}
                  className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 active:scale-[0.99] transition"
                >
                  Regenerate
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Selected scope title */}
        <div className="mx-auto max-w-7xl px-4">
          {selectionTitle && (
            <div className="mt-4 flex flex-wrap gap-2">
              {selectionTitle.split(" · ").map((chip) => (
                <span key={chip} className="rounded-full border border-gray-200 px-3 py-1 text-xs text-gray-700">
                  {chip}
                </span>
              ))}
            </div>
          )}
        </div>

        {/* Timetable */}
        <div className="mx-auto mt-6 max-w-7xl px-4">
          <div className="overflow-x-auto rounded-xl border border-gray-200">
            <table className="min-w-full bg-white text-[13px]">
              <thead>
                <tr>
                  <th className="sticky left-0 z-10 border-b border-gray-200 bg-gray-100 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-gray-700"
                      style={{ minWidth: 160 }}>
                    Time / Day
                  </th>
                  {DAYS.map((d) => (
                    <th key={d} className="border-b border-l border-gray-200 bg-gray-100 px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-gray-700">
                      {d}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {rows.map((row, idx) =>
                  row.kind === "gap" ? (
                    <tr key={row.id} aria-hidden>
                      {/* one connected, thin gray bar spanning all columns */}
                      <td colSpan={DAYS.length + 1} className="px-0 py-1">
                        <div
                          className="mx-5 h-[2px] rounded-full bg-gray-200 transition-opacity hover:opacity-80"
                          title="15-minute break"
                        />
                      </td>
                    </tr>
                  ) : (
                    <tr key={row.id} className={idx % 2 === 0 ? "bg-white" : "bg-gray-50/40"}>
                      <td className="sticky left-0 z-10 border-r border-gray-200 bg-gray-100 px-5 py-3 text-sm font-medium text-gray-900"
                          style={{ minWidth: 160 }}>
                        {formatRange(row.label, timeFormat)}
                      </td>
                      {DAYS.map((day) => {
                        const courses = coursesForSlot(day, row.label);
                        return (
                          <td key={`${day}-${row.id}`} className="border-r border-gray-200 px-2 py-2 align-top">
                            <TimetableCell
                              courses={courses}
                              onClick={(payload) =>
                                setCellDetail({
                                  open: true,
                                  themeKey: payload.themeKey,
                                  courseId: payload.courseId,
                                  courseName: payload.courseName ?? payload.courseId,
                                  instructorId: payload.instructorId ?? null,
                                  roomId: payload.roomId,
                                  type: payload.type,
                                  credits: getCredits(payload.courseId),
                                })
                              }
                            />
                          </td>
                        );
                      })}
                    </tr>
                  ),
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Metrics modal */}
        {openMetrics && showMetrics && timetableData && (
          <Modal onClose={() => setOpenMetrics(false)} title="Performance Metrics">
            <div className="grid grid-cols-2 gap-4">
              <MetricCard label="Execution Time" value={timetableData.execution_time ?? "-"} dot="bg-blue-500" />
              <MetricCard label="Assignments" value={timetableData.assignments ?? "-"} dot="bg-emerald-500" />
              <MetricCard label="Backtracks" value={timetableData.statistics?.backtracks ?? "-"} dot="bg-purple-500" />
              <MetricCard label="Consistency Checks" value={timetableData.statistics?.consistency_checks ?? "-"} dot="bg-amber-500" />
            </div>
          </Modal>
        )}

        {/* Settings modal */}
        {openSettings && (
          <Modal onClose={() => setOpenSettings(false)} title="Settings">
            <div className="space-y-4">
              <fieldset className="rounded-xl border border-gray-200 p-4">
                <legend className="px-1 text-sm font-medium text-gray-700">Show performance metrics</legend>
                <div className="mt-2 flex gap-4">
                  <Radio
                    name="metrics"
                    checked={showMetrics}
                    onChange={() => setShowMetrics(true)}
                    label="On"
                  />
                  <Radio
                    name="metrics"
                    checked={!showMetrics}
                    onChange={() => setShowMetrics(false)}
                    label="Off"
                  />
                </div>
              </fieldset>

              <fieldset className="rounded-xl border border-gray-200 p-4">
                <legend className="px-1 text-sm font-medium text-gray-700">Time format</legend>
                <div className="mt-2 flex gap-4">
                  <Radio name="timeFormat" checked={timeFormat === "24"} onChange={() => setTimeFormat("24")} label="24-hour" />
                  <Radio name="timeFormat" checked={timeFormat === "12"} onChange={() => setTimeFormat("12")} label="12-hour" />
                </div>
              </fieldset>
            </div>
          </Modal>
        )}

        {/* Cell detail modal */}
        {cellDetail.open && (
          <Modal onClose={() => setCellDetail({ open: false })} title={cellDetail.courseName ?? cellDetail.courseId ?? "Course"}>
            <div className={`rounded-xl border ${cellDetail.themeKey ?? "border-gray-200"} p-4`}>
              <DetailRow label="Type" value={cellDetail.type ? shortType(cellDetail.type) : "-"} />
              <DetailRow label="Code" value={cellDetail.courseId ?? "-"} />
              <DetailRow label="Instructor" value={cellDetail.instructorId ?? "-"} />
              <DetailRow label="Credit Hours" value={cellDetail.credits ?? "-"} />
              <DetailRow label="Room" value={cellDetail.roomId ?? "-"} />
            </div>
          </Modal>
        )}
      </div>
    </>
  );
}

// ——————————— Small UI helpers/components ———————————
function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm">
      <span className="text-gray-700">{label}</span>
      <span
        role="switch"
        aria-checked={checked}
        tabIndex={0}
        onClick={() => onChange(!checked)}
        onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && onChange(!checked)}
        className={`relative inline-flex h-6 w-10 cursor-pointer items-center rounded-full transition ${
          checked ? "bg-blue-600" : "bg-gray-300"
        }`}
      >
        <span
          className={`inline-block h-5 w-5 transform rounded-full bg-white shadow ring-1 ring-black/5 transition ${
            checked ? "translate-x-5" : "translate-x-1"
          }`}
        />
      </span>
    </label>
  );
}

function Selector({
  label,
  value,
  onChange,
  placeholder,
  options,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  placeholder: string;
  options: Array<{ value: string; label: string }>;
}) {
  return (
    <div className="flex flex-col">
      <label className="mb-1 text-xs font-medium text-gray-600">{label}</label>
      <select
        className={[
          "w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition",
          value ? "text-gray-900" : "text-gray-400",
        ].join(" ")}
        value={value}
        onChange={(e) => onChange(e.target.value)}
      >
        <option value="">{placeholder}</option>
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </div>
  );
}

function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
  // simple portal-less modal
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/20 p-4" onClick={onClose}>
      <div
        className="max-h-[80vh] w-full max-w-xl overflow-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-none"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
          <button
            onClick={onClose}
            className="rounded-lg border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 transition"
            aria-label="Close"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" fill="none">
              <path d="M18 6 6 18M6 6l12 12" />
            </svg>
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}

function MetricCard({ label, value, dot }: { label: string; value: React.ReactNode; dot: string }) {
  return (
    <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
      <div className="mb-2 flex items-center">
        <span className={`mr-2 inline-block h-2 w-2 rounded-full ${dot}`} />
        <span className="text-xs font-semibold text-gray-700">{label}</span>
      </div>
      <div className="text-xl font-bold text-gray-900">{value}</div>
    </div>
  );
}

function Radio({
  name,
  label,
  checked,
  onChange,
}: {
  name: string;
  label: string;
  checked: boolean;
  onChange: () => void;
}) {
  return (
    <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
      <input
        type="radio"
        name={name}
        checked={checked}
        onChange={onChange}
        className="h-4 w-4 appearance-none rounded-full border border-gray-300 outline-none ring-2 ring-transparent transition checked:border-blue-600 checked:ring-blue-100"
      />
      <span className="text-gray-700">{label}</span>
    </label>
  );
}

function DetailRow({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between border-b border-gray-200 py-2 last:border-b-0">
      <span className="text-sm text-gray-600">{label}</span>
      <span className="text-sm font-medium text-gray-900">{String(value)}</span>
    </div>
  );
}

function shortType(t?: string) {
  if (!t) return "-";
  const k = t.toLowerCase();
  if (k.startsWith("lec")) return "lec";
  if (k.startsWith("lab")) return "lab";
  if (k.startsWith("tut")) return "tut";
  return t;
}
