import React from "react";

interface WaitingPageProps {
  title?: string;
  description?: string;
}

export const WaitingPage: React.FC<WaitingPageProps> = ({
  title = "Preparing your timetable",
  description = "Computing timetable data. This may take a moment...",
}) => {
  return (
    <div className="flex min-h-screen w-full items-center justify-center bg-white px-4">
      <div className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6">
        {/* Spinner */}
        <div className="flex justify-center mb-4">
          <svg
            className="animate-spin h-8 w-8 text-blue-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              className="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            ></circle>
            <path
              className="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            ></path>
          </svg>
        </div>

        {/* Title */}
        <h2 className="text-center text-xl font-semibold text-gray-900">
          {title}
        </h2>

        {/* Description */}
        <p className="mt-2 text-center text-sm text-gray-600">{description}</p>

        {/* Progress bar */}
        <div className="mt-6">
          <div className="h-2 w-full rounded-full bg-gray-200">
            <div className="h-2 w-1/2 rounded-full bg-blue-600" />
          </div>
          <p className="mt-2 text-center text-xs text-gray-500">This won't take long.</p>
        </div>
      </div>
    </div>
  );
};

export default WaitingPage;
