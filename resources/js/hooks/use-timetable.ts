import { useState, useEffect } from 'react';
import axios from 'axios';

export interface TimetableData {
  success: boolean;
  message: string;
  execution_time: string;
  statistics: {
    backtracks: number;
    consistency_checks: number;
    forward_checks: number;
  };
  assignments: number;
  data: {
    [courseId: string]: {
      [type: string]: {
        slot: string;
        room_id: string;
        instructor_id: string | null;
      };
    };
  };
}

export enum ComputationStatus {
  IDLE = 'idle',
  COMPUTING = 'computing',
  COMPLETED = 'completed',
  ERROR = 'error'
}

export function useTimetable() {
  const [timetableData, setTimetableData] = useState<TimetableData | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [computationStatus, setComputationStatus] = useState<ComputationStatus>(ComputationStatus.IDLE);
  const [lastComputedAt, setLastComputedAt] = useState<Date | null>(null);

  // Load cached data from localStorage on initial load
  useEffect(() => {
    const cachedData = localStorage.getItem('timetableData');
    const cachedTimestamp = localStorage.getItem('timetableLastComputed');
    
    if (cachedData) {
      try {
        const parsedData = JSON.parse(cachedData);
        setTimetableData(parsedData);
        
        if (cachedTimestamp) {
          setLastComputedAt(new Date(cachedTimestamp));
        }
        
        setComputationStatus(ComputationStatus.COMPLETED);
      } catch (err) {
        console.error('Failed to parse cached timetable data', err);
        // If cache is corrupted, fetch fresh data
        fetchTimetable();
      }
    } else {
      // No cached data, fetch fresh data
      fetchTimetable();
    }
  }, []);

  // Save data to localStorage whenever it changes
  useEffect(() => {
    if (timetableData && computationStatus === ComputationStatus.COMPLETED) {
      localStorage.setItem('timetableData', JSON.stringify(timetableData));
      const now = new Date();
      localStorage.setItem('timetableLastComputed', now.toISOString());
      setLastComputedAt(now);
    }
  }, [timetableData, computationStatus]);

  const fetchTimetable = async () => {
    setLoading(true);
    setError(null);
    setComputationStatus(ComputationStatus.COMPUTING);
    
    try {
      const response = await axios.get('/getAssignment');
      setTimetableData(response.data);
      setComputationStatus(ComputationStatus.COMPLETED);
    } catch (err) {
      setError('Failed to load timetable data. Please try again.');
      setComputationStatus(ComputationStatus.ERROR);
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const generateTimetable = async () => {
    setLoading(true);
    setError(null);
    setComputationStatus(ComputationStatus.COMPUTING);
    
    try {
      const response = await axios.get('/generate-timetable');
      setTimetableData(response.data);
      setComputationStatus(ComputationStatus.COMPLETED);
    } catch (err) {
      setError('Failed to generate timetable. Please try again.');
      setComputationStatus(ComputationStatus.ERROR);
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return {
    timetableData,
    loading,
    error,
    computationStatus,
    lastComputedAt,
    fetchTimetable,
    generateTimetable
  };
}