import React, { useState, useEffect } from 'react';
import { Clock } from 'lucide-react';

interface CountdownTimerProps {
  endDate: string | Date;
  onExpire?: () => void;
  showDays?: boolean;
  className?: string;
}

export default function CountdownTimer({ 
  endDate, 
  onExpire,
  showDays = true,
  className = ''
}: CountdownTimerProps) {
  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());

  function calculateTimeLeft() {
    const difference = +new Date(endDate) - +new Date();
    
    if (difference > 0) {
      return {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60),
      };
    }
    
    return { days: 0, hours: 0, minutes: 0, seconds: 0 };
  }

  useEffect(() => {
    const timer = setInterval(() => {
      const newTimeLeft = calculateTimeLeft();
      setTimeLeft(newTimeLeft);

      if (newTimeLeft.days === 0 && newTimeLeft.hours === 0 && 
          newTimeLeft.minutes === 0 && newTimeLeft.seconds === 0) {
        if (onExpire) onExpire();
        clearInterval(timer);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [endDate, onExpire]);

  const isExpired = timeLeft.days === 0 && timeLeft.hours === 0 && 
                    timeLeft.minutes === 0 && timeLeft.seconds === 0;

  if (isExpired) {
    return (
      <div className={`text-red-500 font-semibold ${className}`}>
        EXPIRED
      </div>
    );
  }

  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <Clock className="w-4 h-4 text-orange-500" />
      <div className="flex items-center gap-1 font-mono font-bold">
        {showDays && timeLeft.days > 0 && (
          <>
            <div className="flex flex-col items-center bg-gray-900 text-white px-2 py-1 rounded">
              <span className="text-xl">{String(timeLeft.days).padStart(2, '0')}</span>
              <span className="text-xs">DAYS</span>
            </div>
            <span className="text-xl">:</span>
          </>
        )}
        <div className="flex flex-col items-center bg-gray-900 text-white px-2 py-1 rounded">
          <span className="text-xl">{String(timeLeft.hours).padStart(2, '0')}</span>
          <span className="text-xs">HRS</span>
        </div>
        <span className="text-xl">:</span>
        <div className="flex flex-col items-center bg-gray-900 text-white px-2 py-1 rounded">
          <span className="text-xl">{String(timeLeft.minutes).padStart(2, '0')}</span>
          <span className="text-xs">MIN</span>
        </div>
        <span className="text-xl">:</span>
        <div className="flex flex-col items-center bg-gray-900 text-white px-2 py-1 rounded">
          <span className="text-xl">{String(timeLeft.seconds).padStart(2, '0')}</span>
          <span className="text-xs">SEC</span>
        </div>
      </div>
    </div>
  );
}
