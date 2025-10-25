import React, { useMemo, useRef, useState, useCallback } from "react";
import { Head, router } from "@inertiajs/react";

// Allowed sources
type Source = "" | "csv" | "excel" | "db";

export default function GeneratePage() {
  const [source, setSource] = useState<Source>("");
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Color accents for each card (keeps the page colorful and stable)
  const accents = {
    csv:   { edge: "border-emerald-300",  soft: "bg-emerald-50",  chip: "bg-emerald-100 text-emerald-800",  btn: "bg-emerald-600 hover:bg-emerald-700" },
    excel: { edge: "border-amber-300",    soft: "bg-amber-50",    chip: "bg-amber-100 text-amber-800",    btn: "bg-amber-600 hover:bg-amber-700"   },
    db:    { edge: "border-violet-300",   soft: "bg-violet-50",   chip: "bg-violet-100 text-violet-800",   btn: "bg-violet-600 hover:bg-violet-700" },
  } as const;

  const canGenerate = useMemo(() => {
    if (source === "db") return true;
    if (source === "csv")   return !!file && /\.csv$/i.test(file.name);
    if (source === "excel") return !!file && /\.(xlsx|xls)$/i.test(file.name);
    return false;
  }, [source, file]);

  const pick = (s: Source) => {
    setError(null);
    setSource(s);
    if (s === "db") setFile(null);
    if (s === "csv"   && file && !/\.csv$/i.test(file.name)) setFile(null);
    if (s === "excel" && file && !/\.(xlsx|xls)$/i.test(file.name)) setFile(null);
  };

  const onBrowse = () => inputRef.current?.click();
  const onDragOver = (e: React.DragEvent<HTMLDivElement>) => e.preventDefault();

  const onDrop = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    const f = e.dataTransfer.files?.[0];
    if (!f) return;
    if (!source) { setError("Choose CSV or Excel first."); return; }
    if (source === "csv"   && !/\.csv$/i.test(f.name)) { setError("CSV requires a .csv file."); return; }
    if (source === "excel" && !/\.(xlsx|xls)$/i.test(f.name)) { setError("Excel requires .xlsx / .xls."); return; }
    setError(null); setFile(f);
  }, [source]);

  const go = () => {
    // Persist a tiny bit of context for the waiting screen (UX only)
    localStorage.setItem("tt_ingestion", JSON.stringify({ source, filename: file?.name ?? null }));
    router.visit(`/waiting?src=${source || "unknown"}`);
  };

  const Card = ({
    id, title, note, svg,
  }: { id: "csv" | "excel" | "db"; title: string; note: string; svg: React.ReactNode }) => {
    const active = source === id;
    const a = accents[id];
    return (
      <button
        type="button"
        onClick={() => pick(id)}
        aria-pressed={active}
        className={[
          "group flex items-center justify-between rounded-2xl border px-4 py-4 transition active:scale-[0.99]",
          active ? `bg-white ring-2 ring-blue-400 ring-offset-1 ${a.edge}` : `bg-white ${a.edge}`,
        ].join(" ")}
      >
        <div className="flex items-center gap-3">
          <div className={["grid h-9 w-9 place-items-center rounded-xl border", a.edge, a.soft].join(" ")}>
            {svg}
          </div>
          <div className="text-left">
            <div className="text-sm font-semibold text-gray-900">{title}</div>
            <div className={["mt-0.5 inline-block rounded-md px-1.5 py-0.5 text-[11px]", a.chip].join(" ")}>
              {note}
            </div>
          </div>
        </div>
        <div className="rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] text-gray-600">Select</div>
      </button>
    );
  };

  // Inline SVGs (no dependency on icon packages)
  const SvgCsv = (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-emerald-700">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
      <path d="M14 2v6h6"/>
      <path d="M8 13h8M8 17h8"/>
    </svg>
  );
  const SvgExcel = (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-amber-700">
      <path d="M4 4h16v16H4z"/><path d="M9 8h6M9 12h6M9 16h6"/><path d="M8 8v8M16 8v8"/>
    </svg>
  );
  const SvgDb = (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" className="text-violet-700">
      <ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/>
      <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>
    </svg>
  );

  return (
    <>
      <Head title="Generate" />
      <div className="min-h-screen bg-white">
        <div className="mx-auto max-w-4xl px-4 py-10">
          <h1 className="mb-6 text-center text-2xl font-semibold text-gray-900">Generate from</h1>

          {/* colorful selectable cards */}
          <div className="mx-auto grid max-w-3xl grid-cols-1 gap-3 sm:grid-cols-3">
            <Card id="csv"   title="CSV"   note=".csv"          svg={SvgCsv} />
            <Card id="excel" title="Excel" note=".xlsx / .xls"  svg={SvgExcel} />
            <Card id="db"    title="Saved Database" note="testing only" svg={SvgDb} />
          </div>

          {/* drop zone */}
          <div
            onDrop={onDrop}
            onDragOver={onDragOver}
            className={[
              "mx-auto mt-6 grid max-w-3xl place-items-center rounded-2xl border border-dashed p-10 text-center transition",
              source ? "border-blue-300 bg-blue-50/30" : "border-gray-300",
            ].join(" ")}
          >
            <p className="text-sm text-gray-600">Drag the file you want here to analyze</p>
            <div className="mt-4">
              <button
                onClick={onBrowse}
                disabled={source === "" || source === "db"}
                className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]"
              >
                Browse…
              </button>
            </div>
            {file && <p className="mt-3 text-sm text-blue-700">Selected: <span className="font-medium">{file.name}</span></p>}
            {!source && <p className="mt-3 text-xs text-gray-400">Pick a source above</p>}
            {source === "db" && <p className="mt-3 text-xs text-gray-500">No upload required for Saved Database.</p>}
          </div>

          {error && (
            <div className="mx-auto mt-4 max-w-3xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          {/* action */}
          <div className="mx-auto mt-6 max-w-3xl">
            <div className="flex justify-end">
              <button
                onClick={go}
                disabled={!canGenerate}
                className={[
                  "rounded-lg px-5 py-2 text-sm font-semibold text-white transition active:scale-[0.98]",
                  source === "csv"   ? (canGenerate ? accents.csv.btn   : "bg-gray-300") :
                  source === "excel" ? (canGenerate ? accents.excel.btn : "bg-gray-300") :
                  source === "db"    ? (canGenerate ? accents.db.btn    : "bg-gray-300") :
                                        "bg-gray-300",
                ].join(" ")}
              >
                Generate
              </button>
            </div>
          </div>

          {/* hidden input */}
          <input
            ref={inputRef}
            type="file"
            className="hidden"
            accept={source === "csv" ? ".csv" : source === "excel" ? ".xlsx,.xls" : undefined}
            onChange={(e) => {
              const f = e.target.files?.[0] ?? null;
              if (!f) return;
              if (source === "csv"   && !/\.csv$/i.test(f.name))  { setError("CSV requires a .csv file."); setFile(null); return; }
              if (source === "excel" && !/\.(xlsx|xls)$/i.test(f.name)) { setError("Excel requires .xlsx / .xls."); setFile(null); return; }
              setError(null); setFile(f);
            }}
          />
        </div>
      </div>
    </>
  );
}
