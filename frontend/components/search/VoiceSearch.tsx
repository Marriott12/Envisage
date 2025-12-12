'use client';

import { useState, useEffect, useRef } from 'react';
import { Mic, MicOff, Loader2 } from 'lucide-react';

interface VoiceSearchProps {
  onResult: (transcript: string) => void;
  onError?: (error: string) => void;
  language?: string;
  className?: string;
}

export function VoiceSearch({
  onResult,
  onError,
  language = 'en-US',
  className = '',
}: VoiceSearchProps) {
  const [isListening, setIsListening] = useState(false);
  const [isSupported, setIsSupported] = useState(false);
  const [transcript, setTranscript] = useState('');
  const recognitionRef = useRef<any>(null);

  useEffect(() => {
    // Check if Web Speech API is supported
    if (typeof window !== 'undefined') {
      const SpeechRecognition =
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition;

      if (SpeechRecognition) {
        setIsSupported(true);
        recognitionRef.current = new SpeechRecognition();
        recognitionRef.current.continuous = false;
        recognitionRef.current.interimResults = true;
        recognitionRef.current.lang = language;

        recognitionRef.current.onstart = () => {
          setIsListening(true);
        };

        recognitionRef.current.onresult = (event: any) => {
          const currentTranscript = Array.from(event.results)
            .map((result: any) => result[0].transcript)
            .join('');
          setTranscript(currentTranscript);

          // If final result, trigger callback
          if (event.results[event.results.length - 1].isFinal) {
            onResult(currentTranscript);
            setIsListening(false);
            setTranscript('');
          }
        };

        recognitionRef.current.onerror = (event: any) => {
          const errorMessage = getErrorMessage(event.error);
          onError?.(errorMessage);
          setIsListening(false);
          setTranscript('');
        };

        recognitionRef.current.onend = () => {
          setIsListening(false);
        };
      }
    }

    return () => {
      if (recognitionRef.current) {
        recognitionRef.current.stop();
      }
    };
  }, [language, onResult, onError]);

  const getErrorMessage = (error: string): string => {
    switch (error) {
      case 'no-speech':
        return 'No speech was detected. Please try again.';
      case 'audio-capture':
        return 'No microphone was found. Please check your microphone settings.';
      case 'not-allowed':
        return 'Microphone access was denied. Please allow microphone access.';
      case 'network':
        return 'Network error occurred. Please check your internet connection.';
      default:
        return 'An error occurred. Please try again.';
    }
  };

  const startListening = () => {
    if (recognitionRef.current && !isListening) {
      try {
        recognitionRef.current.start();
      } catch (error) {
        console.error('Error starting speech recognition:', error);
        onError?.('Failed to start voice recognition. Please try again.');
      }
    }
  };

  const stopListening = () => {
    if (recognitionRef.current && isListening) {
      recognitionRef.current.stop();
    }
  };

  const toggleListening = () => {
    if (isListening) {
      stopListening();
    } else {
      startListening();
    }
  };

  if (!isSupported) {
    return (
      <div className={`text-center p-4 ${className}`}>
        <MicOff className="w-8 h-8 text-gray-400 mx-auto mb-2" />
        <p className="text-sm text-gray-600">
          Voice search is not supported in your browser.
        </p>
        <p className="text-xs text-gray-500 mt-1">
          Try using Chrome, Edge, or Safari.
        </p>
      </div>
    );
  }

  return (
    <div className={className}>
      <button
        onClick={toggleListening}
        disabled={!isSupported}
        className={`relative p-3 rounded-full transition-all ${
          isListening
            ? 'bg-red-500 text-white hover:bg-red-600 animate-pulse'
            : 'bg-blue-500 text-white hover:bg-blue-600'
        } disabled:opacity-50 disabled:cursor-not-allowed`}
        aria-label={isListening ? 'Stop voice search' : 'Start voice search'}
      >
        {isListening ? (
          <MicOff className="w-5 h-5" />
        ) : (
          <Mic className="w-5 h-5" />
        )}
      </button>

      {transcript && (
        <div className="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
          <p className="text-sm text-gray-700">{transcript}</p>
          <div className="flex items-center gap-1 mt-1">
            <Loader2 className="w-3 h-3 text-blue-500 animate-spin" />
            <span className="text-xs text-gray-500">Listening...</span>
          </div>
        </div>
      )}
    </div>
  );
}

// Inline Voice Search Button (for search bars)
export function VoiceSearchButton({
  onResult,
  onError,
  language = 'en-US',
  className = '',
}: VoiceSearchProps) {
  const [isListening, setIsListening] = useState(false);
  const [isSupported, setIsSupported] = useState(false);
  const recognitionRef = useRef<any>(null);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const SpeechRecognition =
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition;

      if (SpeechRecognition) {
        setIsSupported(true);
        recognitionRef.current = new SpeechRecognition();
        recognitionRef.current.continuous = false;
        recognitionRef.current.interimResults = false;
        recognitionRef.current.lang = language;

        recognitionRef.current.onstart = () => setIsListening(true);
        
        recognitionRef.current.onresult = (event: any) => {
          const transcript = event.results[0][0].transcript;
          onResult(transcript);
        };

        recognitionRef.current.onerror = (event: any) => {
          onError?.(event.error);
          setIsListening(false);
        };

        recognitionRef.current.onend = () => setIsListening(false);
      }
    }

    return () => {
      if (recognitionRef.current) {
        recognitionRef.current.stop();
      }
    };
  }, [language, onResult, onError]);

  const handleClick = () => {
    if (!isSupported) return;
    
    if (isListening) {
      recognitionRef.current?.stop();
    } else {
      try {
        recognitionRef.current?.start();
      } catch (error) {
        console.error('Voice search error:', error);
      }
    }
  };

  if (!isSupported) return null;

  return (
    <button
      type="button"
      onClick={handleClick}
      className={`p-2 hover:bg-gray-100 rounded-lg transition-colors ${
        isListening ? 'text-red-500 animate-pulse' : 'text-gray-500'
      } ${className}`}
      aria-label="Voice search"
      title="Voice search"
    >
      {isListening ? <MicOff className="w-5 h-5" /> : <Mic className="w-5 h-5" />}
    </button>
  );
}
