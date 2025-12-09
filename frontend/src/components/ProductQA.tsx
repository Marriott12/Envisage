import React, { useState, useEffect } from 'react';
import { ThumbsUp, MessageCircle, CheckCircle, ChevronDown, ChevronUp } from 'lucide-react';

interface Question {
  id: number;
  question: string;
  upvotes_count: number;
  user: {
    id: number;
    name: string;
  };
  answers: Answer[];
  created_at: string;
  has_upvoted?: boolean;
}

interface Answer {
  id: number;
  answer: string;
  is_seller: boolean;
  is_helpful: boolean;
  helpful_count: number;
  user: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface ProductQAProps {
  productId: number;
  userId?: number;
  apiToken?: string;
  isSeller?: boolean;
}

export default function ProductQA({ productId, userId, apiToken, isSeller = false }: ProductQAProps) {
  const [questions, setQuestions] = useState<Question[]>([]);
  const [newQuestion, setNewQuestion] = useState('');
  const [expandedQuestions, setExpandedQuestions] = useState<Set<number>>(new Set());
  const [answerInputs, setAnswerInputs] = useState<{ [key: number]: string }>({});
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchQuestions();
  }, [productId]);

  const fetchQuestions = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/products/${productId}/questions`);
      const data = await response.json();
      if (data.success) {
        setQuestions(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch questions:', error);
    } finally {
      setLoading(false);
    }
  };

  const submitQuestion = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newQuestion.trim() || !userId || !apiToken) return;

    setSubmitting(true);
    try {
      const response = await fetch(`${API_BASE}/products/${productId}/questions`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ question: newQuestion }),
      });
      const data = await response.json();
      if (data.success) {
        setQuestions([data.data, ...questions]);
        setNewQuestion('');
      }
    } catch (error) {
      console.error('Failed to submit question:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const submitAnswer = async (questionId: number) => {
    const answerText = answerInputs[questionId];
    if (!answerText?.trim() || !userId || !apiToken) return;

    try {
      const response = await fetch(`${API_BASE}/products/${productId}/questions/${questionId}/answer`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ answer: answerText }),
      });
      const data = await response.json();
      if (data.success) {
        setQuestions(questions.map(q =>
          q.id === questionId ? { ...q, answers: [...q.answers, data.data] } : q
        ));
        setAnswerInputs({ ...answerInputs, [questionId]: '' });
      }
    } catch (error) {
      console.error('Failed to submit answer:', error);
    }
  };

  const toggleUpvote = async (questionId: number) => {
    if (!userId || !apiToken) return;

    try {
      const response = await fetch(`${API_BASE}/products/${productId}/questions/${questionId}/upvote`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setQuestions(questions.map(q =>
          q.id === questionId
            ? { ...q, upvotes_count: data.upvotes_count, has_upvoted: data.upvoted }
            : q
        ));
      }
    } catch (error) {
      console.error('Failed to toggle upvote:', error);
    }
  };

  const markHelpful = async (questionId: number, answerId: number) => {
    if (!userId || !apiToken) return;

    try {
      const response = await fetch(
        `${API_BASE}/products/${productId}/questions/${questionId}/answers/${answerId}/helpful`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${apiToken}`,
            'Accept': 'application/json',
          },
        }
      );
      const data = await response.json();
      if (data.success) {
        setQuestions(questions.map(q =>
          q.id === questionId
            ? {
                ...q,
                answers: q.answers.map(a =>
                  a.id === answerId ? { ...a, is_helpful: true, helpful_count: a.helpful_count + 1 } : a
                ),
              }
            : q
        ));
      }
    } catch (error) {
      console.error('Failed to mark helpful:', error);
    }
  };

  const toggleExpanded = (questionId: number) => {
    const newExpanded = new Set(expandedQuestions);
    if (newExpanded.has(questionId)) {
      newExpanded.delete(questionId);
    } else {
      newExpanded.add(questionId);
    }
    setExpandedQuestions(newExpanded);
  };

  if (loading) {
    return (
      <div className="text-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
        <p className="mt-4 text-gray-600">Loading questions...</p>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto">
      <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <h2 className="text-2xl font-bold mb-4 flex items-center gap-2">
          <MessageCircle className="text-purple-600" size={28} />
          Questions & Answers
        </h2>

        {/* Ask Question Form */}
        {userId && !isSeller && (
          <form onSubmit={submitQuestion} className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Have a question about this product?
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                value={newQuestion}
                onChange={(e) => setNewQuestion(e.target.value)}
                placeholder="Ask your question here..."
                className="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                maxLength={500}
              />
              <button
                type="submit"
                disabled={submitting || !newQuestion.trim()}
                className="bg-gradient-to-br from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg hover:opacity-90 disabled:opacity-50 font-medium"
              >
                {submitting ? 'Posting...' : 'Ask'}
              </button>
            </div>
            <p className="text-xs text-gray-500 mt-1">
              {newQuestion.length}/500 characters
            </p>
          </form>
        )}

        {/* Questions List */}
        <div className="space-y-4">
          {questions.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <MessageCircle size={48} className="mx-auto mb-3 text-gray-300" />
              <p className="text-lg font-medium">No questions yet</p>
              <p className="text-sm">Be the first to ask about this product!</p>
            </div>
          ) : (
            questions.map((question) => (
              <div key={question.id} className="border rounded-lg p-4 hover:bg-gray-50 transition">
                <div className="flex gap-4">
                  {/* Upvote Column */}
                  <div className="flex flex-col items-center">
                    <button
                      onClick={() => toggleUpvote(question.id)}
                      disabled={!userId || !apiToken}
                      className={`p-2 rounded-lg transition ${
                        question.has_upvoted
                          ? 'text-purple-600 bg-purple-100'
                          : 'text-gray-600 hover:bg-gray-100'
                      } disabled:opacity-50`}
                    >
                      <ThumbsUp size={20} />
                    </button>
                    <span className="text-sm font-medium text-gray-700 mt-1">
                      {question.upvotes_count}
                    </span>
                  </div>

                  {/* Question Content */}
                  <div className="flex-1">
                    <div className="flex items-start justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{question.question}</p>
                        <p className="text-sm text-gray-500 mt-1">
                          Asked by {question.user.name} •{' '}
                          {new Date(question.created_at).toLocaleDateString()}
                        </p>
                      </div>
                      <button
                        onClick={() => toggleExpanded(question.id)}
                        className="text-gray-500 hover:text-gray-700"
                      >
                        {expandedQuestions.has(question.id) ? (
                          <ChevronUp size={20} />
                        ) : (
                          <ChevronDown size={20} />
                        )}
                      </button>
                    </div>

                    {/* Answers */}
                    {expandedQuestions.has(question.id) && (
                      <div className="mt-4 space-y-3">
                        {question.answers.length === 0 ? (
                          <p className="text-sm text-gray-500 italic">
                            No answers yet. Be the first to answer!
                          </p>
                        ) : (
                          question.answers.map((answer) => (
                            <div
                              key={answer.id}
                              className={`pl-4 border-l-2 ${
                                answer.is_seller ? 'border-purple-500 bg-purple-50' : 'border-gray-300'
                              } p-3 rounded`}
                            >
                              <div className="flex items-start justify-between">
                                <div className="flex-1">
                                  <p className="text-gray-800">{answer.answer}</p>
                                  <div className="flex items-center gap-3 mt-2">
                                    <p className="text-sm text-gray-600">
                                      {answer.user.name}
                                      {answer.is_seller && (
                                        <span className="ml-2 bg-purple-500 text-white text-xs px-2 py-1 rounded">
                                          Seller
                                        </span>
                                      )}
                                    </p>
                                    <span className="text-sm text-gray-500">
                                      {new Date(answer.created_at).toLocaleDateString()}
                                    </span>
                                  </div>
                                </div>
                                {userId && userId === question.user.id && !answer.is_helpful && (
                                  <button
                                    onClick={() => markHelpful(question.id, answer.id)}
                                    className="text-sm text-purple-600 hover:text-purple-700 flex items-center gap-1"
                                  >
                                    <CheckCircle size={16} />
                                    Helpful
                                  </button>
                                )}
                                {answer.is_helpful && (
                                  <span className="text-sm text-green-600 flex items-center gap-1">
                                    <CheckCircle size={16} />
                                    Marked Helpful
                                  </span>
                                )}
                              </div>
                            </div>
                          ))
                        )}

                        {/* Answer Input */}
                        {userId && apiToken && (
                          <div className="mt-3">
                            <div className="flex gap-2">
                              <input
                                type="text"
                                value={answerInputs[question.id] || ''}
                                onChange={(e) =>
                                  setAnswerInputs({ ...answerInputs, [question.id]: e.target.value })
                                }
                                placeholder="Write your answer..."
                                className="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                              />
                              <button
                                onClick={() => submitAnswer(question.id)}
                                disabled={!answerInputs[question.id]?.trim()}
                                className="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 disabled:opacity-50"
                              >
                                Answer
                              </button>
                            </div>
                          </div>
                        )}
                      </div>
                    )}

                    {/* Quick summary when collapsed */}
                    {!expandedQuestions.has(question.id) && question.answers.length > 0 && (
                      <p className="text-sm text-purple-600 mt-2">
                        {question.answers.length} answer{question.answers.length !== 1 ? 's' : ''}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
