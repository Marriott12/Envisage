export default function Blog() {
  return (
    <section className="py-16 px-4 max-w-5xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Blog & Insights</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">How Digital Marketplaces Empower Africa</h3>
          <p className="text-gray-600">Exploring the impact of online platforms on business growth and innovation across the continent.</p>
        </div>
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-xl mb-2">Top 5 Tech Skills for 2026</h3>
          <p className="text-gray-600">Stay ahead with these in-demand skills for the future of work and entrepreneurship.</p>
        </div>
      </div>
    </section>
  );
}
