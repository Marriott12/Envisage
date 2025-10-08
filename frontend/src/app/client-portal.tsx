export default function ClientPortal() {
  return (
    <section className="py-16 px-4 max-w-4xl mx-auto">
      <h2 className="text-3xl font-bold text-blue-700 mb-8 text-center">Client Portal</h2>
      <div className="bg-white rounded-lg shadow p-8 flex flex-col items-center">
        <p className="text-gray-700 mb-4">Sign in to manage your projects, view support tickets, and collaborate with our team.</p>
        <button className="px-8 py-3 bg-blue-700 text-white rounded hover:bg-blue-800 transition">Sign In</button>
      </div>
    </section>
  );
}
