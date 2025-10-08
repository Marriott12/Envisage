export default function Marketplace() {
  return (
    <section className="py-16 px-4 max-w-6xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Marketplace</h2>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="bg-white rounded-lg shadow p-6 flex flex-col items-center">
          <span className="text-5xl mb-4">🛒</span>
          <h3 className="font-bold text-xl mb-2">Product 1</h3>
          <p className="text-gray-600 mb-4">Description for product 1.</p>
          <button className="px-6 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition">View Details</button>
        </div>
        <div className="bg-white rounded-lg shadow p-6 flex flex-col items-center">
          <span className="text-5xl mb-4">🛒</span>
          <h3 className="font-bold text-xl mb-2">Product 2</h3>
          <p className="text-gray-600 mb-4">Description for product 2.</p>
          <button className="px-6 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition">View Details</button>
        </div>
        <div className="bg-white rounded-lg shadow p-6 flex flex-col items-center">
          <span className="text-5xl mb-4">🛒</span>
          <h3 className="font-bold text-xl mb-2">Product 3</h3>
          <p className="text-gray-600 mb-4">Description for product 3.</p>
          <button className="px-6 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition">View Details</button>
        </div>
      </div>
    </section>
  );
}
