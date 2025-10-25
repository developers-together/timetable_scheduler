import React from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

export type CourseDetail = {
  courseId: string;
  courseName?: string;
  type?: string;
  roomId?: string;
  instructorId?: string | null;
  creditHours?: number | string | null;
  theme?: { base: string; ring: string; text: string };
};

export default function CourseDetailDialog({
  open,
  onOpenChange,
  detail,
}: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  detail: CourseDetail | null;
}) {
  if (!detail) return null;
  const { courseId, courseName, type, roomId, instructorId, creditHours, theme } = detail;

  const accent = theme?.ring ? theme.ring.replace(/^ring-/, "bg-") : "bg-gray-200";
  const text = theme?.text ?? "text-gray-900";
  const chip = theme?.base ?? "bg-gray-100";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md overflow-hidden p-0 border border-gray-200 shadow-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=open]:fade-in data-[state=closed]:fade-out">
        <div className={`h-1.5 w-full ${accent}`} />
        <div className="p-5">
          <DialogHeader>
            <DialogTitle className={`flex items-center gap-2 ${text}`}>
              <span className={`inline-flex h-8 w-8 items-center justify-center rounded-xl ${chip}`}>
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <rect x="3" y="4" width="18" height="16" rx="2" />
                  <path d="M7 8h10" />
                </svg>
              </span>
              <span className="truncate">{courseName || courseId}</span>
            </DialogTitle>
          </DialogHeader>

          <div className="mt-3 space-y-3">
            <Item label="Course Code" value={courseId} />
            <Item label="Type" value={type ? type.toLowerCase() : "—"} />
            <Item label="Room" value={roomId || "—"} />
            <Item label="Instructor" value={instructorId || "—"} />
            <Item label="Credit Hours" value={creditHours ?? "—"} />
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

function Item({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
      <span className="text-xs font-medium text-gray-600">{label}</span>
      <span className="text-sm font-semibold text-gray-900">{value}</span>
    </div>
  );
}
