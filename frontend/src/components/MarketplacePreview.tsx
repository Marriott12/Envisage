export default function MarketplacePreview() {
  return (
    <section id="marketplace" className="py-16 bg-gray-50">
      <h2 className="text-3xl font-bold text-center text-blue-700 mb-8">Marketplace Preview</h2>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Example product cards */}
        {[1,2,3].map((i) => (
          <div key={i} className="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div className="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mb-4">
              <span className="text-3xl">🖥️</span>
            </div>
            <h3 className="font-bold text-lg mb-2">Product {i}</h3>
            <p className="text-gray-600 mb-4">Short description of product {i}.</p>
            <button className="px-6 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition">View Details</button>
          </div>
        ))}
      </div>
    </section>
  );
}
