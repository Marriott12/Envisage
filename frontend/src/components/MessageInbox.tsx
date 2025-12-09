import React, { useState, useEffect, useRef } from 'react';
import { Send, Paperclip, X, Search, ChevronLeft } from 'lucide-react';

interface Message {
  id: number;
  message: string;
  sender_id: number;
  sender: {
    id: number;
    name: string;
  };
  attachments: string | null;
  is_read: boolean;
  created_at: string;
}

interface Conversation {
  id: number;
  buyer: {
    id: number;
    name: string;
  };
  seller: {
    id: number;
    name: string;
  };
  product?: {
    id: number;
    name: string;
    price: number;
    images: string;
  };
  latest_message?: Message;
  unread_count: number;
  created_at: string;
}

interface MessageInboxProps {
  userId: number;
  userRole: 'buyer' | 'seller';
  apiToken: string;
}

export default function MessageInbox({ userId, userRole, apiToken }: MessageInboxProps) {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [newMessage, setNewMessage] = useState('');
  const [attachments, setAttachments] = useState<File[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  // Fetch conversations
  useEffect(() => {
    fetchConversations();
  }, []);

  // Fetch messages when conversation is selected
  useEffect(() => {
    if (selectedConversation) {
      fetchMessages(selectedConversation.id);
      markAsRead(selectedConversation.id);
    }
  }, [selectedConversation]);

  // Auto-scroll to bottom on new messages
  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const fetchConversations = async () => {
    try {
      const response = await fetch(`${API_BASE}/messages/conversations`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setConversations(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch conversations:', error);
    }
  };

  const fetchMessages = async (conversationId: number) => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE}/messages/conversations/${conversationId}`, {
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      const data = await response.json();
      if (data.success) {
        setMessages(data.data.messages);
      }
    } catch (error) {
      console.error('Failed to fetch messages:', error);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async (conversationId: number) => {
    try {
      await fetch(`${API_BASE}/messages/conversations/${conversationId}/read`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Accept': 'application/json',
        },
      });
      // Update local state
      setConversations(prevConvs =>
        prevConvs.map(conv =>
          conv.id === conversationId ? { ...conv, unread_count: 0 } : conv
        )
      );
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
  };

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newMessage.trim() && attachments.length === 0) return;
    if (!selectedConversation) return;

    setSending(true);
    const formData = new FormData();
    formData.append('message', newMessage);
    attachments.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file);
    });

    try {
      const response = await fetch(
        `${API_BASE}/messages/conversations/${selectedConversation.id}/send`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${apiToken}`,
            'Accept': 'application/json',
          },
          body: formData,
        }
      );
      const data = await response.json();
      if (data.success) {
        setMessages([...messages, data.data]);
        setNewMessage('');
        setAttachments([]);
        scrollToBottom();
      }
    } catch (error) {
      console.error('Failed to send message:', error);
    } finally {
      setSending(false);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files);
      if (attachments.length + newFiles.length > 5) {
        alert('Maximum 5 attachments allowed');
        return;
      }
      setAttachments([...attachments, ...newFiles]);
    }
  };

  const removeAttachment = (index: number) => {
    setAttachments(attachments.filter((_, i) => i !== index));
  };

  const filteredConversations = conversations.filter(conv => {
    const otherPerson = userRole === 'buyer' ? conv.seller : conv.buyer;
    return otherPerson.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
           conv.product?.name.toLowerCase().includes(searchQuery.toLowerCase());
  });

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Conversations List */}
      <div className={`w-full md:w-1/3 bg-white border-r ${selectedConversation ? 'hidden md:block' : ''}`}>
        <div className="p-4 border-b">
          <h2 className="text-xl font-bold mb-3">Messages</h2>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
            <input
              type="text"
              placeholder="Search conversations..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            />
          </div>
        </div>

        <div className="overflow-y-auto h-[calc(100vh-140px)]">
          {filteredConversations.map((conv) => {
            const otherPerson = userRole === 'buyer' ? conv.seller : conv.buyer;
            return (
              <div
                key={conv.id}
                onClick={() => setSelectedConversation(conv)}
                className={`p-4 border-b cursor-pointer hover:bg-gray-50 transition ${
                  selectedConversation?.id === conv.id ? 'bg-purple-50' : ''
                }`}
              >
                <div className="flex items-start gap-3">
                  <div className="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                    {otherPerson.name.charAt(0).toUpperCase()}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-start">
                      <h3 className="font-semibold truncate">{otherPerson.name}</h3>
                      {conv.unread_count > 0 && (
                        <span className="bg-purple-500 text-white text-xs rounded-full px-2 py-1 ml-2">
                          {conv.unread_count}
                        </span>
                      )}
                    </div>
                    {conv.product && (
                      <p className="text-sm text-gray-500 truncate">{conv.product.name}</p>
                    )}
                    {conv.latest_message && (
                      <p className="text-sm text-gray-600 truncate mt-1">
                        {conv.latest_message.message}
                      </p>
                    )}
                    <p className="text-xs text-gray-400 mt-1">
                      {new Date(conv.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              </div>
            );
          })}
          {filteredConversations.length === 0 && (
            <div className="text-center py-8 text-gray-500">
              No conversations found
            </div>
          )}
        </div>
      </div>

      {/* Messages Area */}
      <div className={`flex-1 flex flex-col ${!selectedConversation ? 'hidden md:flex' : ''}`}>
        {selectedConversation ? (
          <>
            {/* Header */}
            <div className="bg-white border-b p-4 flex items-center gap-3">
              <button
                onClick={() => setSelectedConversation(null)}
                className="md:hidden"
              >
                <ChevronLeft size={24} />
              </button>
              <div className="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                {(userRole === 'buyer' ? selectedConversation.seller : selectedConversation.buyer).name.charAt(0).toUpperCase()}
              </div>
              <div className="flex-1">
                <h3 className="font-semibold">
                  {(userRole === 'buyer' ? selectedConversation.seller : selectedConversation.buyer).name}
                </h3>
                {selectedConversation.product && (
                  <p className="text-sm text-gray-500">{selectedConversation.product.name}</p>
                )}
              </div>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
              {loading ? (
                <div className="text-center py-8">Loading messages...</div>
              ) : (
                messages.map((msg) => {
                  const isOwn = msg.sender_id === userId;
                  return (
                    <div key={msg.id} className={`flex ${isOwn ? 'justify-end' : 'justify-start'}`}>
                      <div className={`max-w-[70%] ${isOwn ? 'order-2' : 'order-1'}`}>
                        <div
                          className={`rounded-lg p-3 ${
                            isOwn
                              ? 'bg-gradient-to-br from-purple-500 to-pink-500 text-white'
                              : 'bg-white border'
                          }`}
                        >
                          <p className="text-sm">{msg.message}</p>
                          {msg.attachments && JSON.parse(msg.attachments).length > 0 && (
                            <div className="mt-2 space-y-1">
                              {JSON.parse(msg.attachments).map((att: any, idx: number) => (
                                <a
                                  key={idx}
                                  href={att.url}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className={`text-xs underline block ${isOwn ? 'text-white' : 'text-purple-600'}`}
                                >
                                  📎 {att.name}
                                </a>
                              ))}
                            </div>
                          )}
                        </div>
                        <p className={`text-xs text-gray-500 mt-1 ${isOwn ? 'text-right' : 'text-left'}`}>
                          {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </p>
                      </div>
                    </div>
                  );
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Input Area */}
            <div className="bg-white border-t p-4">
              {attachments.length > 0 && (
                <div className="mb-2 flex flex-wrap gap-2">
                  {attachments.map((file, idx) => (
                    <div key={idx} className="bg-gray-100 rounded px-3 py-1 text-sm flex items-center gap-2">
                      <span className="truncate max-w-[150px]">{file.name}</span>
                      <button onClick={() => removeAttachment(idx)}>
                        <X size={16} />
                      </button>
                    </div>
                  ))}
                </div>
              )}
              <form onSubmit={sendMessage} className="flex gap-2">
                <input
                  type="file"
                  ref={fileInputRef}
                  onChange={handleFileSelect}
                  multiple
                  className="hidden"
                  accept="image/*,.pdf,.doc,.docx"
                />
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                >
                  <Paperclip size={24} />
                </button>
                <input
                  type="text"
                  value={newMessage}
                  onChange={(e) => setNewMessage(e.target.value)}
                  placeholder="Type a message..."
                  className="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                />
                <button
                  type="submit"
                  disabled={sending || (!newMessage.trim() && attachments.length === 0)}
                  className="bg-gradient-to-br from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg hover:opacity-90 disabled:opacity-50 flex items-center gap-2"
                >
                  <Send size={20} />
                  Send
                </button>
              </form>
            </div>
          </>
        ) : (
          <div className="flex-1 flex items-center justify-center text-gray-500">
            <div className="text-center">
              <div className="text-6xl mb-4">💬</div>
              <p className="text-xl">Select a conversation to start messaging</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
