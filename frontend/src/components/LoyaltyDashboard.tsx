import React, { useState, useEffect } from 'react';
import {
  Award,
  Gift,
  Users,
  TrendingUp,
  ShoppingBag,
  Copy,
  Check,
  Star,
} from 'lucide-react';

interface LoyaltyPoints {
  current_balance: number;
  lifetime_points: number;
  tier: 'bronze' | 'silver' | 'gold' | 'platinum' | 'diamond';
}

interface Transaction {
  id: number;
  points: number;
  type: 'earned' | 'redeemed' | 'expired';
  source: string;
  description: string;
  created_at: string;
}

interface Reward {
  id: number;
  name: string;
  description: string;
  points_required: number;
  type: 'discount' | 'shipping' | 'gift_card' | 'product';
  value: number;
  stock_quantity: number;
  is_active: boolean;
}

interface Referral {
  id: number;
  referred_user?: {
    name: string;
  };
  status: 'pending' | 'registered' | 'completed';
  points_awarded: number;
  created_at: string;
}

interface LoyaltyDashboardProps {
  userId: number;
  apiToken: string;
}

export default function LoyaltyDashboard({ userId, apiToken }: LoyaltyDashboardProps) {
  const [loyaltyPoints, setLoyaltyPoints] = useState<LoyaltyPoints | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [rewards, setRewards] = useState<Reward[]>([]);
  const [referralCode, setReferralCode] = useState('');
  const [referrals, setReferrals] = useState<Referral[]>([]);
  const [activeTab, setActiveTab] = useState<'overview' | 'rewards' | 'referrals' | 'history'>('overview');
  const [copied, setCopied] = useState(false);
  const [loading, setLoading] = useState(true);
  const [redeeming, setRedeeming] = useState<number | null>(null);

  const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    fetchLoyaltyData();
  }, []);

  const fetchLoyaltyData = async () => {
    setLoading(true);
    try {
      const [pointsRes, transactionsRes, rewardsRes, referralCodeRes, referralsRes] = await Promise.all([
        fetch(`${API_BASE}/loyalty/my-points`, {
          headers: { 'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json' },
        }),
        fetch(`${API_BASE}/loyalty/transactions`, {
          headers: { 'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json' },
        }),
        fetch(`${API_BASE}/loyalty/rewards-catalog`, {
          headers: { 'Accept': 'application/json' },
        }),
        fetch(`${API_BASE}/loyalty/referral-code`, {
          headers: { 'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json' },
        }),
        fetch(`${API_BASE}/loyalty/my-referrals`, {
          headers: { 'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json' },
        }),
      ]);

      const [pointsData, transactionsData, rewardsData, codeData, referralsData] = await Promise.all([
        pointsRes.json(),
        transactionsRes.json(),
        rewardsRes.json(),
        referralCodeRes.json(),
        referralsRes.json(),
      ]);

      if (pointsData.success) setLoyaltyPoints(pointsData.data);
      if (transactionsData.success) setTransactions(transactionsData.data);
      if (rewardsData.success) setRewards(rewardsData.data);
      if (codeData.success) setReferralCode(codeData.referral_code);
      if (referralsData.success) setReferrals(referralsData.data);
    } catch (error) {
      console.error('Failed to fetch loyalty data:', error);
    } finally {
      setLoading(false);
    }
  };

  const redeemReward = async (rewardId: number) => {
    if (!confirm('Are you sure you want to redeem this reward?')) return;

    setRedeeming(rewardId);
    try {
      const response = await fetch(`${API_BASE}/loyalty/redeem`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ reward_id: rewardId }),
      });
      const data = await response.json();
      if (data.success) {
        alert(`Reward redeemed successfully! Code: ${data.data.redemption_code}`);
        fetchLoyaltyData(); // Refresh data
      } else {
        alert(data.message || 'Failed to redeem reward');
      }
    } catch (error) {
      console.error('Failed to redeem reward:', error);
      alert('Failed to redeem reward. Please try again.');
    } finally {
      setRedeeming(null);
    }
  };

  const copyReferralCode = () => {
    navigator.clipboard.writeText(referralCode);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const getTierInfo = (tier: string) => {
    const tiers = {
      bronze: { name: 'Bronze', color: 'from-amber-700 to-amber-900', next: 'Silver', required: 500 },
      silver: { name: 'Silver', color: 'from-gray-400 to-gray-600', next: 'Gold', required: 2000 },
      gold: { name: 'Gold', color: 'from-yellow-400 to-yellow-600', next: 'Platinum', required: 5000 },
      platinum: { name: 'Platinum', color: 'from-gray-300 to-gray-500', next: 'Diamond', required: 10000 },
      diamond: { name: 'Diamond', color: 'from-cyan-400 to-blue-500', next: null, required: 0 },
    };
    return tiers[tier as keyof typeof tiers] || tiers.bronze;
  };

  if (loading || !loyaltyPoints) {
    return (
      <div className="text-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
        <p className="mt-4 text-gray-600">Loading your rewards...</p>
      </div>
    );
  }

  const tierInfo = getTierInfo(loyaltyPoints.tier);
  const progress = tierInfo.next
    ? Math.min(100, (loyaltyPoints.lifetime_points / tierInfo.required) * 100)
    : 100;

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Header Stats */}
      <div className="grid md:grid-cols-3 gap-6 mb-8">
        {/* Points Balance */}
        <div className="bg-gradient-to-br from-purple-500 to-pink-500 text-white rounded-2xl p-6 shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <Award size={32} />
            <span className="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full">
              Balance
            </span>
          </div>
          <p className="text-4xl font-bold mb-1">{loyaltyPoints.current_balance.toLocaleString()}</p>
          <p className="text-purple-100">Available Points</p>
        </div>

        {/* Lifetime Points */}
        <div className="bg-white border-2 border-gray-200 rounded-2xl p-6">
          <div className="flex items-center justify-between mb-4">
            <TrendingUp className="text-purple-600" size={32} />
            <span className="text-sm bg-purple-100 text-purple-600 px-3 py-1 rounded-full">
              Lifetime
            </span>
          </div>
          <p className="text-4xl font-bold mb-1 text-gray-900">
            {loyaltyPoints.lifetime_points.toLocaleString()}
          </p>
          <p className="text-gray-600">Total Points Earned</p>
        </div>

        {/* Tier Status */}
        <div className={`bg-gradient-to-br ${tierInfo.color} text-white rounded-2xl p-6 shadow-lg`}>
          <div className="flex items-center justify-between mb-4">
            <Star size={32} />
            <span className="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full">
              Tier
            </span>
          </div>
          <p className="text-4xl font-bold mb-1">{tierInfo.name}</p>
          {tierInfo.next && (
            <div className="mt-4">
              <div className="flex justify-between text-sm mb-1">
                <span>{progress.toFixed(0)}% to {tierInfo.next}</span>
                <span>{tierInfo.required - loyaltyPoints.lifetime_points} pts</span>
              </div>
              <div className="bg-white bg-opacity-20 rounded-full h-2">
                <div
                  className="bg-white rounded-full h-2 transition-all"
                  style={{ width: `${progress}%` }}
                />
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-lg shadow-sm border mb-6">
        <div className="flex border-b">
          {(['overview', 'rewards', 'referrals', 'history'] as const).map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`flex-1 px-6 py-4 font-medium transition ${
                activeTab === tab
                  ? 'text-purple-600 border-b-2 border-purple-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              {tab.charAt(0).toUpperCase() + tab.slice(1)}
            </button>
          ))}
        </div>

        <div className="p-6">
          {/* Overview Tab */}
          {activeTab === 'overview' && (
            <div className="space-y-6">
              <div>
                <h3 className="text-xl font-bold mb-4">How to Earn Points</h3>
                <div className="grid md:grid-cols-2 gap-4">
                  <div className="flex items-start gap-3 p-4 bg-purple-50 rounded-lg">
                    <ShoppingBag className="text-purple-600 mt-1" size={24} />
                    <div>
                      <h4 className="font-semibold">Make Purchases</h4>
                      <p className="text-sm text-gray-600">Earn 1 point for every $1 spent</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3 p-4 bg-purple-50 rounded-lg">
                    <Users className="text-purple-600 mt-1" size={24} />
                    <div>
                      <h4 className="font-semibold">Refer Friends</h4>
                      <p className="text-sm text-gray-600">Get 500 points per successful referral</p>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-xl font-bold mb-4">Recent Activity</h3>
                <div className="space-y-2">
                  {transactions.slice(0, 5).map((tx) => (
                    <div key={tx.id} className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                      <div>
                        <p className="font-medium">{tx.description}</p>
                        <p className="text-sm text-gray-600">{new Date(tx.created_at).toLocaleDateString()}</p>
                      </div>
                      <span
                        className={`font-bold ${
                          tx.type === 'earned'
                            ? 'text-green-600'
                            : tx.type === 'redeemed'
                            ? 'text-purple-600'
                            : 'text-gray-600'
                        }`}
                      >
                        {tx.type === 'earned' ? '+' : '-'}{tx.points}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Rewards Tab */}
          {activeTab === 'rewards' && (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {rewards.filter(r => r.is_active).map((reward) => (
                <div key={reward.id} className="border rounded-lg p-6 hover:shadow-lg transition">
                  <div className="flex items-start justify-between mb-4">
                    <Gift className="text-purple-600" size={32} />
                    <span className="bg-purple-100 text-purple-600 px-3 py-1 rounded-full text-sm font-bold">
                      {reward.points_required} pts
                    </span>
                  </div>
                  <h3 className="text-lg font-bold mb-2">{reward.name}</h3>
                  <p className="text-sm text-gray-600 mb-4">{reward.description}</p>
                  {reward.stock_quantity > 0 ? (
                    <button
                      onClick={() => redeemReward(reward.id)}
                      disabled={
                        loyaltyPoints.current_balance < reward.points_required ||
                        redeeming === reward.id
                      }
                      className="w-full bg-purple-600 text-white py-2 rounded-lg font-medium hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
                    >
                      {redeeming === reward.id ? 'Redeeming...' : 'Redeem Now'}
                    </button>
                  ) : (
                    <p className="text-center text-sm text-red-600">Out of Stock</p>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* Referrals Tab */}
          {activeTab === 'referrals' && (
            <div className="space-y-6">
              <div className="bg-gradient-to-br from-purple-500 to-pink-500 text-white rounded-lg p-6">
                <h3 className="text-xl font-bold mb-4">Share & Earn 500 Points</h3>
                <p className="mb-4">Invite friends and earn 500 points when they make their first purchase!</p>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={referralCode}
                    readOnly
                    className="flex-1 px-4 py-2 rounded-lg bg-white text-gray-900"
                  />
                  <button
                    onClick={copyReferralCode}
                    className="bg-white text-purple-600 px-6 py-2 rounded-lg font-medium hover:bg-purple-50 transition flex items-center gap-2"
                  >
                    {copied ? <Check size={20} /> : <Copy size={20} />}
                    {copied ? 'Copied!' : 'Copy'}
                  </button>
                </div>
              </div>

              <div>
                <h3 className="text-lg font-bold mb-4">Your Referrals ({referrals.length})</h3>
                <div className="space-y-2">
                  {referrals.map((ref) => (
                    <div key={ref.id} className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                      <div>
                        <p className="font-medium">
                          {ref.referred_user?.name || 'Pending signup'}
                        </p>
                        <p className="text-sm text-gray-600">
                          Status: {ref.status.charAt(0).toUpperCase() + ref.status.slice(1)}
                        </p>
                      </div>
                      <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                        ref.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'
                      }`}>
                        {ref.points_awarded > 0 ? `+${ref.points_awarded} pts` : 'Pending'}
                      </span>
                    </div>
                  ))}
                  {referrals.length === 0 && (
                    <p className="text-center text-gray-500 py-8">No referrals yet. Start sharing your code!</p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* History Tab */}
          {activeTab === 'history' && (
            <div className="space-y-2">
              {transactions.map((tx) => (
                <div key={tx.id} className="flex justify-between items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                  <div>
                    <p className="font-medium">{tx.description}</p>
                    <p className="text-sm text-gray-600">
                      {tx.source} • {new Date(tx.created_at).toLocaleString()}
                    </p>
                  </div>
                  <span
                    className={`text-lg font-bold ${
                      tx.type === 'earned'
                        ? 'text-green-600'
                        : tx.type === 'redeemed'
                        ? 'text-purple-600'
                        : 'text-gray-600'
                    }`}
                  >
                    {tx.type === 'earned' ? '+' : '-'}{tx.points}
                  </span>
                </div>
              ))}
              {transactions.length === 0 && (
                <p className="text-center text-gray-500 py-8">No transaction history yet</p>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
