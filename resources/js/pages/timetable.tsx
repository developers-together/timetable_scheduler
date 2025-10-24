import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Alert } from '@/components/ui/alert';
import { useTimetable, ComputationStatus } from '@/hooks/use-timetable';
import TimetableCell from '@/components/timetable-cell';
import WaitingPage from '@/components/waiting-page';
import { formatDistanceToNow } from 'date-fns';

const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
const timeSlots = ['09:00-10:30', '10:45-12:15', '12:30-14:00', '14:15-15:45', '16:00-17:30'];

const Timetable = () => {
  const [showWaitingPage, setShowWaitingPage] = useState(false);
  const {
    timetableData,
    loading,
    error,
    computationStatus,
    lastComputedAt,
    fetchTimetable,
    generateTimetable
  } = useTimetable();

-  // Function to get color based on course ID (for visual distinction)
-  const getCourseColor = (courseId: string) => {
-    const colors = [
-      'bg-blue-100 border-blue-300 text-blue-800',
-      'bg-green-100 border-green-300 text-green-800',
-      'bg-purple-100 border-purple-300 text-purple-800',
-      'bg-yellow-100 border-yellow-300 text-yellow-800',
-      'bg-pink-100 border-pink-300 text-pink-800',
-      'bg-indigo-100 border-indigo-300 text-indigo-800',
-      'bg-red-100 border-red-300 text-red-800',
-      'bg-orange-100 border-orange-300 text-orange-800',
-      'bg-teal-100 border-teal-300 text-teal-800',
-      'bg-cyan-100 border-cyan-300 text-cyan-800',
-    ];
-
-    // Simple hash function to assign consistent colors
-    const hash = courseId.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
-    return colors[hash % colors.length];
-  };

  // Function to handle compute button click
  const handleCompute = () => {
    setShowWaitingPage(true);
    generateTimetable().finally(() => {
      setShowWaitingPage(false);
    });
  };

  // Function to handle refresh button click
  const handleRefresh = () => {
    setShowWaitingPage(true);
    fetchTimetable().finally(() => {
      setShowWaitingPage(false);
    });
  };

  // Function to get all courses scheduled for a specific day and time
  const getCoursesForSlot = (day: string, timeSlot: string) => {
    if (!timetableData || !timetableData.data) return [];

    const courses = [];
    for (const [courseId, types] of Object.entries(timetableData.data)) {
      for (const [type, details] of Object.entries(types)) {
        const [slotDay, slotTime] = details.slot.split('-');
        if (slotDay === day && slotTime === timeSlot.split('-')[0]) {
          courses.push({
            courseId,
            type,
            roomId: details.room_id,
            instructorId: details.instructor_id,
          });
        }
      }
    }
    return courses;
  };

  // If waiting page is shown, render it instead of the timetable
  if (showWaitingPage) {
    return (
      <>
        <Head title="Computing Timetable" />
        <WaitingPage description="Computing timetable data. This may take a moment..." />
      </>
    );
  }

  return (
    <>
      <Head title="Timetable" />
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden sm:rounded-lg">
            <div className="p-6 bg-white">
              <div className="flex justify-between items-center mb-6">
                <div>
                  <h1 className="text-2xl font-semibold text-gray-800">Course Timetable</h1>
                  {lastComputedAt && (
                    <p className="text-sm text-gray-500 mt-1">
                      Last computed: {formatDistanceToNow(lastComputedAt, { addSuffix: true })}
                    </p>
                  )}
                </div>
                <div className="flex space-x-4">
                  <Button onClick={handleRefresh} disabled={loading || computationStatus === ComputationStatus.COMPUTING}>
                    Refresh Data
                  </Button>
                  <Button
                    onClick={handleCompute}
                    disabled={loading || computationStatus === ComputationStatus.COMPUTING}
                    variant="default"
                  >
                    Generate New Timetable
                  </Button>
                </div>
              </div>

              {error && (
                <Alert variant="destructive" className="mb-4">
                  {error}
                </Alert>
              )}

              {loading || computationStatus === ComputationStatus.COMPUTING ? (
                <div className="flex justify-center items-center h-64">
                  <Spinner className="h-12 w-12" />
                  <span className="ml-3 text-lg text-gray-600">Loading timetable...</span>
                </div>
              ) : timetableData && timetableData.success ? (
                <div>
                  <div className="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card className="p-4">
                      <h2 className="text-lg font-medium mb-2 text-gray-800 flex items-center">
                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Timetable Statistics
                      </h2>
                      <div className="grid grid-cols-2 gap-3">
                        <div className="bg-blue-50 p-3 rounded-lg border border-blue-100">
                          <p className="text-sm text-blue-600 font-medium">Execution Time</p>
                          <p className="text-lg font-semibold text-blue-800">{timetableData.execution_time}</p>
                        </div>
                        <div className="bg-green-50 p-3 rounded-lg border border-green-100">
                          <p className="text-sm text-green-600 font-medium">Assignments</p>
                          <p className="text-lg font-semibold text-green-800">{timetableData.assignments}</p>
                        </div>
                        <div className="bg-purple-50 p-3 rounded-lg border border-purple-100">
                          <p className="text-sm text-purple-600 font-medium">Backtracks</p>
                          <p className="text-lg font-semibold text-purple-800">{timetableData.statistics.backtracks}</p>
                        </div>
                        <div className="bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                          <p className="text-sm text-yellow-600 font-medium">Consistency Checks</p>
                          <p className="text-lg font-semibold text-yellow-800">{timetableData.statistics.consistency_checks}</p>
                        </div>
                      </div>
                    </Card>
                    <Card className="p-4">
                      <h2 className="text-lg font-medium mb-2 text-gray-800 flex items-center">
                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Legend
                      </h2>
                      <div className="grid grid-cols-2 gap-3">
                        <div className="flex items-center p-2 bg-blue-50 rounded-lg">
                          <div className="w-4 h-4 bg-blue-100 border border-blue-300 rounded mr-2"></div>
                          <span className="text-sm font-medium text-blue-800">Lecture</span>
                        </div>
                        <div className="flex items-center p-2 bg-green-50 rounded-lg">
                          <div className="w-4 h-4 bg-green-100 border border-green-300 rounded mr-2"></div>
                          <span className="text-sm font-medium text-green-800">Lab</span>
                        </div>
                        <div className="flex items-center p-2 bg-purple-50 rounded-lg">
                          <div className="w-4 h-4 bg-purple-100 border border-purple-300 rounded mr-2"></div>
                          <span className="text-sm font-medium text-purple-800">Tutorial</span>
                        </div>
                      </div>
                    </Card>
                  </div>

                  <div className="overflow-x-auto rounded-lg shdow-md">
                    <table className="min-w-full bg-white border border-gray-200 rounded-lg">
                      <thead>
                        <tr>
                          <th className="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b sticky left-0 z-10">
                            Time / Day
                          </th>
                          {daysOfWeek.map((day) => (
                            <th
                              key={day}
                              className="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider border-b border-l"
                            >
                              {day}
                            </th>
                          ))}
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-200">
                        {timeSlots.map((timeSlot, index) => (
                          <tr key={timeSlot} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                            <td className="py-3 px-4 text-sm font-medium text-gray-900 bg-gray-100 border-r sticky left-0 z-10">
                              {timeSlot}
                            </td>
                            {daysOfWeek.map((day) => {
                              const courses = getCoursesForSlot(day, timeSlot);
                              return (
                                <td
                                  key={`${day}-${timeSlot}`}
                                  className="py-2 px-3 border-r text-sm"
                                >
                                  <TimetableCell courses={courses} />
                                </td>
                              );
                            })}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              ) : (
                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                  <p className="text-yellow-700">
                    No timetable data available. Click "Generate New Timetable" to create one.
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Timetable;
