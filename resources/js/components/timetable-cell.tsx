import React from 'react';

interface CourseSlot {
  courseId: string;
  type: string;
  roomId: string;
  instructorId: string | null;
}

interface TimetableCellProps {
  courses: CourseSlot[];
}

const TimetableCell: React.FC<TimetableCellProps> = ({ courses }) => {
  // Simple solid color styles based on session type
  const getTypeStyles = (type: string) => {
    switch (type) {
      case 'Lecture':
        return 'bg-blue-100 border-blue-300 text-blue-800 hover:bg-blue-200';
      case 'Lab':
        return 'bg-green-100 border-green-300 text-green-800 hover:bg-green-200';
      case 'Tutorial':
        return 'bg-purple-100 border-purple-300 text-purple-800 hover:bg-purple-200';
      default:
        return 'bg-gray-100 border-gray-300 text-gray-800 hover:bg-gray-200';
    }
  };

  // Remove gradients for empty cells and keep a simple look
  if (courses.length === 0) {
    return (
      <div className="h-full min-h-[60px] bg-gray-50 rounded-lg border border-dashed border-gray-300 flex items-center justify-center">
        <span className="text-gray-400 text-xs font-medium">Available</span>
      </div>
    );
  }

  return (
    <div className="grid gap-2">
      {courses.map((course, idx) => (
        <div
          key={`${course.courseId}-${course.type}-${idx}`}
          className={`relative p-3 rounded-lg border ${getTypeStyles(course.type)} text-xs shadow-none transition-colors`}
        >
          <div className="font-bold text-sm mb-1">{course.courseId}</div>
          <div className="font-semibold text-xs mb-2 opacity-90">{course.type}</div>

          <div className="space-y-1">
            <div className="text-xs opacity-75 flex items-center">
              <svg className="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span className="truncate">{course.roomId}</span>
            </div>
            {course.instructorId && (
              <div className="text-xs opacity-75 flex items-center">
                <svg className="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span className="truncate">{course.instructorId}</span>
              </div>
            )}
          </div>
        </div>
      ))}
    </div>
  );
};

export default TimetableCell;