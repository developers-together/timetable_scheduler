import TimetableCell from '@/components/timetable-cell';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import WaitingPage from '@/components/waiting-page';
import { useTimetable } from '@/hooks/use-timetable';
import { Head } from '@inertiajs/react';
import React, { useState } from 'react';

// Using type Record instead of empty interface
type TimetableTestProps = Record<string, never>;

// Define days and time slots
const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
const timeSlots = ['09:00-10:30', '10:45-12:15', '12:30-14:00', '14:15-15:45'];

// Sample timetable data for testing
// Using backend-provided data via useTimetable; removed local sample data.

const TimetableTest: React.FC<TimetableTestProps> = () => {
    const [showWaitingPage, setShowWaitingPage] = useState(false);
    const { timetableData, loading, lastComputedAt, generateTimetable } =
        useTimetable();

    // Trigger real backend generation
    const handleCompute = async () => {
        setShowWaitingPage(true);
        try {
            await generateTimetable();
        } finally {
            setShowWaitingPage(false);
        }
    };

    // Function to get all courses scheduled for a specific day and time
    const getCoursesForSlot = (day: string, timeSlot: string) => {
        if (!timetableData || !timetableData.data) return [];
        const courses: Array<{
            courseId: string;
            type: string;
            roomId: string;
            instructorId: string | null;
        }> = [];
        const timeStart = timeSlot.split('-')[0]; // Get just the start time (e.g., "09:00")

        for (const [courseId, types] of Object.entries(timetableData.data)) {
            const typedTypes = types as Record<
                string,
                {
                    slot: string | string[];
                    room_id: string;
                    instructor_id: string | null;
                }
            >;
            for (const [type, details] of Object.entries(typedTypes)) {
                if (Array.isArray(details.slot)) {
                    for (const slot of details.slot) {
                        const [slotDay, slotTime] = slot.split('-');
                        if (slotDay === day && slotTime === timeStart) {
                            courses.push({
                                courseId,
                                type,
                                roomId: details.room_id,
                                instructorId: details.instructor_id,
                            });
                        }
                    }
                } else {
                    const [slotDay, slotTime] = details.slot.split('-');
                    if (slotDay === day && slotTime === timeStart) {
                        courses.push({
                            courseId,
                            type,
                            roomId: details.room_id,
                            instructorId: details.instructor_id,
                        });
                    }
                }
            }
        }

        return courses;
    };

    // If waiting page is shown, render it instead of the timetable
    if (showWaitingPage || loading) {
        return (
            <>
                <Head title="Computing Timetable" />
                <WaitingPage description="Computing timetable data. This may take a moment..." />
            </>
        );
    }

    return (
        <>
            <Head title="Timetable Test" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white sm:rounded-lg">
                        <div className="bg-white p-6">
                            <div className="mb-8 flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">
                                        Academic Timetable
                                    </h1>
                                    {lastComputedAt && (
                                        <p className="mt-1 text-sm text-gray-500">
                                            Last generated:{' '}
                                            {lastComputedAt.toLocaleTimeString()}
                                        </p>
                                    )}
                                </div>
                                <div className="flex space-x-4">
                                    <Button
                                        onClick={handleCompute}
                                        disabled={loading}
                                        variant="default"
                                        className="rounded-md bg-indigo-600 px-5 py-2 font-medium text-white shadow-sm hover:bg-indigo-700"
                                    >
                                        {loading
                                            ? 'Generating...'
                                            : 'Generate New Timetable'}
                                    </Button>
                                </div>
                            </div>

                            <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <Card className="rounded-xl border border-gray-200 bg-white p-6 shadow-none">
                                    <h2 className="mb-4 flex items-center text-xl font-bold text-gray-800">
                                        <div className="mr-3 flex h-8 w-8 items-center justify-center rounded-xl bg-gray-200">
                                            <svg
                                                className="h-4 w-4 text-gray-700"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                                />
                                            </svg>
                                        </div>
                                        Performance Metrics
                                    </h2>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mb-2 flex items-center">
                                                <div className="mr-2 h-3 w-3 rounded-full bg-blue-500"></div>
                                                <p className="text-sm font-semibold text-gray-700">
                                                    Execution Time
                                                </p>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">
                                                {timetableData?.execution_time ??
                                                    '-'}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mb-2 flex items-center">
                                                <div className="mr-2 h-3 w-3 rounded-full bg-emerald-500"></div>
                                                <p className="text-sm font-semibold text-gray-700">
                                                    Assignments
                                                </p>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">
                                                {timetableData?.assignments ??
                                                    '-'}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mb-2 flex items-center">
                                                <div className="mr-2 h-3 w-3 rounded-full bg-purple-500"></div>
                                                <p className="text-sm font-semibold text-gray-700">
                                                    Backtracks
                                                </p>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">
                                                {timetableData?.statistics
                                                    ?.backtracks ?? '-'}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mb-2 flex items-center">
                                                <div className="mr-2 h-3 w-3 rounded-full bg-amber-500"></div>
                                                <p className="text-sm font-semibold text-gray-700">
                                                    Consistency Checks
                                                </p>
                                            </div>
                                            <p className="text-2xl font-bold text-gray-900">
                                                {timetableData?.statistics
                                                    ?.consistency_checks ?? '-'}
                                            </p>
                                        </div>
                                    </div>
                                </Card>
                                <Card className="rounded-xl border border-gray-200 bg-white p-6 shadow-none">
                                    <h2 className="mb-4 flex items-center text-xl font-bold text-gray-800">
                                        <div className="mr-3 flex h-8 w-8 items-center justify-center rounded-xl bg-gray-200">
                                            <svg
                                                className="h-4 w-4 text-gray-700"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                        Session Types
                                    </h2>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mr-3 flex h-6 w-6 items-center justify-center rounded-xl bg-blue-500">
                                                <div className="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <span className="text-sm font-bold text-gray-800">
                                                Lecture
                                            </span>
                                        </div>
                                        <div className="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mr-3 flex h-6 w-6 items-center justify-center rounded-xl bg-green-500">
                                                <div className="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <span className="text-sm font-bold text-gray-800">
                                                Lab
                                            </span>
                                        </div>
                                        <div className="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mr-3 flex h-6 w-6 items-center justify-center rounded-xl bg-purple-500">
                                                <div className="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <span className="text-sm font-bold text-gray-800">
                                                Tutorial
                                            </span>
                                        </div>
                                        <div className="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <div className="mr-3 flex h-6 w-6 items-center justify-center rounded-xl bg-gray-400">
                                                <div className="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <span className="text-sm font-bold text-gray-800">
                                                Available
                                            </span>
                                        </div>
                                    </div>
                                </Card>
                            </div>

                            <div className="overflow-x-auto rounded-xl border border-gray-200">
                                <table className="min-w-full rounded-xl bg-white">
                                    <thead>
                                        <tr>
                                            <th className="sticky left-0 z-10 border-b border-gray-200 bg-gray-100 px-5 py-4 text-left text-xs font-bold tracking-wider text-gray-700 uppercase">
                                                Time / Day
                                            </th>
                                            {daysOfWeek.map((day) => (
                                                <th
                                                    key={day}
                                                    className="border-b border-l border-gray-200 bg-gray-100 px-5 py-4 text-left text-xs font-bold tracking-wider text-gray-700 uppercase"
                                                >
                                                    {day}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {timeSlots.map((timeSlot, index) => (
                                            <tr
                                                key={timeSlot}
                                                className={
                                                    index % 2 === 0
                                                        ? 'bg-white'
                                                        : 'bg-gray-50'
                                                }
                                            >
                                                <td className="sticky left-0 z-10 border-r border-gray-200 bg-gray-100 px-5 py-4 text-sm font-semibold text-gray-900">
                                                    {timeSlot}
                                                </td>
                                                {daysOfWeek.map((day) => {
                                                    const courses =
                                                        getCoursesForSlot(
                                                            day,
                                                            timeSlot,
                                                        );
                                                    return (
                                                        <td
                                                            key={`${day}-${timeSlot}`}
                                                            className="border-r border-gray-200 px-4 py-3 text-sm"
                                                        >
                                                            <TimetableCell
                                                                courses={
                                                                    courses
                                                                }
                                                            />
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default TimetableTest;
